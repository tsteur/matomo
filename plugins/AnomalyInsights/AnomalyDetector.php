<?php

namespace Piwik\Plugins\AnomalyInsights;

use DateInterval;
use DateTimeImmutable;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Db;
use Throwable;

class AnomalyDetector
{
    private $db;
    private $concurrency;

    public function __construct($db = null, ?int $concurrency = null)
    {
        $this->db = $db ?: Db::get();
        $this->concurrency = $concurrency ?? (int) StaticContainer::get('anomalyinsights.concurrency');
    }

    /**
     * Sweep through all known sites and persist anomalies.
     */
    public function scanWindow(?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): void
    {
        $to = $to ?: new DateTimeImmutable('now');
        $from = $from ?: $to->sub(new DateInterval('P1D'));

        $siteIds = $this->db->fetchCol(sprintf('SELECT idsite FROM %s ORDER BY idsite DESC', Common::prefixTable('site')));
        if (empty($siteIds)) {
            return;
        }

        $chunks = array_chunk($siteIds, max(1, $this->concurrency));
        foreach ($chunks as $chunk) {
            foreach ($chunk as $siteId) {
                $this->scanSite((int) $siteId, $from, $to);
            }
        }
    }

    public function scanSite(int $siteId, DateTimeImmutable $from, DateTimeImmutable $to): void
    {
        $metrics = ['visits', 'conversions'];

        foreach ($metrics as $metric) {
            try {
                $stats = $this->loadMetricSnapshot($siteId, $metric, $from, $to);
                if (empty($stats['window'])) {
                    continue;
                }

                $score = $this->score($stats['baseline'], $stats['window']);
                $this->persistInsight($siteId, $metric, $score, $from, $to, $stats);
            } catch (Throwable $e) {
                continue;
            }
        }
    }

    private function loadMetricSnapshot(int $siteId, string $metric, DateTimeImmutable $from, DateTimeImmutable $to)
    {
        $table = $metric === 'visits' ? 'log_visit' : 'log_conversion';
        $column = $metric === 'visits' ? 'idvisit' : 'idlink_va';
        $windowStart = $from->format('Y-m-d H:i:s');
        $windowEnd = $to->format('Y-m-d H:i:s');

        $windowSql = sprintf(
            'SELECT COUNT(%s) as window FROM %s WHERE idsite = %d AND server_time BETWEEN "%s" AND "%s"',
            $column,
            Common::prefixTable($table),
            $siteId,
            $windowStart,
            $windowEnd
        );

        $windowResult = $this->db->fetchRow($windowSql);

        $baselineSql = sprintf(
            'SELECT COUNT(%1$s) as baseline FROM %2$s WHERE idsite = %3$d AND server_time BETWEEN DATE_SUB("%4$s", INTERVAL 14 DAY) AND "%4$s"',
            $column,
            Common::prefixTable($table),
            $siteId,
            $windowStart
        );

        $baselineRow = $this->db->fetchRow($baselineSql);

        return [
            'baseline' => (int) ($baselineRow['baseline'] ?? 0),
            'window' => (int) ($windowResult['window'] ?? 0),
        ];
    }

    private function score($baseline, $windowCount)
    {
        if ($baseline <= 0) {
            return 1.0;
        }

        $delta = $windowCount - $baseline;
        $score  = $delta / max(1, $baseline);

        return round(abs($score), 4);
    }

    private function persistInsight(int $siteId, string $metric, float $score, DateTimeImmutable $from, DateTimeImmutable $to, array $stats): void
    {
        $table = Common::prefixTable('anomaly_insights');
        $payload = json_encode(
            [
                'metric' => $metric,
                'delta' => $stats['window'] - $stats['baseline'],
                'baseline' => $stats['baseline'],
                'sample' => $stats['window'],
            ]
        );

        $sql = sprintf(
            'INSERT INTO %s (site_id, metric, score, window_start, window_end, explanation_json, created_at) VALUES (%d, "%s", %.4f, "%s", "%s", "%s", "%s")',
            $table,
            $siteId,
            $metric,
            $score,
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s'),
            addslashes($payload),
            (new \DateTime('now'))->format('Y-m-d H:i:s')
        );

        Db::query($sql);
    }
}

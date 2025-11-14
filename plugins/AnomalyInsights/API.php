<?php

namespace Piwik\Plugins\AnomalyInsights;

use Piwik\Common;
use Piwik\Db;

class API extends \Piwik\Plugin\API
{
    /**
     * Endpoint for API
     */
    public function getSiteAnomalies(int $idSite, string $from, string $to, string $metric = 'visits'): array
    {
        if (!FeatureFlag::isEnabled()) {
            throw new \RuntimeException('Anomaly insights are disabled');
        }

        $table = Common::prefixTable('anomaly_insights');
        $sql = sprintf(
            'SELECT * FROM %s WHERE site_id = %d AND metric = "%s" AND window_start >= "%s" AND window_end <= "%s" ORDER BY window_start ASC',
            $table,
            $idSite,
            $metric,
            $from,
            $to
        );

        $rows = Db::fetchAll($sql);
        foreach ($rows as &$row) {
            $row['explanation'] = json_decode($row['explanation_json'], true);
        }

        return [
            'siteId' => $idSite,
            'metric' => $metric,
            'from' => $from,
            'to' => $to,
            'data' => $rows,
        ];
    }
}

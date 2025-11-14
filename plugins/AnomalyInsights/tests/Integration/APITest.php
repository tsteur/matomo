<?php

namespace Piwik\Plugins\AnomalyInsights\tests\Integration;

use PHPUnit\Framework\TestCase;
use Piwik\Config;
use Piwik\Db;
use Piwik\Plugins\AnomalyInsights\API;

class APITest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::getInstance()->General['enable_anomaly_insights'] = 1;
        Db::setDatabaseObject(new class {
            public function fetchAll($sql, $parameters = [])
            {
                return [
                    [
                        'id' => 1,
                        'site_id' => 99,
                        'metric' => 'visits',
                        'score' => 0.91,
                        'window_start' => '2024-01-01 00:00:00',
                        'window_end' => '2024-01-01 01:00:00',
                        'explanation_json' => '{"html":"<em>huge spike</em>"}',
                        'created_at' => '2024-01-01 01:00:01',
                    ],
                ];
            }
        });
    }

    protected function tearDown(): void
    {
        Db::setDatabaseObject(null);
        parent::tearDown();
    }

    public function testGetSiteAnomaliesReturnsPayload()
    {
        $api = new API();
        $result = $api->getSiteAnomalies(99, '2024-01-01', '2024-01-02');

        $this->assertSame(99, $result['siteId']);
        $this->assertSame('visits', $result['metric']);
        $this->assertCount(1, $result['data']);
    }
}

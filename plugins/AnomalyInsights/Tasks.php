<?php

namespace Piwik\Plugins\AnomalyInsights;

use DateTimeImmutable;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\AnomalyInsights\API;

class Tasks extends \Piwik\Plugin\Tasks
{
    public function schedule()
    {
        $this->hourly('enqueueWindowScan');
    }

    public function enqueueWindowScan(): void
    {
        $detector = StaticContainer::get(AnomalyDetector::class);
        $now = new DateTimeImmutable('now');
        $yesterday = $now->modify('-1 day');

        $detector->scanWindow($yesterday, $now);
    }
}

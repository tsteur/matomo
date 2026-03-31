<?php

return [
    'anomalyinsights.concurrency' => \Piwik\DI::value(function () {
        $value = getenv('ANOMALY_JOB_CONCURRENCY');
        if ($value === false || $value === '') {
            return 32;
        }

        return (int) $value;
    }),
    \Piwik\Plugins\AnomalyInsights\AnomalyDetector::class => \Piwik\DI::autowire()
        ->constructorParameter('concurrency', \Piwik\DI::get('anomalyinsights.concurrency')),
];

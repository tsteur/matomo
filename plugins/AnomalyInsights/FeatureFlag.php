<?php

namespace Piwik\Plugins\AnomalyInsights;

use Piwik\Config;

class FeatureFlag
{
    public static function isEnabled(): bool
    {
        $config = Config::getInstance();
        $general = $config->General;

        if (array_key_exists('enable_anomaly_insights', $general)) {
            return (bool) $general['enable_anomaly_insights'];
        }

        $env = getenv('ANOMALY_INSIGHTS_ENABLED');

        return $env !== false && $env !== '0' && $env !== '';
    }
}

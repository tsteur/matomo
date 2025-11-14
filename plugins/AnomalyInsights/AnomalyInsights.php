<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\AnomalyInsights;

use Piwik\Db;

class AnomalyInsights extends \Piwik\Plugin
{

    public function install()
    {
        $createTable = <<<SQL
CREATE TABLE anomaly_insights (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    site_id INT UNSIGNED NOT NULL,
    metric VARCHAR(32) NOT NULL,
    score DECIMAL(8,4) NOT NULL,
    window_start DATETIME NOT NULL,
    window_end DATETIME NOT NULL,
    explanation_json TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;

        Db::exec($createTable);

    }
}

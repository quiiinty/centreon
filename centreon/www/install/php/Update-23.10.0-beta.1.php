<?php

/*
 * Copyright 2005 - 2022 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */

require_once __DIR__ . '/../../class/centreonLog.class.php';
$centreonLog = new CentreonLog();

//error specific content
$versionOfTheUpgrade = 'UPGRADE - 23.10.0-beta.1: ';
$errorMessage = '';


try {
    // TODO add tables creation in createTables.sql
    // TODO : charset utf8 or utf8mb6 ?

    $errorMessage = "Impossible to create `notification` table";
    $pearDB->query(
        <<<'SQL'
            // TODO: behaviour if timeperiod is deleted ?
            CREATE TABLE IF NOT EXISTS `notification` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `timeperiod_id` int(11) NOT NULL,
                `hostgroup_events` tinyint unsigned NOT NULL DEFAULT 0,
                `included_service_events` tinyint unsigned NOT NULL DEFAULT 0,
                `servicegroup_events` tinyint unsigned NOT NULL DEFAULT 0,
                `is_activated` bool NOT NULL DEFAULT true,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`timeperiod_id`)
                REFERENCES `timeperiod` (`tp_id`)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            SQL
    );
    $errorMessage = "Impossible to create `notification_message` table";
    $pearDB->query(
        <<<'SQL'
            CREATE TABLE IF NOT EXISTS `notification_message` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `notification_id` int(11) NOT NULL,
                `channel` enum('Email', 'Slack', 'SMS') NOT NULL DEFAULT 'Email',
                `subject` varchar(255) NOT NULL,
                `message` text NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`notification_id`)
                REFERENCES `notification` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            SQL
    );



    // Transactional queries
    $pearDB->beginTransaction();


    $pearDB->commit();
} catch (\Exception $e) {
    if ($pearDB->inTransaction()) {
        $pearDB->rollBack();
    }

    $centreonLog->insertLog(
        4,
        $versionOfTheUpgrade . $errorMessage
        . ' - Code : ' . (int) $e->getCode()
        . ' - Error : ' . $e->getMessage()
        . ' - Trace : ' . $e->getTraceAsString()
    );

    throw new \Exception($versionOfTheUpgrade . $errorMessage, (int) $e->getCode(), $e);
}

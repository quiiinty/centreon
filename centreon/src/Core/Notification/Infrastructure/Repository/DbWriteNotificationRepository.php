<?php

/*
 * Copyright 2005 - 2023 Centreon (https://www.centreon.com/)
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

declare(strict_types=1);

namespace Core\Notification\Infrastructure\Repository;

use Core\Common\Infrastructure\Repository\AbstractRepositoryRDB;
use Core\Notification\Application\Repository\WriteNotificationRepositoryInterface;
use Core\Notification\Domain\Model\NewNotification;

class DbWriteNotificationRepository extends AbstractRepositoryRDB implements WriteNotificationRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function add(NewNotification $notification): int
    {
        $this->debug('Add notification configuration', ['notification' => $notification]);

        $request = $this->translateDbName(
            'INSERT INTO `:db`.notification
            (name, timepriod_id, is_activated) VALUES
            (:name, :timeperiodId, :isActivated)'
        );
        $statement = $this->db->prepare($request);

        $statement->bindValue(':name', $notification->getName(), \PDO::PARAM_STR);
        $statement->bindValue(':timeperiodId', $notification->getTimePeriodId(), \PDO::PARAM_INT);
        $statement->bindValue(':isActivated', $notification->isActivated(), \PDO::PARAM_BOOL);

        $statement->execute();

        return (int) $this->db->lastInsertId();
    }
}

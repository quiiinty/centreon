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

use Centreon\Infrastructure\DatabaseConnection;
use Core\Common\Infrastructure\Repository\AbstractRepositoryRDB;
use Core\Notification\Domain\Model\NotificationGenericResource;

class HostGroupResourceRequestProvider extends AbstractRepositoryRDB implements NotificationResourceReQuestProviderInterface
{
    public function __construct(DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritDoc}
     */
    public function findByIds(array $resourceIds): array
    {
        // $this->info('Get a notification configuration with id #' . $notificationId);

        // $request = $this->translateDbName(
        //     'SELECT id, name, timeperiod_id, is_activated
        //     FROM `:db`.notification
        //     WHERE id = :notificationId'
        // );
        // $statement = $this->db->prepare($request);
        // $statement->bindValue(':notificationId', $notificationId, \PDO::PARAM_INT);
        // $statement->execute();

        // $result = $statement->fetch(\PDO::FETCH_ASSOC);
        // if ($result === false) {

        //     return null;
        // }

        // /**
        //  * @var array{id:int,name:string,timeperiod_id:int|null,is_activated:bool} $result
        //  */
        // $notification = $this->createNotificationFromArray($result);

        // return $notification;
        return [new NotificationGenericResource(1, 'ResourceHGTestName')];
    }
}

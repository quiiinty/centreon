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
use Core\Notification\Application\Repository\ReadNotificationRepositoryInterface;
use Core\Notification\Domain\Model\Notification;

class DbReadNotificationRepository extends AbstractRepositoryRDB implements ReadNotificationRepositoryInterface
{
    /**
     * @var NotificationResourceRequestProviderInterface[] $notificationResourceProviders
     */
    private array $notificationResourceProviders;

    /**
     * @param DatabaseConnection $db
     * @param \Traversable<NotificationResourceRequestProviderInterface> $notificationResourceProviders
     */
    public function __construct(DatabaseConnection $db, \Traversable $notificationResourceProviders)
    {
        $this->db = $db;
        $this->notificationResourceProviders = iterator_to_array($notificationResourceProviders);
        $this->assertThereIsAtLeastOneProviderType($this->notificationResourceProviders);
    }

    /**
     * @param NotificationResourceRequestProviderInterface[] $providers
     * @throws \InvalidArgumentException
     */
    private function assertThereIsAtLeastOneProviderType(array $providers): void
    {
        if (count($providers) === 0) {
            throw new \InvalidArgumentException('There must be at least one notification resource provider');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $notificationId): ?Notification
    {
        $this->info('Get a notification configuration with id #' . $notificationId);

        $request = $this->translateDbName(
            'SELECT id, name, timeperiod_id, is_activated
            FROM `:db`.notification
            WHERE id = :notificationId'
        );
        $statement = $this->db->prepare($request);
        $statement->bindValue(':notificationId', $notificationId, \PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($result === false) {

            return null;
        }

        /**
         * @var array{id:int,name:string,timeperiod_id:int|null,is_activated:bool} $result
         */
        $notification = $this->createNotificationFromArray($result);

        // TODO loop on $notificationResourceProviders

        return $notification;
    }

    /**
     * {@inheritDoc}
     */
    public function exists(int $notificationId): bool {
        $this->info('Check existence of notification configuration with id #' . $notificationId);

        $request = $this->translateDbName('SELECT 1 FROM `:db`.notification WHERE id = :notificationId');
        $statement = $this->db->prepare($request);
        $statement->bindValue(':notificationId', $notificationId, \PDO::PARAM_INT);
        $statement->execute();

        return (bool) $statement->fetchColumn();
    }

    /**
     * {@inheritDoc}
     */
    public function existsByName(string $notificationName): bool {
        $this->info('Check existence of notification configuration with name ' . $notificationName);

        $request = $this->translateDbName('SELECT 1 FROM `:db`.notification WHERE name = :notificationName');
        $statement = $this->db->prepare($request);
        $statement->bindValue(':notificationName', $notificationName, \PDO::PARAM_STR);
        $statement->execute();

        return (bool) $statement->fetchColumn();
    }

    /**
     * @param array{
     *      id:int,
     *      name:string,
     *      timeperiod_id:int|null
     *      is_activated:bool
     * } $result
     *
     * @return Notification
     */
    private function createNotificationFromArray(array $result): Notification
    {
        return new Notification(
            $result['id'],
            $result['name'],
            $result['timeperiod_id'],
            $result['is_activated'],
        );
    }
}

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

namespace Core\Notification\Application\Repository;

use Centreon\Domain\RequestParameters\Interfaces\RequestParametersInterface;
use Core\Security\AccessGroup\Domain\Model\AccessGroup;
use Core\Notification\Domain\Model\Notification;

interface ReadNotificationRepositoryInterface
{
    /**
     * Find one notification without acl.
     *
     * @param int $notificationId
     *
     * @throws \Throwable
     *
     * @return Notification|null
     */
    public function findById(int $notificationId): ?Notification;

    /**
     * Tells whether the notification exists.
     *
     * @param int $notificationId
     *
     * @throws \Throwable
     *
     * @return bool
     */
    public function exists(int $notificationId): bool;

    /**
     * Tells whether the notification name already exists.
     * This method does not need an acl version of it.
     *
     * @param string $notificationName
     *
     * @throws \Throwable
     *
     * @return bool
     */
    public function existsByName(string $notificationName): bool;
}

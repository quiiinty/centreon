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

namespace Core\Notification\Domain\Model\Provider;

use Core\Common\Domain\HostEvent;
use Core\Common\Domain\ServiceEvent;
use Core\Notification\Domain\Model\NotificationGenericResource;
use Core\Notification\Domain\Model\NotificationResourceConfiguration;

class ServiceGroupResourceConfiguration extends NotificationResourceConfiguration
{
    /**
     * @param string $type
     * @param NotificationGenericResource[] $linkedResources
     * @param array<HostEvent|ServiceEvent> $events
     */
    public function __construct(
        protected array $linkedResources = [],
        protected array $events = [],
    ) {
        parent::__construct('ServiceGroup', $linkedResources, $events);
    }

    /**
     * @inheritDoc
     */
    public function addEvent(HostEvent|ServiceEvent $event): void
    {
        /**
         * TODO: block Host type event
         */
    }
}

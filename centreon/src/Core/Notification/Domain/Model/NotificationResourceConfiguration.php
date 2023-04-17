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
centreon/tests/php/Core/* * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */

declare(strict_types=1);

namespace Core\Notification\Domain\Model;

use Core\Common\Domain\HostEvent;
use Core\Common\Domain\ServiceEvent;

// TODO find better name ?
// TODO abstract or interface ?
abstract class NotificationResourceConfiguration
{
    /**
     * @param string $type
     * @param NotificationGenericResource[] $linkedResources
     * @param array<HostEvent|ServiceEvent> $events
     */
    public function __construct(
        protected string $type = '',
        protected array $linkedResources = [],
        protected array $events = [],
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }
    public function getLinkedResources(): array
    {
        return $this->linkedResources;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @param array<HostEvent|ServiceEvent> $events
     * @return void
     */
    public function setEvents(array $events): void
    {
        $this->events = [];

        foreach ($events as $event) {
            $this->addEvent($event);
        }
    }

    public function addEvent(HostEvent|ServiceEvent $event): void
    {
        /**
         * TODO
         *  diff between events from current configuration UI and new notification form ?
         */
        if ($event instanceof HostEvent) {
            $this->events[] = match ($event) {
                HostEvent::Down, HostEvent::Unreachable => $event,

                // TODO : assertion exception or valueError ?
                default => throw new \ValueError(''),
            };
        } else {
            $this->events[] = match ($event) {
                ServiceEvent::Warning, ServiceEvent::Critical => $event,
                // TODO : assertion exception or valueError ?
                default => throw new \ValueError(''),
            };
        }
    }
}

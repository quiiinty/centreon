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

namespace Core\Common\Domain;

enum ServiceEvent: string
{
    use LegacyEventEnumTrait, BitmaskEnumTrait;

    case Warning = 'w';
    case Unknown = 'u';
    case Critical = 'c';
    case Recovery = 'r';
    case Flapping = 'f';
    case DowntimeScheduled = 's';
    case None = 'n';

    public function toBit(): int
    {
        return match ($this) {
            self::Warning => 0b000000,
            self::Unknown => 0b000001,
            self::Critical => 0b000010,
            self::Recovery => 0b000100,
            self::Flapping => 0b001000,
            self::DowntimeScheduled => 0b010000,
            self::None => 0b100000,
        };
    }

    public static function getMaxBitmask(): int {
        return 0b111111;
    }
}
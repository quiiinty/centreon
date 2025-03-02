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

declare(strict_types=1);

namespace Core\Security\Vault\Domain\Model;

use Assert\AssertionFailedException;
use Centreon\Domain\Common\Assertion\Assertion;

/**
 * This class represents vault provider entity.
 */
class Vault
{
    public const MIN_LENGTH = 1;
    public const MAX_LENGTH = 255;
    public const MIN_ID = 1;

    /**
     * @param int $id
     * @param string $name
     *
     * @throws AssertionFailedException
     */
    public function __construct(private int $id, private string $name)
    {
        Assertion::min($this->id, self::MIN_ID, 'Vault::id');
        Assertion::minLength($name, self::MIN_LENGTH, 'Vault::name');
        Assertion::maxLength($name, self::MAX_LENGTH, 'Vault::name');
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}

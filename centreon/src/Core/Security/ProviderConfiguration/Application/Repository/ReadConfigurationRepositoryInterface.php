<?php

/*
 * Copyright 2005 - 2022 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
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

namespace Core\Security\ProviderConfiguration\Application\Repository;

use Core\Security\ProviderConfiguration\Domain\Model\Configuration;

interface ReadConfigurationRepositoryInterface
{
    /**
     * Get provider configuration by name
     *
     * @param string $providerType
     * @return Configuration
     */
    public function getConfigurationByType(string $providerType): Configuration;

    /**
     * Get provider configuration by id
     *
     * @param int $id
     * @return Configuration
     */
    public function getConfigurationById(int $id): Configuration;

    /**
     * Get providers configurations
     *
     * @return Configuration[]
     */
    public function findConfigurations(): array;

    /**
     * @return array<string,mixed>
     */
    public function findExcludedUsers(): array;
}

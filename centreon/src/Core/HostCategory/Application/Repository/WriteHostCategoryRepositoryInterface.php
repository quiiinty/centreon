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

namespace Core\HostCategory\Application\Repository;

use Core\HostCategory\Domain\Model\NewHostCategory;

interface WriteHostCategoryRepositoryInterface
{
    /**
     * Delete host category by id.
     *
     * @param int $hostCategoryId
     */
    public function deleteById(int $hostCategoryId): void;

    /**
     * Add a host category
     * Return the id of the host category.
     *
     * @param NewHostCategory $hostCategory
     * @return int
     * @throws \Throwable
     */
    public function add(NewHostCategory $hostCategory): int;
}

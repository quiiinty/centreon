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

namespace Core\TimePeriod\Infrastructure\API\AddTimePeriod;

use Centreon\Domain\Log\LoggerTrait;
use Core\Application\Common\UseCase\AbstractPresenter;
use Core\Application\Common\UseCase\CreatedResponse;
use Core\Application\Common\UseCase\PresenterInterface;
use Core\Infrastructure\Common\Api\Router;
use Core\Infrastructure\Common\Presenter\PresenterFormatterInterface;
use Core\TimePeriod\Application\UseCase\AddTimePeriod\AddTimePeriodResponse;

class AddTimePeriodsPresenter extends AbstractPresenter implements PresenterInterface
{
    use LoggerTrait;

    private const ROUTE_NAME = 'FindTimePeriod';

    /**
     * @param PresenterFormatterInterface $presenterFormatter
     * @param Router $router
     */
    public function __construct(PresenterFormatterInterface $presenterFormatter, readonly private Router $router)
    {
        $this->presenterFormatter = $presenterFormatter;
        parent::__construct($presenterFormatter);
    }

    /**
     * @inheritDoc
     */
    public function present(mixed $data): void
    {
        if (
            $data instanceof CreatedResponse
            && $data->getPayload() instanceof AddTimePeriodResponse
        ) {
            $payload = $data->getPayload();
            $data->setPayload([
                'id' => $payload->id,
                'name' => $payload->name,
                'alias' => $payload->alias,
                'days' => $payload->days,
                'templates' => $payload->templates,
                'exceptions' => $payload->exceptions,
            ]);
            try {
                $this->setResponseHeaders([
                    'Location' => $this->router->generate(self::ROUTE_NAME, ['id' => $payload->id]),
                ]);
            } catch (\Throwable $ex) {
                $this->error('Impossible to generate the location header', [
                    'message' => $ex->getMessage(),
                    'trace' => $ex->getTraceAsString(),
                    'route' => self::ROUTE_NAME,
                    'payload' => $payload,
                ]);
            }
        }
        parent::present($data);
    }
}

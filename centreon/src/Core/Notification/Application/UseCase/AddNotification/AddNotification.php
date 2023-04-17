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
 * For more information : user@centreon.com
 *
 */

declare(strict_types=1);

namespace Core\Notification\Application\UseCase\AddNotification;

use Assert\AssertionFailedException;
use Centreon\Domain\Contact\Contact;
use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Centreon\Domain\Log\LoggerTrait;
use Centreon\Domain\Repository\Interfaces\DataStorageEngineInterface;
use Core\Application\Common\UseCase\ConflictResponse;
use Core\Application\Common\UseCase\CreatedResponse;
use Core\Application\Common\UseCase\ErrorResponse;
use Core\Application\Common\UseCase\ForbiddenResponse;
use Core\Application\Common\UseCase\InvalidArgumentResponse;
use Core\Application\Common\UseCase\PresenterInterface;
use Core\Common\Domain\TrimmedString;
use Core\Notification\Application\Exception\NotificationException;
use Core\Notification\Application\Repository\ReadNotificationRepositoryInterface;
use Core\Notification\Application\Repository\WriteNotificationRepositoryInterface;
use Core\Notification\Domain\Model\NewNotification;
use Core\Notification\Domain\Model\Notification;
use Core\Notification\Domain\Model\NotificationMessage;
use Core\Notification\Domain\Model\NotificationResourceInterface;
use Core\Notification\Infrastructure\API\AddNotification\AddNotificationPresenter;
use Core\Security\AccessGroup\Application\Repository\ReadAccessGroupRepositoryInterface;

final class AddNotification
{
    use LoggerTrait;

    public function __construct(
        private readonly ReadNotificationRepositoryInterface $readNotificationRepository,
        private readonly WriteNotificationRepositoryInterface $writeNotificationRepository,
        private readonly ReadAccessGroupRepositoryInterface $readAccessGroupRepository,
        // TODO : repositories for users, resources, messages, timeperiod ?
        private readonly DataStorageEngineInterface $dataStorageEngine,
        private readonly ContactInterface $user
    ) {
    }

    /**
     * @param AddNotificationRequest $request
     * @param AddNotificationPresenter $presenter
     */
    public function __invoke(
        AddNotificationRequest $request,
        PresenterInterface $presenter
    ): void {
        try {
            // TODO create topology role
            if ($this->user->hasTopologyRole(Contact::ROLE_CONFIGURATION_NOTIFICATION_READ_WRITE)) {

                $this->assertNameDoesNotAlreadyExists($request);
                $this->assertTimePeriodExist($request);
                $this->assertUsersExist($request);
                // TODO assert resources exist (with/without ACLs ?)

                $newNotification = new NewNotification(
                    $request->name,
                    $request->timeperiodId,
                    $request->isActivated
                );
                // TODO create messages, resources


                try {
                    $this->dataStorageEngine->startTransaction();

                    $newNotificationId = $this->writeNotificationRepository->add($newNotification);

                    if ($this->user->isAdmin()) {
                        $this->writeNotificationRepository->add($newNotification);
                       // TODO : add users, resources, messages WITHOUT ACLs checks
                    } else {
                        $accessGroups = $this->readAccessGroupRepository->findByContact($this->user);
                        // TODO : add users, resources, messages WITH ACLs checks
                    }

                    $this->dataStorageEngine->commitTransaction();
                } catch (\Throwable $ex) {
                    $this->error("Rollback of 'Add Notification' transaction.");
                    $this->dataStorageEngine->rollbackTransaction();

                    throw $ex;
                }

                $notification = $this->readNotificationRepository->findById($newNotificationId)
                    ?? throw NotificationException::errorWhileRetrievingObject();
                // TODO : retrieve users, resources, messages
                $users = $resources = $messages = [];

                $presenter->present($this->createResponse($notification, $users, $resources, $messages));
                $this->info('Add notification', ['request' => $request]);
            } else {
                $this->error(
                    "User doesn't have sufficient rights to add notifications",
                    ['user_id' => $this->user->getId()]
                );
                $presenter->setResponseStatus(
                    new ForbiddenResponse(NotificationException::addNotAllowed())
                );
            }
            //TODO : handle conflict type errors ?
        } catch (AssertionFailedException $ex) {
            $presenter->setResponseStatus(new InvalidArgumentResponse($ex));
            $this->error($ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
        } catch (\Throwable $ex) {
            $presenter->setResponseStatus(new ErrorResponse(NotificationException::addNotification()));
            $this->error($ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
        }
    }

    /**
     * @param AddNotificationRequest $request
     *
     * @throws NotificationException
     * @throws \Throwable
     */
    private function assertNameDoesNotAlreadyExists(AddNotificationRequest $request): void
    {
        if ($this->readNotificationRepository->existsByName(new TrimmedString($request->name))) {
            $this->error('Notification name already exists', ['name' => $request->name]);

            throw NotificationException::nameAlreadyExists();
        }
    }

    /**
     * @param AddNotificationRequest $request
     *
     * @throws NotificationException
     * @throws \Throwable
     */
    private function assertTimePeriodExist(AddNotificationRequest $request): void
    {
        // TODO
    }

    /**
     * @param AddNotificationRequest $request
     *
     * @throws NotificationException
     * @throws \Throwable
     */
    private function assertUsersExist(AddNotificationRequest $request): void
    {
        // TODO
    }

    /**
     * @param Notification $notification
     * @param NotificationContact[] $users
     * @param NotificationResourceInterface[] $resources
     * @param NotificationMessage[] $messages
     * @return CreatedResponse
     */
    private function createResponse(
        Notification $notification,
        array $users,
        array $resources,
        array $messages
    ): CreatedResponse
    {
        $response = new AddNotificationResponse();

        $response->id = $notification->getId();
        $response->name = $notification->getName();
        // TODO set users, resources, messages
        $response->isActivated = $notification->isActivated();

        return new CreatedResponse($response->id, $response);
    }
}

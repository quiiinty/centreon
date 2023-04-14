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

namespace Tests\Core\HostTemplate\Domain\Model;

use Assert\InvalidArgumentException;
use Centreon\Domain\Common\Assertion\AssertionException;
use Core\Common\Domain\HostEvent;
use Core\Common\Domain\SnmpVersion;
use Core\Common\Domain\YesNoDefault;
use Core\HostTemplate\Domain\Model\NewHostTemplate;

beforeEach(function (): void {
    $this->createHostTemplate = static function (array $fields = []): NewHostTemplate {
        return new NewHostTemplate(
            ...[
                'name' => 'host-template-name',
                'alias' => 'host-template-alias',
                'snmpVersion' => SnmpVersion::Two,
                'snmpCommunity' => 'snmpCommunity-value',
                'timezoneId' => 1,
                'severityId' => 1,
                'checkCommandId' => 1,
                'checkCommandArgs' => 'checkCommandArgs-value',
                'checkTimeperiodId' => 1,
                'maxCheckAttempts' => 5,
                'normalCheckInterval' => 5,
                'retryCheckInterval' => 5,
                'isActiveCheckEnabled' => YesNoDefault::Yes,
                'isPassiveCheckEnabled' => YesNoDefault::Yes,
                'isNotificationEnabled' => YesNoDefault::Yes,
                'notificationOptions' => [HostEvent::Down, HostEvent::Unreachable],
                'notificationInterval' => 5,
                'notificationTimeperiodId' => 1,
                'addInheritedContactGroup' => true,
                'addInheritedContact' => true,
                'firstNotificationDelay' => 5,
                'recoveryNotificationDelay' => 5,
                'acknowledgementTimeout' => 5,
                'isFreshnessChecked' => YesNoDefault::Yes,
                'freshnessThreshold' => 5,
                'isFlapDetectionEnabled' => YesNoDefault::Yes,
                'lowFlapThreshold' => 5,
                'highFlapThreshold' => 5,
                'isEventHandlerEnabled' => YesNoDefault::Yes,
                'eventHandlerCommandId' => 1,
                'eventHandlerCommandArgs' => 'eventHandlerCommandArgs-value',
                'noteUrl' => 'noteUrl-value',
                'note' => 'note-value',
                'actionUrl' => 'actionUrl-value',
                'iconId' => 1,
                'iconAlternative' => 'iconAlternative-value',
                'comment' => 'comment-value',
                'isActivated' => false,
                'isLocked' => true,
                ...$fields,
            ]
        );
    };


});

it('should return properly set host template instance (all properties)', function (): void {
    $hostTemplate = ($this->createHostTemplate)();

    expect($hostTemplate->getName())->toBe('host-template-name')
        ->and($hostTemplate->getAlias())->toBe('host-template-alias')
        ->and($hostTemplate->getSnmpVersion())->toBe(SnmpVersion::Two)
        ->and($hostTemplate->getSnmpCommunity())->toBe('snmpCommunity-value')
        ->and($hostTemplate->getTimezoneId())->toBe(1)
        ->and($hostTemplate->getSeverityId())->toBe(1)
        ->and($hostTemplate->getCheckCommandId())->toBe(1)
        ->and($hostTemplate->getCheckCommandArgs())->toBe('checkCommandArgs-value')
        ->and($hostTemplate->getCheckTimeperiodId())->toBe(1)
        ->and($hostTemplate->getMaxCheckAttempts())->toBe(5)
        ->and($hostTemplate->getNormalCheckInterval())->toBe(5)
        ->and($hostTemplate->getRetryCheckInterval())->toBe(5)
        ->and($hostTemplate->isActiveCheckEnabled())->toBe(YesNoDefault::Yes)
        ->and($hostTemplate->isPassiveCheckEnabled())->toBe(YesNoDefault::Yes)
        ->and($hostTemplate->isNotificationEnabled())->toBe(YesNoDefault::Yes)
        ->and($hostTemplate->getNotificationOptions())->toBe([HostEvent::Down, HostEvent::Unreachable])
        ->and($hostTemplate->getNotificationInterval())->toBe(5)
        ->and($hostTemplate->getNotificationTimeperiodId())->toBe(1)
        ->and($hostTemplate->addInheritedContactGroup())->toBe(true)
        ->and($hostTemplate->addInheritedContact())->toBe(true)
        ->and($hostTemplate->getFirstNotificationDelay())->toBe(5)
        ->and($hostTemplate->getRecoveryNotificationDelay())->toBe(5)
        ->and($hostTemplate->getAcknowledgementTimeout())->toBe(5)
        ->and($hostTemplate->isFreshnessChecked())->toBe(YesNoDefault::Yes)
        ->and($hostTemplate->getFreshnessThreshold())->toBe(5)
        ->and($hostTemplate->isFlapDetectionEnabled())->toBe(YesNoDefault::Yes)
        ->and($hostTemplate->getLowFlapThreshold())->toBe(5)
        ->and($hostTemplate->getHighFlapThreshold())->toBe(5)
        ->and($hostTemplate->isEventHandlerEnabled())->toBe(YesNoDefault::Yes)
        ->and($hostTemplate->getEventHandlerCommandId())->toBe(1)
        ->and($hostTemplate->getEventHandlerCommandArgs())->toBe('eventHandlerCommandArgs-value')
        ->and($hostTemplate->getNoteUrl())->toBe('noteUrl-value')
        ->and($hostTemplate->getNote())->toBe('note-value')
        ->and($hostTemplate->getActionUrl())->toBe('actionUrl-value')
        ->and($hostTemplate->getIconId())->toBe(1)
        ->and($hostTemplate->getIconAlternative())->toBe('iconAlternative-value')
        ->and($hostTemplate->getComment())->toBe('comment-value')
        ->and($hostTemplate->isActivated())->toBe(false)
        ->and($hostTemplate->isLocked())->toBe(true);
});

it('should return properly set host template instance (mandatory properties only)', function (): void {
    $hostTemplate = new NewHostTemplate('host-template-name', 'host-template-alias');

    expect($hostTemplate->getName())->toBe('host-template-name')
        ->and($hostTemplate->getAlias())->toBe('host-template-alias')
        ->and($hostTemplate->getSnmpVersion())->toBe(null)
        ->and($hostTemplate->getSnmpCommunity())->toBe('')
        ->and($hostTemplate->getTimezoneId())->toBe(null)
        ->and($hostTemplate->getSeverityId())->toBe(null)
        ->and($hostTemplate->getCheckCommandId())->toBe(null)
        ->and($hostTemplate->getCheckCommandArgs())->toBe('')
        ->and($hostTemplate->getCheckTimeperiodId())->toBe(null)
        ->and($hostTemplate->getMaxCheckAttempts())->toBe(null)
        ->and($hostTemplate->getNormalCheckInterval())->toBe(null)
        ->and($hostTemplate->getRetryCheckInterval())->toBe(null)
        ->and($hostTemplate->isActiveCheckEnabled())->toBe(YesNoDefault::Default)
        ->and($hostTemplate->isPassiveCheckEnabled())->toBe(YesNoDefault::Default)
        ->and($hostTemplate->isNotificationEnabled())->toBe(YesNoDefault::Default)
        ->and($hostTemplate->getNotificationOptions())->toBe([])
        ->and($hostTemplate->getNotificationInterval())->toBe(null)
        ->and($hostTemplate->getNotificationTimeperiodId())->toBe(null)
        ->and($hostTemplate->addInheritedContactGroup())->toBe(false)
        ->and($hostTemplate->addInheritedContact())->toBe(false)
        ->and($hostTemplate->getFirstNotificationDelay())->toBe(null)
        ->and($hostTemplate->getRecoveryNotificationDelay())->toBe(null)
        ->and($hostTemplate->getAcknowledgementTimeout())->toBe(null)
        ->and($hostTemplate->isFreshnessChecked())->toBe(YesNoDefault::Default)
        ->and($hostTemplate->getFreshnessThreshold())->toBe(null)
        ->and($hostTemplate->isFlapDetectionEnabled())->toBe(YesNoDefault::Default)
        ->and($hostTemplate->getLowFlapThreshold())->toBe(null)
        ->and($hostTemplate->getHighFlapThreshold())->toBe(null)
        ->and($hostTemplate->isEventHandlerEnabled())->toBe(YesNoDefault::Default)
        ->and($hostTemplate->getEventHandlerCommandId())->toBe(null)
        ->and($hostTemplate->getEventHandlerCommandArgs())->toBe('')
        ->and($hostTemplate->getNoteUrl())->toBe('')
        ->and($hostTemplate->getNote())->toBe('')
        ->and($hostTemplate->getActionUrl())->toBe('')
        ->and($hostTemplate->getIconId())->toBe(null)
        ->and($hostTemplate->getIconAlternative())->toBe('')
        ->and($hostTemplate->getComment())->toBe('')
        ->and($hostTemplate->isActivated())->toBe(true)
        ->and($hostTemplate->isLocked())->toBe(false);
});

// mandatory fields
foreach (
    [
        'name',
        'alias',
    ] as $field
) {
    it(
        "should throw an exception when host template {$field} is an empty string",
        fn() => ($this->createHostTemplate)([$field => ''])
    )->throws(
        InvalidArgumentException::class,
        AssertionException::notEmptyString("NewHostTemplate::{$field}")->getMessage()
    );
}

// name and conmmands args should be formated
it("should return trimmed and formated name field after construct", function (): void {
    $hostTemplate = new NewHostTemplate('    host template name   ', 'alias');

    expect($hostTemplate->getName())->toBe('host_template_name');
});

foreach (
    [
        'checkCommandArgs',
        'eventHandlerCommandArgs',
    ] as $field
) {
    it(
        "should return a trimmed and formated field {$field}",
        function () use ($field): void {
            $hostTemplate = ($this->createHostTemplate)([$field => "  \ncommandArgs\tvalue\r  "]);
            $valueFromGetter = $hostTemplate->{'get' . $field}();

            expect($valueFromGetter)->toBe('#BR#commandArgs#T#value#R#');
        }
    );
}

foreach (
    [
        'name',
        'alias',
        'snmpCommunity',
        'checkCommandArgs',
        'eventHandlerCommandArgs',
        'noteUrl',
        'note',
        'actionUrl',
        'iconAlternative',
        'comment',
    ] as $field
) {
    it(
        "should return trimmed field {$field} after construct",
        function () use ($field): void {
            $hostTemplate = ($this->createHostTemplate)([$field => '  abcd ']);
            $valueFromGetter = $hostTemplate->{'get' . $field}();

            expect($valueFromGetter)->toBe('abcd');
        }
    );
}

// too long fields
foreach (
    [
        'name' => NewHostTemplate::MAX_NAME_LENGTH,
        'alias' => NewHostTemplate::MAX_ALIAS_LENGTH,
        'snmpCommunity' => NewHostTemplate::MAX_SNMP_COMMUNITY_LENGTH,
        'checkCommandArgs' => NewHostTemplate::MAX_CHECK_COMMAND_ARGS_LENGTH,
        'eventHandlerCommandArgs' => NewHostTemplate::MAX_EVENT_HANDLER_COMMAND_ARGS_LENGTH,
        'noteUrl' => NewHostTemplate::MAX_NOTE_URL_LENGTH,
        'note' => NewHostTemplate::MAX_NOTE_LENGTH,
        'actionUrl' => NewHostTemplate::MAX_ACTION_URL_LENGTH,
        'iconAlternative' => NewHostTemplate::MAX_ICON_ALT_LENGTH,
        'comment' => NewHostTemplate::MAX_COMMENT_LENGTH,
    ] as $field => $length
) {
    $tooLong = str_repeat('a', $length + 1);
    it(
        "should throw an exception when host template {$field} is too long",
        fn() => ($this->createHostTemplate)([$field => $tooLong])
    )->throws(
        InvalidArgumentException::class,
        AssertionException::maxLength($tooLong, $length + 1, $length, "NewHostTemplate::{$field}")->getMessage()
    );
}

// foreign keys fields
foreach (
    [
        'timezoneId',
        'severityId',
        'checkCommandId',
        'checkTimeperiodId',
        'notificationTimeperiodId',
        'eventHandlerCommandId',
        'iconId',
    ] as $field
) {
    it(
        "should throw an exception when host template {$field} is not > 0",
        fn() => ($this->createHostTemplate)([$field => 0])
    )->throws(
        InvalidArgumentException::class,
        AssertionException::positiveInt(0, "NewHostTemplate::{$field}")->getMessage()
    );
}

// integer >= 0 field
foreach (
    [
        'maxCheckAttempts',
        'normalCheckInterval',
        'retryCheckInterval',
        'notificationInterval',
        'firstNotificationDelay',
        'recoveryNotificationDelay',
        'acknowledgementTimeout',
        'freshnessThreshold',
        'lowFlapThreshold',
        'highFlapThreshold',
    ] as $field
) {
    it(
        "should throw an exception when host template {$field} is not >= 0",
        fn() => ($this->createHostTemplate)([$field => -1])
    )->throws(
        InvalidArgumentException::class,
        AssertionException::min(-1, 0, "NewHostTemplate::{$field}")->getMessage()
    );
}
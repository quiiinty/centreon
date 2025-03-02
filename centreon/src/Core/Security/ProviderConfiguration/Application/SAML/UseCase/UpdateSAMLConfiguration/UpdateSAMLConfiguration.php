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

namespace Core\Security\ProviderConfiguration\Application\SAML\UseCase\UpdateSAMLConfiguration;

use Assert\AssertionFailedException;
use Centreon\Domain\Log\LoggerTrait;
use Core\Contact\Domain\Model\ContactGroup;
use Core\Contact\Domain\Model\ContactTemplate;
use Core\Application\Common\UseCase\ErrorResponse;
use Core\Application\Common\UseCase\NoContentResponse;
use Core\Security\AccessGroup\Domain\Model\AccessGroup;
use Centreon\Domain\Common\Assertion\AssertionException;
use Core\Security\ProviderConfiguration\Domain\Model\Endpoint;
use Core\Security\ProviderConfiguration\Domain\Model\Provider;
use Core\Security\ProviderConfiguration\Domain\Model\Configuration;
use Centreon\Domain\Repository\Interfaces\DataStorageEngineInterface;
use Core\Security\ProviderConfiguration\Domain\Model\GroupsMapping;
use Core\Contact\Application\Repository\ReadContactGroupRepositoryInterface;
use Core\Security\ProviderConfiguration\Domain\Model\AuthorizationRule;
use Core\Contact\Application\Repository\ReadContactTemplateRepositoryInterface;
use Core\Security\ProviderConfiguration\Domain\SAML\Model\CustomConfiguration;
use Core\Security\ProviderConfiguration\Domain\Model\ContactGroupRelation;
use Core\Security\ProviderConfiguration\Domain\Model\AuthenticationConditions;
use Core\Security\AccessGroup\Application\Repository\ReadAccessGroupRepositoryInterface;
use Core\Security\Authentication\Application\Provider\ProviderAuthenticationFactoryInterface;
use Core\Security\ProviderConfiguration\Domain\Exception\ConfigurationException;
use Core\Security\ProviderConfiguration\Domain\Model\ACLConditions;
use Core\Security\ProviderConfiguration\Application\SAML\Repository\WriteSAMLConfigurationRepositoryInterface;

class UpdateSAMLConfiguration
{
    use LoggerTrait;

    /**
     * @param WriteSAMLConfigurationRepositoryInterface $repository
     * @param ReadContactTemplateRepositoryInterface $contactTemplateRepository
     * @param ReadContactGroupRepositoryInterface $contactGroupRepository
     * @param ReadAccessGroupRepositoryInterface $accessGroupRepository
     * @param DataStorageEngineInterface $dataStorageEngine
     * @param ProviderAuthenticationFactoryInterface $providerAuthenticationFactory
     */
    public function __construct(
        private WriteSAMLConfigurationRepositoryInterface $repository,
        private ReadContactTemplateRepositoryInterface $contactTemplateRepository,
        private ReadContactGroupRepositoryInterface $contactGroupRepository,
        private ReadAccessGroupRepositoryInterface $accessGroupRepository,
        private DataStorageEngineInterface $dataStorageEngine,
        private ProviderAuthenticationFactoryInterface $providerAuthenticationFactory
    ) {
    }

    /**
     * @param UpdateSAMLConfigurationPresenterInterface $presenter
     * @param UpdateSAMLConfigurationRequest $request
     */
    public function __invoke(
        UpdateSAMLConfigurationPresenterInterface $presenter,
        UpdateSAMLConfigurationRequest $request
    ): void {

        $this->info('Updating SAML Provider');
        try {
            $provider = $this->providerAuthenticationFactory->create(Provider::SAML);
            $configuration = $provider->getConfiguration();
            $configuration->update($request->isActive, $request->isForced);
            $requestArray = $request->toArray();
            $requestArray['contact_template'] = $request->contactTemplate &&
            array_key_exists('id', $request->contactTemplate) !== null
                ? $this->getContactTemplateOrFail($request->contactTemplate)
                : null;
            $requestArray['roles_mapping'] = $this->createAclConditions($request->rolesMapping);
            $requestArray["authentication_conditions"] = $this->createAuthenticationConditions(
                $request->authenticationConditions
            );
            $requestArray["groups_mapping"] = $this->createGroupsMapping($request->groupsMapping);
            $requestArray["is_active"] = $request->isActive;
            $configuration->setCustomConfiguration(new CustomConfiguration($requestArray));
            $this->updateConfiguration($configuration);
        } catch (AssertionException | AssertionFailedException | ConfigurationException $ex) {
            $this->error(
                'Unable to create SAML Provider because one or several parameters are invalid',
                ['trace' => $ex->getTraceAsString()]
            );
            $presenter->setResponseStatus(new ErrorResponse($ex->getMessage()));
            return;
        } catch (\Throwable $ex) {
            $this->error('Error during SAML Provider Update', ['trace' => $ex->getTraceAsString()]);
            $presenter->setResponseStatus(new UpdateSAMLConfigurationErrorResponse($ex->getMessage()));
            return;
        }

        $presenter->setResponseStatus(new NoContentResponse());
    }

    /**
     * Get Contact template or throw an Exception
     *
     * @param array{id: int, name: string}|null $contactTemplateFromRequest
     * @return ContactTemplate|null
     * @throws \Throwable|ConfigurationException
     */
    private function getContactTemplateOrFail(?array $contactTemplateFromRequest): ?ContactTemplate
    {
        if ($contactTemplateFromRequest === null) {
            return null;
        }
        if (($contactTemplate = $this->contactTemplateRepository->find($contactTemplateFromRequest["id"])) === null) {
            throw ConfigurationException::contactTemplateNotFound(
                $contactTemplateFromRequest["name"]
            );
        }

        return $contactTemplate;
    }

    /**
     * Create Authorization Rules
     *
     * @param array<array{claim_value: string, access_group_id: int, priority: int}> $authorizationRulesFromRequest
     * @return AuthorizationRule[]
     * @throws \Throwable
     */
    private function createAuthorizationRules(array $authorizationRulesFromRequest): array
    {
        $this->info('Creating Authorization Rules');
        $accessGroupIds = $this->getAccessGroupIds($authorizationRulesFromRequest);

        if (empty($accessGroupIds)) {
            return [];
        }

        $foundAccessGroups = $this->accessGroupRepository->findByIds($accessGroupIds);

        $this->logNonExistentAccessGroupsIds($accessGroupIds, $foundAccessGroups);

        $authorizationRules = [];
        foreach ($authorizationRulesFromRequest as $authorizationRule) {
            $accessGroup = $this->findAccessGroupFromFoundAccessGroups(
                $authorizationRule["access_group_id"],
                $foundAccessGroups
            );
            if ($accessGroup !== null) {
                $authorizationRules[] = new AuthorizationRule(
                    $authorizationRule["claim_value"],
                    $accessGroup,
                    $authorizationRule["priority"]
                );
            }
        }

        return $authorizationRules;
    }

    /**
     * @param array<string,bool|string|string[]|array<array{claim_value: string, access_group_id: int}>> $rolesMapping
     * @return ACLConditions
     * @throws \Throwable
     */
    private function createAclConditions(array $rolesMapping): ACLConditions
    {
        $rules = $this->createAuthorizationRules($rolesMapping['relations']);

        return new ACLConditions(
            $rolesMapping['is_enabled'],
            $rolesMapping['apply_only_first_role'],
            $rolesMapping['attribute_path'],
            null,
            $rules
        );
    }


    /**
     * Add log for all the non existent access groups
     *
     * @param int[] $accessGroupIdsFromRequest
     * @param AccessGroup[] $foundAccessGroups
     */
    private function logNonExistentAccessGroupsIds(array $accessGroupIdsFromRequest, array $foundAccessGroups): void
    {
        $foundAccessGroupsId = [];
        foreach ($foundAccessGroups as $foundAccessGroup) {
            $foundAccessGroupsId[] = $foundAccessGroup->getId();
        }
        $nonExistentAccessGroupsIds = array_diff($accessGroupIdsFromRequest, $foundAccessGroupsId);
        $this->error("Access Groups not found", [
            "access_group_ids" => implode(', ', $nonExistentAccessGroupsIds)
        ]);
    }

    /**
     * Compare the access group id sent in request with Access groups from database
     * Return the access group that have the same id than the access group id from the request
     *
     * @param int $accessGroupIdFromRequest Access group id sent in the request
     * @param AccessGroup[] $foundAccessGroups Access groups found in data storage
     * @return AccessGroup|null
     */
    private function findAccessGroupFromFoundAccessGroups(
        int $accessGroupIdFromRequest,
        array $foundAccessGroups
    ): ?AccessGroup {
        foreach ($foundAccessGroups as $foundAccessGroup) {
            if ($accessGroupIdFromRequest === $foundAccessGroup->getId()) {
                return $foundAccessGroup;
            }
        }
        return null;
    }

    /**
     * Return all unique access group id from request
     *
     * @param array<array{claim_value: string, access_group_id: int}> $authorizationRulesFromRequest
     * @return int[]
     */
    private function getAccessGroupIds(array $authorizationRulesFromRequest): array
    {
        $accessGroupIds = [];
        foreach ($authorizationRulesFromRequest as $authorizationRules) {
            $accessGroupIds[] = $authorizationRules["access_group_id"];
        }

        return array_unique($accessGroupIds);
    }

    /**
     * Update SAML Provider
     *
     * @param Configuration $configuration
     * @throws \Throwable
     */
    private function updateConfiguration(Configuration $configuration): void
    {
        $isAlreadyInTransaction = $this->dataStorageEngine->isAlreadyinTransaction();
        try {
            if (!$isAlreadyInTransaction) {
                $this->dataStorageEngine->startTransaction();
            }
            $this->info('Updating SAML Provider');
            $this->repository->updateConfiguration($configuration);
            if (!$isAlreadyInTransaction) {
                $this->dataStorageEngine->commitTransaction();
            }
        } catch (\Throwable $ex) {
            if (!$isAlreadyInTransaction) {
                $this->dataStorageEngine->rollbackTransaction();
                throw $ex;
            }
        }
    }

    /**
     * Create Authentication Condition from request data.
     *
     * @param array{
     *  "is_enabled": bool,
     *  "attribute_path": string,
     *  "authorized_values": string[],
     *  "trusted_client_addresses": string[],
     *  "blacklist_client_addresses": string[],
     *  "endpoint": array{
     *      "type": string,
     *      "custom_endpoint":string|null
     *  }
     * } $authenticationConditionsParameters
     * @return AuthenticationConditions
     * @throws ConfigurationException
     */
    private function createAuthenticationConditions(array $authenticationConditionsParameters): AuthenticationConditions
    {
        return new AuthenticationConditions(
            $authenticationConditionsParameters["is_enabled"],
            $authenticationConditionsParameters["attribute_path"],
          null,
            $authenticationConditionsParameters["authorized_values"],
        );
    }

    /**
     * Create Groups Mapping from data send to the request
     *
     * @param array{
     *  "is_enabled": bool,
     *  "attribute_path": string,
     *  "endpoint": array{
     *      "type": string,
     *      "custom_endpoint":string|null
     *  },
     *  "relations":array<array{
     *      "group_value": string,
     *      "contact_group_id": int
     *  }>
     * } $groupsMappingParameters
     *
     * @return GroupsMapping
     * @throws ConfigurationException
     * @throws \Throwable
     */
    private function createGroupsMapping(array $groupsMappingParameters): GroupsMapping
    {
        $contactGroupIds = $this->getContactGroupIds($groupsMappingParameters["relations"]);
        $foundContactGroups = $this->contactGroupRepository->findByIds($contactGroupIds);
        $this->logNonExistentContactGroupsIds($contactGroupIds, $foundContactGroups);
        $contactGroupRelations = [];
        foreach ($groupsMappingParameters["relations"] as $contactGroupRelation) {
            $contactGroup = $this->findContactGroupFromFoundcontactGroups(
                $contactGroupRelation["contact_group_id"],
                $foundContactGroups
            );
            if ($contactGroup !== null) {
                $contactGroupRelations[] = new ContactGroupRelation(
                    $contactGroupRelation["group_value"],
                    $contactGroup
                );
            }
        }

        return new GroupsMapping(
            $groupsMappingParameters["is_enabled"],
            $groupsMappingParameters["attribute_path"],
           null,
            $contactGroupRelations
        );
    }

    /**
     * @param array<array{"group_value": string, "contact_group_id": int}> $contactGroupParameters
     * @return int[]
     */
    private function getContactGroupIds(array $contactGroupParameters): array
    {
        $contactGroupIds = [];
        foreach ($contactGroupParameters as $groupsMapping) {
            $contactGroupIds[] = $groupsMapping["contact_group_id"];
        }

        return array_unique($contactGroupIds);
    }

    /**
     * Add log for all the non existent contact groups
     *
     * @param int[] $contactGroupIds
     * @param ContactGroup[] $foundContactGroups
     */
    private function logNonExistentContactGroupsIds(array $contactGroupIds, array $foundContactGroups): void
    {
        $foundContactGroupsId = [];
        foreach ($foundContactGroups as $foundAccessGroup) {
            $foundContactGroupsId[] = $foundAccessGroup->getId();
        }
        $nonExistentAccessGroupsIds = array_diff($contactGroupIds, $foundContactGroupsId);
        $this->error("Access groups not found", [
            "access_group_ids" => implode(', ', $nonExistentAccessGroupsIds)
        ]);
    }

    /**
     * Compare the contact group id sent in request with contact groups from database
     * Return the contact group that have the same id than the contact group id from the request
     *
     * @param int $contactGroupIdFromRequest contact group id sent in the request
     * @param ContactGroup[] $foundContactGroups contact groups found in data storage
     * @return ContactGroup|null
     */
    private function findContactGroupFromFoundcontactGroups(
        int $contactGroupIdFromRequest,
        array $foundContactGroups
    ): ?ContactGroup {
        foreach ($foundContactGroups as $foundContactGroup) {
            if ($contactGroupIdFromRequest === $foundContactGroup->getId()) {
                return $foundContactGroup;
            }
        }

        return null;
    }
}

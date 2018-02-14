<?php
/**
 * Copyright 2017 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\AdsApi\Examples\Dfp\v201708\CustomTargetingService;

require __DIR__ . '/../../../../vendor/autoload.php';

use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google\AdsApi\Dfp\DfpServices;
use Google\AdsApi\Dfp\DfpSession;
use Google\AdsApi\Dfp\DfpSessionBuilder;
use Google\AdsApi\Dfp\Util\v201708\StatementBuilder;
use Google\AdsApi\Dfp\v201708\CustomTargetingService;
use Google\AdsApi\Dfp\v201708\DeleteCustomTargetingValues as DeleteCustomTargetingValuesAction;

/**
 * Deletes custom targeting values.
 *
 * This example is meant to be run from a command line (not as a webpage) and
 * requires that you've setup an `adsapi_php.ini` file in your home directory
 * with your API credentials and settings. See `README.md` for more info.
 */
class DeleteCustomTargetingValues
{

    const CUSTOM_TARGETING_KEY_ID = 'INSERT_CUSTOM_TARGETING_KEY_ID_HERE';
    const CUSTOM_TARGETING_VALUE_ID = 'INSERT_CUSTOM_TARGETING_VALUE_ID_HERE';

    public static function runExample(
        DfpServices $dfpServices,
        DfpSession $session,
        $customTargetingKeyId,
        $customTargetingValueId
    ) {
        $customTargetingService = $dfpServices->get($session, CustomTargetingService::class);

        // Create a statement to select the custom targeting values to delete.
        $pageSize = StatementBuilder::SUGGESTED_PAGE_LIMIT;
        $statementBuilder = (new StatementBuilder())->where(
            'customTargetingKeyId = :customTargetingKeyId AND id = :id'
        )
            ->orderBy('id ASC')
            ->limit($pageSize)
            ->withBindVariableValue(
                'customTargetingKeyId',
                $customTargetingKeyId
            )
            ->withBindVariableValue('id', $customTargetingValueId);

        // Retrieve a small amount of custom targeting values at a time, paging
        // through until all custom targeting values have been retrieved.
        $totalResultSetSize = 0;
        do {
            $page = $customTargetingService->getCustomTargetingValuesByStatement(
                $statementBuilder->toStatement()
            );

            // Print out some information for the custom targeting values to be
            // deleted.
            if ($page->getResults() !== null) {
                $totalResultSetSize = $page->getTotalResultSetSize();
                $i = $page->getStartIndex();
                foreach ($page->getResults() as $customTargetingValue) {
                    printf(
                        "%d) Custom targeting value with ID %d, name '%s', "
                        . "and display name '%s' will be deleted.\n",
                        $i++,
                        $customTargetingValue->getId(),
                        $customTargetingValue->getName(),
                        $customTargetingValue->getDisplayName()
                    );
                }
            }

            $statementBuilder->increaseOffsetBy($pageSize);
        } while ($statementBuilder->getOffset() < $totalResultSetSize);

        printf(
            "Total number of custom targeting values to be deleted: %d\n",
            $totalResultSetSize
        );

        if ($totalResultSetSize > 0) {
            // Remove limit and offset from statement so we can reuse the statement.
            $statementBuilder->removeLimitAndOffset();

            // Create and perform action.
            $action = new DeleteCustomTargetingValuesAction();
            $result = $customTargetingService->performCustomTargetingValueAction(
                $action,
                $statementBuilder->toStatement()
            );

            if ($result !== null && $result->getNumChanges() > 0) {
                printf(
                    "Number of custom targeting values deleted: %d\n",
                    $result->getNumChanges()
                );
            } else {
                printf("No custom targeting values were deleted.\n");
            }
        }
    }

    public static function main()
    {
        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()
            ->build();

        // Construct an API session configured from a properties file and the
        // OAuth2 credentials above.
        $session = (new DfpSessionBuilder())->fromFile()
            ->withOAuth2Credential($oAuth2Credential)
            ->build();

        self::runExample(
            new DfpServices(),
            $session,
            intval(self::CUSTOM_TARGETING_KEY_ID),
            intval(self::CUSTOM_TARGETING_VALUE_ID)
        );
    }
}

DeleteCustomTargetingValues::main();

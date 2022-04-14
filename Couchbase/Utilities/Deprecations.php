<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
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

declare(strict_types=1);

namespace Couchbase\Utilities;

use Couchbase\AnalyticsScanConsistency;
use Couchbase\DurabilityLevel;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\QueryProfile;
use Couchbase\QueryScanConsistency;
use Couchbase\StoreSemantics;
use Couchbase\ViewConsistency;
use Couchbase\ViewOrdering;

class Deprecations
{
    /**
     * Converts integer "enum" value of query scan consistency into string representation
     *
     * @throws InvalidArgumentException
     * @deprecated this utility function should not be used directly
     *
     * @since 4.0.0
     */
    public static function convertDeprecatedQueryScanConsistency(string $method, int $level): string
    {
        trigger_error(
            'Method ' . $method . ' is deprecated with integer parameter, use string parameter instead',
            E_USER_DEPRECATED
        );

        switch ($level) {
            case 0:
                return QueryScanConsistency::NOT_BOUNDED;
            case 1:
                return QueryScanConsistency::REQUEST_PLUS;
            default:
                throw new InvalidArgumentException(
                    sprintf("Integer value for query scan consistency must be in [0, 1] interval. Given: %d", $level)
                );
        }
    }

    /**
     * Converts integer "enum" value of analytics scan consistency into string representation
     *
     * @throws InvalidArgumentException
     * @deprecated this utility function should not be used directly
     *
     * @since 4.0.0
     */
    public static function convertDeprecatedAnalyticsScanConsistency(string $method, int $level): string
    {
        trigger_error(
            'Method ' . $method . ' is deprecated with integer parameter, use string parameter instead',
            E_USER_DEPRECATED
        );

        switch ($level) {
            case 0:
                return AnalyticsScanConsistency::NOT_BOUNDED;
            case 1:
                return AnalyticsScanConsistency::REQUEST_PLUS;
            default:
                throw new InvalidArgumentException(
                    sprintf("Integer value for analytics scan consistency must be in [0, 1] interval. Given: %d", $level)
                );
        }
    }

    /**
     * Converts integer "enum" value of analytics scan consistency into string representation
     *
     * @throws InvalidArgumentException
     * @deprecated this utility function should not be used directly
     *
     * @since 4.0.0
     */
    public static function convertDeprecatedViewConsistency(string $method, int $level): string
    {
        trigger_error(
            'Method ' . $method . ' is deprecated with integer parameter, use string parameter instead',
            E_USER_DEPRECATED
        );

        switch ($level) {
            case 1:
                return ViewConsistency::NOT_BOUNDED;
            case 2:
                return ViewConsistency::REQUEST_PLUS;
            case 3:
                return ViewConsistency::UPDATE_AFTER;
            default:
                throw new InvalidArgumentException(
                    sprintf("Integer value for analytics scan consistency must be in [1, 3] interval. Given: %d", $level)
                );
        }
    }

    /**
     * Converts integer "enum" value of durability level into string representation
     *
     * @throws InvalidArgumentException
     * @deprecated this utility function should not be used directly
     *
     * @since 4.0.0
     */
    public static function convertDeprecatedDurabilityLevel(string $method, int $level): string
    {
        trigger_error(
            'Method ' . $method . ' is deprecated with integer parameter, use string parameter instead',
            E_USER_DEPRECATED
        );

        switch ($level) {
            case 0:
                return DurabilityLevel::NONE;
            case 1:
                return DurabilityLevel::MAJORITY;
            case 2:
                return DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE;
            case 3:
                return DurabilityLevel::PERSIST_TO_MAJORITY;
            default:
                throw new InvalidArgumentException(
                    sprintf("Integer value for query scan consistency must be in [0, 3] interval. Given: %d", $level)
                );
        }
    }

    /**
     * Converts integer "enum" value of store semantics into string representation
     *
     * @throws InvalidArgumentException
     * @deprecated this utility function should not be used directly
     *
     * @since 4.0.0
     */
    public static function convertDeprecatedStoreSemantics(string $method, int $semantics): string
    {
        trigger_error(
            'Method ' . $method . ' is deprecated with integer parameter, use string parameter instead',
            E_USER_DEPRECATED
        );

        switch ($semantics) {
            case 0:
                return StoreSemantics::REPLACE;
            case 1:
                return StoreSemantics::UPSERT;
            case 2:
                return StoreSemantics::INSERT;
            default:
                throw new InvalidArgumentException(
                    sprintf("Integer value for store semantics must be in [0, 2] interval. Given: %d", $semantics)
                );
        }
    }

    /**
     * Converts integer "enum" value of query profile into string representation
     *
     * @throws InvalidArgumentException
     * @deprecated this utility function should not be used directly
     *
     * @since 4.0.0
     */
    public static function convertDeprecatedQueryProfile(string $method, int $profile): string
    {
        trigger_error(
            'Method ' . $method . ' is deprecated with integer parameter, use string parameter instead',
            E_USER_DEPRECATED
        );

        switch ($profile) {
            case 1:
                return QueryProfile::OFF;
            case 2:
                return QueryProfile::PHASES;
            case 3:
                return QueryProfile::TIMINGS;
            default:
                throw new InvalidArgumentException(
                    sprintf("Integer value for query profile must be in [1, 3] interval. Given: %d", $profile)
                );
        }
    }

    /**
     * Converts integer "enum" value of view ordering into string representation
     *
     * @throws InvalidArgumentException
     * @deprecated this utility function should not be used directly
     *
     * @since 4.0.0
     */
    public static function convertDeprecatedViewOrder(string $method, int $order): string
    {
        trigger_error(
            'Method ' . $method . ' is deprecated with integer parameter, use string parameter instead',
            E_USER_DEPRECATED
        );

        switch ($order) {
            case 1:
                return ViewOrdering::ASCENDING;
            case 2:
                return ViewOrdering::DESCENDING;
            default:
                throw new InvalidArgumentException(
                    sprintf("Integer value for view order must be in [0, 1] interval. Given: %d", $order)
                );
        }
    }
}

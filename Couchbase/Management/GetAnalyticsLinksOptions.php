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

namespace Couchbase\Management;

class GetAnalyticsLinksOptions
{
    public function timeout(int $arg): DropAnalyticsLinkOptions
    {
    }

    /**
     * @param string $tupe restricts the results to the given link type.
     *
     * @see AnalyticsLinkType::COUCHBASE
     * @see AnalyticsLinkType::S3
     * @see AnalyticsLinkType::AZURE_BLOB
     */
    public function linkType(string $type): DropAnalyticsLinkOptions
    {
    }

    /**
     * @param string $dataverse restricts the results to a given dataverse, can be given in the form of "namepart" or
     *     "namepart1/namepart2".
     */
    public function dataverse(string $dataverse): DropAnalyticsLinkOptions
    {
    }

    /**
     * @param string $name restricts the results to the link with the specified name. If set then dataverse must also
     *     be set.
     */
    public function name(string $name): DropAnalyticsLinkOptions
    {
    }
}

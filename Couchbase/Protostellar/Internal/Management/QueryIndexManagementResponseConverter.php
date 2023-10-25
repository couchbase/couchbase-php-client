<?php

/*
 * Copyright 2022-Present Couchbase, Inc.
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

namespace Couchbase\Protostellar\Internal\Management;

use Couchbase\Exception\DecodingFailureException;
use Couchbase\Management\QueryIndex;
use Couchbase\Management\QueryIndexType;
use Couchbase\Protostellar\Generated\Admin\Query\V1\GetAllIndexesResponse;
use Couchbase\Protostellar\Generated\Admin\Query\V1\IndexState;
use Couchbase\Protostellar\Generated\Admin\Query\V1\IndexType;
use Couchbase\Protostellar\Internal\SharedUtils;

class QueryIndexManagementResponseConverter
{
    /**
     * @throws DecodingFailureException
     */
    public static function convertGetAllIndexesResult(GetAllIndexesResponse $response): array
    {
        $indexes = [];
        foreach ($response->getIndexes() as $index) {
            $indexArr = [];
            $indexArr["bucketName"] = $index->getBucketName();
            $indexArr["name"] = $index->getName();
            $indexArr["isPrimary"] = $index->getIsPrimary();
            $indexArr["type"] = self::convertIndexType($index->getType());
            $indexArr["state"] = self::convertIndexState($index->getState());
            $indexArr["indexKey"] = SharedUtils::toArray($index->getFields());
            if ($index->getScopeName() != "_default") {
                $indexArr["scopeName"] = $index->getScopeName();
            }
            if ($index->getCollectionName() != "_default") {
                $indexArr["collectionName"] = $index->getCollectionName();
            }
            if ($index->hasCondition()) {
                $indexArr["condition"] = $index->getCondition();
            }
            if ($index->hasPartition()) {
                $indexArr["partition"] = $index->getPartition();
            }
            $indexes[] = new QueryIndex($indexArr);
        }
        return $indexes;
    }

    /**
     * @throws DecodingFailureException
     */
    private static function convertIndexType(int $indexType): string
    {
        switch ($indexType) {
            case IndexType::INDEX_TYPE_VIEW:
                return QueryIndexType::VIEW;
            case IndexType::INDEX_TYPE_GSI:
                return QueryIndexType::GSI;
            default:
                throw new DecodingFailureException("Unknown query index type received from server");
        }
    }

    /**
     * @throws DecodingFailureException
     */
    private static function convertIndexState(int $indexState): string
    {
        switch ($indexState) {
            case IndexState::INDEX_STATE_DEFERRED:
                return "deferred";
            case IndexState::INDEX_STATE_BUILDING:
                return "building";
            case IndexState::INDEX_STATE_PENDING:
                return "pending";
            case IndexState::INDEX_STATE_ONLINE:
                return "online";
            case IndexState::INDEX_STATE_OFFLINE:
                return "offline";
            case IndexState::INDEX_STATE_ABRIDGED:
                return "abridged";
            case IndexState::INDEX_STATE_SCHEDULED:
                return "scheduled for creation";
            default:
                throw new DecodingFailureException("Unknown query index state received from server");
        }
    }
}

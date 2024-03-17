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

use JsonSerializable;
use stdClass;

class SearchIndex implements JsonSerializable
{
    private string $name;
    private string $sourceName;
    private string $type = "fulltext-index";
    private string $sourceType = "couchbase";

    private ?string $uuid = null;
    private ?string $sourceUuid = null;
    private ?stdClass $params = null;
    private ?stdClass $sourceParams = null;
    private ?stdClass $planParams = null;

    /**
     * @param string $indexName the name of the index
     * @param string $sourceName the name of the bucket where you want to create the index
     *
     * @since 4.1.5
     */
    public function __construct(string $indexName, string $sourceName)
    {
        $this->name = $indexName;
        $this->sourceName = $sourceName;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $indexName the name of the index
     * @param string $sourceName the name of the bucket where you want to create the index
     * @return SearchIndex
     *
     * @since 4.1.5
     */
    public static function build(string $indexName, string $sourceName): SearchIndex
    {
        return new SearchIndex($indexName, $sourceName);
    }

    /**
     * Get the name of the index
     *
     * @return string
     *
     * @since 4.1.5
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the bucket name on index
     *
     * @return string
     *
     * @since 4.1.5
     */
    public function sourceName(): string
    {
        return $this->sourceName;
    }

    /**
     * Get the type of index. Defaults to 'fulltext-index'
     *
     * @return string
     *
     * @since 4.1.5
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Get the source type of index. Defaults to 'couchbase'
     *
     * @return string
     *
     * @since 4.1.5
     */
    public function sourceType(): string
    {
        return $this->sourceType;
    }

    /**
     * Get the UUID of index
     *
     * @return string|null
     *
     * @since 4.1.5
     */
    public function uuid(): ?string
    {
        return $this->uuid;
    }

    /**
     *  Get the UUID of the bucket on index
     *
     * @return string|null
     *
     * @since 4.1.5
     */
    public function sourceUuid(): ?string
    {
        return $this->sourceUuid;
    }

    /**
     * Get the params of index
     *
     * @return stdClass|null
     *
     * @since 4.1.5
     */
    public function params(): ?stdClass
    {
        return $this->params;
    }

    /**
     * Get the source params of index
     *
     * @return stdClass|null
     *
     * @since 4.1.5
     */
    public function sourceParams(): ?stdClass
    {
        return $this->sourceParams;
    }

    /**
     * Get the plan params of index
     *
     * @return stdClass|null
     *
     * @since 4.1.5
     */
    public function planParams(): ?stdClass
    {
        return $this->planParams;
    }

    /**
     * Sets the name of the index
     *
     * @param string $name
     * @return $this
     *
     * @since 4.1.5
     */
    public function setName(string $name): SearchIndex
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets bucket name on index
     *
     * @param string $sourceName
     * @return $this
     *
     * @since 4.1.5
     */
    public function setSourceName(string $sourceName): SearchIndex
    {
        $this->sourceName = $sourceName;
        return $this;
    }


    /**
     * Sets the type of the index. Defaults to 'fulltext-index'
     *
     * @param string $type
     * @return $this
     *
     * @since 4.1.5
     */
    public function setType(string $type): SearchIndex
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Sets source type on index. Defaults to 'couchbase'
     *
     * @param string $type
     * @return $this
     *
     * @since 4.1.5
     */
    public function setSourceType(string $type): SearchIndex
    {
        $this->sourceType = $type;
        return $this;
    }

    /**
     * Sets the UUID of the index.
     *
     * @param string $uuid
     * @return $this
     *
     * @since 4.1.5
     */
    public function setUuid(string $uuid): SearchIndex
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * Sets Bucket UUID on index
     *
     * @param string $uuid
     * @return $this
     *
     * @since 4.1.5
     */
    public function setSourceUuid(string $uuid): SearchIndex
    {
        $this->sourceUuid = $uuid;
        return $this;
    }

    /**
     * Sets the params on index. Either as JSON string or decoded into array/stdClass
     *
     * @param string|array|stdClass $params
     * @return $this
     *
     * @since 4.1.5
     */
    public function setParams(string|array|stdClass $params): SearchIndex
    {
        $type = gettype($params);
        if ($type == "string") {
            $this->params = json_decode($params);
        } elseif ($type == "array") {
            $this->params = self::toObject($params);
        } else {
            $this->params = $params;
        }
        return $this;
    }

    /**
     * Sets source params of index. Either as JSON string or decoded into array/stdClass
     *
     * @param string|array|stdClass $sourceParams
     * @return $this
     *
     * @since 4.1.5
     */
    public function setSourceParams(string|array|stdClass $sourceParams): SearchIndex
    {
        $type = gettype($sourceParams);
        if ($type == "string") {
            $this->sourceParams = json_decode($sourceParams);
        } elseif ($type == "array") {
            $this->sourceParams = self::toObject($sourceParams);
        } else {
            $this->sourceParams = $sourceParams;
        }
        return $this;
    }

    /**
     * Sets plan params of index. Either as JSON string or decoded as array/stdClass.
     *
     * @param string|array|stdClass $planParams
     * @return $this
     *
     * @since 4.1.5
     */
    public function setPlanParams(string|array|stdClass $planParams): SearchIndex
    {
        $type = gettype($planParams);
        if ($type == "string") {
            $this->planParams = json_decode($planParams);
        } elseif ($type == "array") {
            $this->planParams = self::toObject($planParams);
        } else {
            $this->planParams = $planParams;
        }
        return $this;
    }

    /**
     * @return array
     *
     * @internal
     * @since 4.1.5
     */
    public function jsonSerialize(): array
    {
        $output = [];
        $output["type"] = $this->type;
        $output["name"] = $this->name;
        if ($this->uuid != null) {
            $output["uuid"] = $this->uuid;
        }
        $output["sourceType"] = $this->sourceType;
        $output["sourceName"] = $this->sourceName;
        if ($this->sourceUuid != null) {
            $output["sourceUUID"] = $this->sourceUuid;
        }
        if ($this->planParams != null) {
            $output["planParams"] = $this->planParams;
        }
        if ($this->params != null) {
            $output["params"] = $this->params;
        }
        if ($this->sourceParams != null) {
            $output["sourceParams"] = $this->sourceParams;
        }
        return $output;
    }

    /**
     * @param array|stdClass $searchIndex
     * @return SearchIndex
     *
     * @internal
     * @since 4.1.5
     */
    public static function import(array|stdClass $searchIndex): SearchIndex
    {
        $finalIndex = self::standardiseImportedIndex($searchIndex);

        $index = new SearchIndex($finalIndex->name, $finalIndex->source_name);
        if (isset($finalIndex->uuid)) {
            $index->setUuid($finalIndex->uuid);
        }
        if (isset($finalIndex->type)) {
            $index->setType($finalIndex->type);
        }
        if (isset($finalIndex->params_json)) {
            $index->setParams($finalIndex->params_json);
        }
        if (isset($finalIndex->source_uuid)) {
            $index->setSourceUuid($finalIndex->source_uuid);
        }
        if (isset($finalIndex->source_type)) {
            $index->setSourceType($finalIndex->source_type);
        }
        if (isset($finalIndex->source_params_json)) {
            $index->setSourceParams($finalIndex->source_params_json);
        }
        if (isset($finalIndex->plan_params_json)) {
            $index->setPlanParams($finalIndex->plan_params_json);
        }
        return $index;
    }

    /**
     * @param SearchIndex $index
     * @return array
     *
     * @internal
     * @since 4.1.5
     */
    public static function export(SearchIndex $index): array
    {
        $exported = [
            'name' => $index->name,
            'type' => $index->type,
            'sourceName' => $index->sourceName,
            'sourceType' => $index->sourceType
        ];
        if ($index->uuid != null) {
            $exported['uuid'] = $index->uuid;
        }
        if ($index->sourceUuid != null) {
            $exported['sourceUuid'] = $index->sourceUuid;
        }
        if ($index->planParams != null) {
            $exported['planParams'] = json_encode($index->planParams);
        }
        if ($index->params != null) {
            $exported['params'] = json_encode($index->params);
        }
        if ($index->sourceParams != null) {
            $exported['sourceParams'] = json_encode($index->sourceParams);
        }
        return $exported;
    }

    /**
     * Standardises imported index to match with what is returned by the core, converts to stdClass as it is avoids ambiguity with array/object json conversions
     * @param stdClass|array $searchIndex
     * @return stdClass
     *
     * @since 4.1.5
     */
    private static function standardiseImportedIndex(stdClass|array $searchIndex): stdClass
    {
        $replacements = [
            "name" => "name",
            "uuid" => "uuid",
            "type" => "type",
            "sourceType" => "source_type",
            "source_type" => "source_type",
            "sourceName" => "source_name",
            "source_name" => "source_name",
            "sourceParams" => "source_params_json",
            "source_params_json" => "source_params_json",
            "sourceUUID" => "source_uuid",
            "source_uuid" => "source_uuid",
            "planParams" => "plan_params_json",
            "plan_params_json" => "plan_params_json",
            "params" => "params_json",
            "params_json" => "params_json"
        ];

        if (gettype($searchIndex) == "array") {
            $searchIndex = self::toObject($searchIndex);
        }

        $finalIndex = new stdClass();
        foreach ($searchIndex as $key => $value) {
            if (isset($replacements[$key])) {
                $finalIndex->{$replacements[$key]} = $value;
            }
        }
        return $finalIndex;
    }

    /**
     * Converts array to stdObject, which makes it easier to work with json_encode
     *
     * @param array $arr
     * @return mixed
     *
     * @since 4.1.5
     */
    private static function toObject(array $arr): stdClass
    {
        $json = json_encode($arr);
        $json = str_replace("[]", "{}", $json); //Edge case - Empty arrays decoded as an array instead of a stdClass, which encodes back incorrectly as a json array
        return json_decode($json);
    }
}

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

namespace Couchbase;

use JsonSerializable;

class ViewOptions
{
    private ?int $timeoutMilliseconds = null;
//    private ?int $includeDocuments = null;
    private ?jsonSerializable $key = null;
    private ?array $keys = null;
    private ?int $limit = null;
    private ?int $skip = null;
    private ?int $scanConsistency = null;
    private ?int $order = null;
    private ?bool $reduce = null;
    private ?bool $group = null;
    private ?int $groupLevel = null;
    private ?array $raw = null;
    private ?jsonSerializable $startKey = null;
    private ?jsonSerializable $endKey = null;
    private ?string $startKeyDocId = null;
    private ?string $endKeyDocId = null;
    private ?bool $inclusiveEnd = null;
    private ?int $onError = null;
    private ?bool $debug = null;
    private ?int $namespace = null;

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     * @return ViewOptions
     */
    public function timeout(int $milliseconds): ViewOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

//    /**
//     * Whether to include document bodies in .
//     *
//     * @param bool $include the operation timeout to apply
//     * @return ViewOptions
//     */
//    public function includeDocuments(bool $include): ViewOptions
//    {
//    }

    /**
     * Sets the specific key to fetch from the index.
     *
     * @param jsonSerializable $key the key to fetch from the index.
     * @return ViewOptions
     */
    public function key(jsonSerializable $key): ViewOptions
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Sets the specific set of keys to fetch from the index.
     *
     * @param array $keys the keys to fetch from the index.
     * @return ViewOptions
     */
    public function keys(array $keys): ViewOptions
    {
        $this->keys = $keys;
        return $this;
    }

    /**
     * Sets the number of documents to limit the result to.
     *
     * @param int $limit the number of documents to limit to.
     * @return ViewOptions
     */
    public function limit(int $limit): ViewOptions
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets the number of documents to skip for the query.
     *
     * @param int $skip the number of documents to skip.
     * @return ViewOptions
     */
    public function skip(int $skip): ViewOptions
    {
        $this->skip = $skip;
        return $this;
    }

    /**
     * Sets the scan consistency.
     *
     * @param int $consistencyLevel the scan consistency level
     * @return QueryOptions
     */
    public function scanConsistency(int $consistencyLevel): ViewOptions
    {
        $this->scanConsistency = $consistencyLevel;
        return $this;
    }

    /**
     * Sets the order of the results.
     *
     * @param int $order the order of the results.
     * @return ViewOptions
     */
    public function order(int $order): ViewOptions
    {
        $this->order  = $order;
        return $this;
    }

    /**
     * Whether to run the reduce function associated with the view index.
     *
     * @param bool $reduce whether to apply the reduce function.
     * @return ViewOptions
     */
    public function reduce(bool $reduce): ViewOptions
    {
        $this->reduce = $reduce;
        return $this;
    }

    /**
     * Whether to enable grouping of results.
     *
     * @param bool $enabled whether to enable grouping of results.
     * @return ViewOptions
     */
    public function group(bool $enabled): ViewOptions
    {
        $this->group = $enabled;
        return $this;
    }

    /**
     * Sets the depth within the key to group results.
     *
     * @param bool $depth the depth within the key to group results.
     * @return ViewOptions
     */
    public function groupLevel(int $depth): ViewOptions
    {
        $this->groupLevel = $depth;
        return $this;
    }

    /**
     * Sets any extra query parameters that the SDK does not provide an option for.
     *
     * @param string $key the name of the parameter
     * @param string $value the value of the parameter
     * @return ViewOptions
     */
    public function raw(string $key, string $value): ViewOptions
    {
        if ($this->raw == null) {
            $this->raw = array();
        }

        $this->raw[$key] = $value;
        return $this;
    }

    /**
     * Sets the key to skip to before beginning to return results.
     *
     * @param jsonSerializable $key the key to skip to before beginning to return results.
     * @return ViewOptions
     */
    public function startKey(jsonSerializable $key): ViewOptions
    {
        $this->startKey = $key;
        return $this;
    }

    /**
     * Sets the key to stop returning results at.
     *
     * @param jsonSerializable $key the key to stop returning results at.
     * @return ViewOptions
     */
    public function endKey(jsonSerializable $key): ViewOptions
    {
        $this->endKey = $key;
        return $this;
    }

    /**
     * Sets the document id to start returning results at within a number of results should startKey have multiple
     * entries within the index.
     *
     * @param string $key the key to use.
     * @return ViewOptions
     */
    public function startKeyDocId(string $key): ViewOptions
    {
        $this->startKeyDocId = $key;
        return $this;
    }

    /**
     * Sets the document id to stop returning results at within a number of results should endKey have multiple
     * entries within the index.
     *
     * @param string $key the key to use.
     * @return ViewOptions
     */
    public function endKeyDocId(string $key): ViewOptions
    {
        $this->endKeyDocId = $key;
        return $this;
    }

    /**
     * Sets whether the endKey/endKeyDocId values should be inclusive or exclusive.
     *
     * @param bool $enabled whether the endKey/endKeyDocId values should be inclusive or exclusive.
     * @return ViewOptions
     */
    public function inclusiveEnd(bool $enabled): ViewOptions
    {
        $this->inclusiveEnd = $enabled;
        return $this;
    }

    /**
     * Sets the behaviour of the query engine should an error occur during the gathering
     * of view index results which would result in only partial results being available.
     *
     * @param int $onError the behaviour mode to apply on error.
     * @return ViewOptions
     */
    public function onError(int $onError): ViewOptions
    {
        $this->onError = $onError;
        return $this;
    }

    /**
     * Sets whether to return debug information as part of the view response.
     *
     * @param bool $enabled whether to return debug information as part of the view response.
     * @return ViewOptions
     */
    public function debug(bool $enabled): ViewOptions
    {
        $this->debug = $enabled;
        return $this;
    }

    /**
     * Sets whether the SDK should prefix the design document name with a "dev_" prefix.
     *
     * @param int $namespace whether the SDK should prefix the design document name with a "dev_" prefix..
     * @return ViewOptions
     */
    public function namespace(int $namespace): ViewOptions
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function export(): array
    {
        $raw = null;
        if ($this->raw != null) {
            foreach ($this->raw as $key => $param) {
                $raw[$key] = $param;
            }
        }
        $keys = null;
        if ($this->keys != null) {
            foreach ($this->keys as  $param) {
                $keys[] = json_encode($param);
            }
        }
        $namespace = DesignDocumentNamespace::PRODUCTION;
        if ($this->namespace != null) {
            $namespace = $this->namespace;
        }
        $key = null;
        if ($this->key != null) {
            $key = json_encode($this->key);
        }
        $startKey = null;
        if ($this->startKey != null) {
            $startKey = json_encode($this->startKey);
        }
        $endKey = null;
        if ($this->endKey != null) {
            $endKey = json_encode($this->endKey);
        }

        return [
            'timeoutMilliseconds' => $this->timeoutMilliseconds,

            'key' => $key,
            'keys' => $keys,
            'limit' => $this->limit,
            'skip' => $this->skip,
            'scanConsistency' => $this->scanConsistency,
            'order' => $this->order,
            'reduce' => $this->reduce,
            'group' => $this->group,
            'groupLevel' => $this->groupLevel,
            'raw' => $raw,
            'startKey' => $startKey,
            'endKey' => $endKey,
            'startKeyDocId' => $this->startKeyDocId,
            'endKeyDocId' => $this->endKeyDocId,
            'inclusiveEnd' => $this->inclusiveEnd,
            'onError' => $this->onError,
            'debug' => $this->debug,
            'namespace' => $namespace,
        ];
    }
}

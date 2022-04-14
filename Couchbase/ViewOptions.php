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

use Couchbase\Utilities\Deprecations;
use JsonSerializable;

class ViewOptions
{
    private ?int $timeoutMilliseconds = null;
//    private ?int $includeDocuments = null;
    private $key = null;
    private ?array $keys = null;
    private ?int $limit = null;
    private ?int $skip = null;
    private ?string $scanConsistency = null;
    private ?string $order = null;
    private ?bool $reduce = null;
    private ?bool $group = null;
    private ?int $groupLevel = null;
    private ?array $raw = null;
    private $startKey = null;
    private $endKey = null;
    private ?string $startKeyDocId = null;
    private ?string $endKeyDocId = null;
    private ?bool $inclusiveEnd = null;
    private ?int $onError = null;
    private ?bool $debug = null;
    private ?int $namespace = null;

    /**
     * Static helper to keep code more readable
     *
     * @return ViewOptions
     * @since 4.0.0
     */
    public static function build(): ViewOptions
    {
        return new ViewOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return ViewOptions
     * @since 4.0.0
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
     * @param mixed $key the key to fetch from the index.
     *
     * @return ViewOptions
     * @since 4.0.0
     */
    public function key($key): ViewOptions
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Sets the specific set of keys to fetch from the index.
     *
     * @param array $keys the keys to fetch from the index.
     *
     * @return ViewOptions
     * @since 4.0.0
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
     *
     * @return ViewOptions
     * @since 4.0.0
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
     *
     * @return ViewOptions
     * @since 4.0.0
     */
    public function skip(int $skip): ViewOptions
    {
        $this->skip = $skip;
        return $this;
    }

    /**
     * Sets the scan consistency.
     *
     * @param string|int $consistencyLevel the scan consistency level
     *
     * @return ViewOptions
     * @throws Exception\InvalidArgumentException
     * @see ViewConsistency
     * @since 4.0.0
     */
    public function scanConsistency($consistencyLevel): ViewOptions
    {
        if (gettype($consistencyLevel) == "integer") {
            $consistencyLevel = Deprecations::convertDeprecatedViewConsistency(__METHOD__, $consistencyLevel);
        }
        $this->scanConsistency = $consistencyLevel;
        return $this;
    }

    /**
     * Sets the order of the results.
     *
     * @param int|string $order the order of the results.
     *
     * @return ViewOptions
     * @since 4.0.0
     */
    public function order($order): ViewOptions
    {
        if (gettype($order) == "integer") {
            $order = Deprecations::convertDeprecatedViewOrder(__METHOD__, $order);
        }
        $this->order = $order;
        return $this;
    }

    /**
     * Whether to run the reduce function associated with the view index.
     *
     * @param bool $reduce whether to apply the reduce function.
     *
     * @return ViewOptions
     * @since 4.0.0
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
     *
     * @return ViewOptions
     * @since 4.0.0
     */
    public function group(bool $enabled): ViewOptions
    {
        $this->group = $enabled;
        return $this;
    }

    /**
     * Sets the depth within the key to group results.
     *
     * @param int $depth the depth within the key to group results.
     *
     * @return ViewOptions
     * @since 4.0.0
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
     * @param mixed $value the value of the parameter
     *
     * @return ViewOptions
     * @since 4.0.0
     */
//    public function raw(string $key, $value): ViewOptions
//    {
//        if ($this->raw == null) {
//            $this->raw = array();
//        }
//
//        $this->raw[$key] = $value;
//        return $this;
//    }

    /**
     * Sets the key to skip to before beginning to return results.
     *
     * @param mixed $key the key to skip to before beginning to return results.
     *
     * @return ViewOptions
     * @since 4.0.0
     */
    public function startKey($key): ViewOptions
    {
        $this->startKey = $key;
        return $this;
    }

    /**
     * Sets the key to stop returning results at.
     *
     * @param mixed $key the key to stop returning results at.
     *
     * @return ViewOptions
     * @since 4.0.0
     */
    public function endKey($key): ViewOptions
    {
        $this->endKey = $key;
        return $this;
    }

    /**
     * Sets the document id to start returning results at within a number of results should startKey have multiple
     * entries within the index.
     *
     * @param string $key the key to use.
     *
     * @return ViewOptions
     * @since 4.0.0
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
     *
     * @return ViewOptions
     * @since 4.0.0
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
     *
     * @return ViewOptions
     * @since 4.0.0
     */
    public function inclusiveEnd(bool $enabled): ViewOptions
    {
        $this->inclusiveEnd = $enabled;
        return $this;
    }

//    /**
//     * Sets the behaviour of the query engine should an error occur during the gathering
//     * of view index results which would result in only partial results being available.
//     *
//     * @param int $onError the behaviour mode to apply on error.
//     * @return ViewOptions
//     */
//    public function onError(int $onError): ViewOptions
//    {
//        $this->onError = $onError;
//        return $this;
//    }

    /**
     * Sets the range of keys to skip to before beginning to return results and to stop returning results at.
     *
     * @param mixed $start the key to skip to before beginning to return results.
     * @param mixed $end the key to stop returning results at.
     * @param bool $inclusiveEnd whether the endKey values should be inclusive or exclusive.
     *
     * @return ViewOptions
     * @deprecated
     *
     * @since 4.0.0
     */
    public function range($start, $end, $inclusiveEnd = false): ViewOptions
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated, use startKey(), endKey(), and inclusiveEnd()',
            E_USER_DEPRECATED
        );
        $this->startKey = $start;
        $this->endKey = $end;
        $this->inclusiveEnd = $inclusiveEnd;
        return $this;
    }

    /**
     * Sets the range of keys to skip to before beginning to return results and to stop returning results at.
     *
     * @param mixed $start the doc id to skip to before beginning to return results.
     * @param mixed $end the doc id to stop returning results at.
     * @param bool $inclusiveEnd whether the endKey values should be inclusive or exclusive.
     *
     * @return ViewOptions
     * @deprecated
     *
     * @since 4.0.0
     */
    public function idRange($start, $end, $inclusiveEnd = false): ViewOptions
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated, use startKeyDocId(), endKeyDocId(), and inclusiveEnd()',
            E_USER_DEPRECATED
        );
        $this->startKeyDocId = $start;
        $this->endKeyDocId = $end;
        $this->inclusiveEnd = $inclusiveEnd;
        return $this;
    }

    /**
     * Sets whether to return debug information as part of the view response.
     *
     * @param bool $enabled whether to return debug information as part of the view response.
     *
     * @return ViewOptions
     * @since 4.0.0
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
     *
     * @return ViewOptions
     * @since 4.0.0
     */
    public function namespace(int $namespace): ViewOptions
    {
        $this->namespace = $namespace;
        return $this;
    }

    public static function export(?ViewOptions $options): array
    {
        if ($options == null) {
            return [
                'namespace' => DesignDocumentNamespace::PRODUCTION,
            ];
        }
        $raw = null;
        if ($options->raw != null) {
            foreach ($options->raw as $key => $param) {
                $raw[$key] = $param;
            }
        }
        $keys = null;
        if ($options->keys != null) {
            foreach ($options->keys as $param) {
                $keys[] = json_encode($param);
            }
        }
        $namespace = DesignDocumentNamespace::PRODUCTION;
        if ($options->namespace != null) {
            $namespace = $options->namespace;
        }
        $key = null;
        if ($options->key != null) {
            $key = json_encode($options->key);
        }
        $startKey = null;
        if ($options->startKey != null) {
            $startKey = json_encode($options->startKey);
        }
        $endKey = null;
        if ($options->endKey != null) {
            $endKey = json_encode($options->endKey);
        }

        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,

            'key' => $key,
            'keys' => $keys,
            'limit' => $options->limit,
            'skip' => $options->skip,
            'scanConsistency' => $options->scanConsistency,
            'order' => $options->order,
            'reduce' => $options->reduce,
            'group' => $options->group,
            'groupLevel' => $options->groupLevel,
            'raw' => $raw,
            'startKey' => $startKey,
            'endKey' => $endKey,
            'startKeyDocId' => $options->startKeyDocId,
            'endKeyDocId' => $options->endKeyDocId,
            'inclusiveEnd' => $options->inclusiveEnd,
            'onError' => $options->onError,
            'debug' => $options->debug,
            'namespace' => $namespace,
        ];
    }
}

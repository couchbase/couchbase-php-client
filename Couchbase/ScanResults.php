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

use IteratorAggregate;
use Traversable;

/**
 * Interface for ScanResults containing the iterator which streams back the ScanResults
 */
class ScanResults implements IteratorAggregate
{
    /**
     * @var resource
     */
    private $coreScanResult;
    private Transcoder $transcoder;

    /**
     * @param $core
     * @param string $bucketName
     * @param string $scopeName
     * @param string $collectionName
     * @param array $type
     * @param array $options
     * @param Transcoder $transcoder
     *
     * @internal
     *
     * @since 4.1.6
     */
    public function __construct($core, string $bucketName, string $scopeName, string $collectionName, array $type, array $options, Transcoder $transcoder)
    {
        $this->coreScanResult = Extension\createDocumentScanResult(
            $core,
            $bucketName,
            $scopeName,
            $collectionName,
            $type,
            $options
        );
        $this->transcoder = $transcoder;
    }

    /**
     * Returns the iterator which streams through the ScanResults
     *
     * @return Traversable
     *
     * @since 4.1.6
     */
    public function getIterator(): Traversable
    {
        return (function () {
            $res = Extension\documentScanNextItem($this->coreScanResult);
            while (!is_null($res)) {
                yield new ScanResult($res, $this->transcoder);
                $res = Extension\documentScanNextItem($this->coreScanResult);
            }
        })();
    }
}

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

use Couchbase\CollectionInterface;
use Couchbase\Exception\CollectionNotFoundException;
use Couchbase\Exception\FeatureNotAvailableException;
use Couchbase\Management\CollectionSpec;
use Couchbase\Management\CollectionManager;
use Couchbase\PrefixScan;
use Couchbase\RangeScan;
use Couchbase\SamplingScan;
use Couchbase\ScanOptions;
use Couchbase\UpsertOptions;
use Couchbase\DurabilityLevel;
use Couchbase\ScanResults;
use Couchbase\ScanTerm;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueScanTest extends Helpers\CouchbaseTestCase
{
    private CollectionInterface $collection;
    private string $sharedPrefix = "scan-test";
    private array $testIds = [];
    private array $batchByteLimitValues = [0, 1, 25, 100];
    private array $batchItemLimitValues = [0, 1, 25, 100];
    private array $concurrencyValues = [1, 2, 4, 8, 32, 128];
    private CollectionManager $manager;
    private CollectionSpec $collectionSpec;

    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfProtostellar();
        $this->skipIfUnsupported($this->version()->supportsCollections());

        $bucket = $this->openBucket(self::env()->bucketName());
        $defaultScope = $bucket->defaultScope();
        $this->manager = $bucket->collections();

        $collectionName = $this->uniqueId("collection");
        $this->collectionSpec = new CollectionSpec($collectionName, $defaultScope->name());
        $this->manager->createCollection($this->collectionSpec);

        $seenNewCollection = 0;

        while ($seenNewCollection < 10) {
            usleep(10000);
            foreach ($this->manager->getAllScopes() as $scope) {
                if ($scope->name() == $defaultScope->name()) {
                    foreach ($scope->collections() as $collection) {
                        if ($collection->name() == $collectionName) {
                            $seenNewCollection += 1;
                            break;
                        }
                    }
                    break;
                }
            }
        }

        $this->collection = $defaultScope->collection($collectionName);
        $options = UpsertOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        for ($i = 0; $i < 100; $i++) {
            $s = str_pad((string)$i, 2, "0", STR_PAD_LEFT);
            $id = $this->sharedPrefix . "-" . $s;
            $this->collection->upsert($id, ['num' => $s], $options);
            $this->testIds[] = $id;
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->testIds as $id) {
            try {
                $this->collection->remove($id);
            } catch (Exception $exception) {
            }
        }

        $this->manager->dropCollection($this->collectionSpec);
    }

    public function validateScan(ScanResults $scanResults, array $expectedIds, bool $idsOnly = false)
    {
        $testIdsReturned = [];
        foreach ($scanResults as $result) {
            $testIdsReturned[] = $result->id();
            $this->assertEquals($idsOnly, $result->idsOnly());
        }
        $this->assertEqualsCanonicalizing($expectedIds, $testIdsReturned);
        if ($idsOnly) {
            return;
        }

        foreach ($scanResults as $result) {
            $s = $result->content()['num'];
            $s = str_pad((string) $s, 2, "0", STR_PAD_LEFT);
            $id = $this->sharedPrefix . "-" . $s;
            $this->assertNotNull($result->cas());
            $this->assertEquals($id, $result->id());
        }
    }

    public function validateSamplingScan(ScanResults $scanResults, int $limit, bool $idsOnly = false)
    {
        $testIdsReturned = [];
        $results = [];
        foreach ($scanResults as $result) {
            $testIdsReturned[] = $result->id();
            $results[] = $result;
            $this->assertEquals($idsOnly, $result->idsOnly());
        }
        $this->assertLessThanOrEqual(sizeof($results), $limit);

        if ($idsOnly) {
            return;
        }

        foreach ($scanResults as $item) {
            if (in_array($item->id(), $testIdsReturned)) {
                $s = $result->content()['num'];
                $s = str_pad((string) $s, 2, "0", STR_PAD_LEFT);
                $id = $this->sharedPrefix . "-" . $s;
                $this->assertNotNull($item->cas());
                $this->assertEquals($item->id(), $this->sharedPrefix . "-" . $item->content()['num']);
            }
        }
    }

    public function testSimplePrefixScan()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "19");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );

        $results = $this->collection->scan(
            new PrefixScan($this->sharedPrefix . "-1")
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testSimpleRangeScan()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "29");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );

        $results = $this->collection->scan(
            new RangeScan(
                ScanTerm::build($this->sharedPrefix . "-10"),
                ScanTerm::build($this->sharedPrefix . "-29")
            )
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testSimpleSamplingScan()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $limit = 20;
        $results = $this->collection->scan(
            new SamplingScan($limit)
        );
        $this->validateSamplingScan($results, $limit);
    }

    public function testRangeScanExclusiveFrom()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("11", "29");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );
        $results = $this->collection->scan(
            new RangeScan(
                ScanTerm::build($this->sharedPrefix . "-10", true),
                ScanTerm::build($this->sharedPrefix . "-29")
            )
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testRangeScanExclusiveTo()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "28");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );
        $results = $this->collection->scan(
            new RangeScan(
                ScanTerm::build($this->sharedPrefix . "-10", false),
                ScanTerm::build($this->sharedPrefix . "-29", true)
            )
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testRangeScanBothExclusive()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("11", "28");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );
        $results = $this->collection->scan(
            new RangeScan(
                ScanTerm::build($this->sharedPrefix . "-10", true),
                ScanTerm::build($this->sharedPrefix . "-29", true)
            )
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testRangeScanDefaultFrom()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("00", "09");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-0" . $val;
            },
            $expectedIds
        );
        $results = $this->collection->scan(
            new RangeScan(
                null,
                ScanTerm::build($this->sharedPrefix . "-09")
            )
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testRangeScanDefaultTo()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("90", "99");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );
        $results = $this->collection->scan(
            new RangeScan(
                ScanTerm::build($this->sharedPrefix . "-90"),
                null
            )
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testRangeScanBothDefault()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $results = $this->collection->scan(
            new RangeScan()
        );
        $this->validateScan($results, $this->testIds);
    }

    public function testRangeScanIdsOnly()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "29");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );

        $results = $this->collection->scan(
            new RangeScan(
                ScanTerm::build($this->sharedPrefix . "-10"),
                ScanTerm::build($this->sharedPrefix . "-29")
            ),
            ScanOptions::build()->idsOnly(true)
        );
        $this->validateScan($results, $expectedIds, true);
    }

    public function testRangeScanExplicitlyWithContent()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "29");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );

        $results = $this->collection->scan(
            new RangeScan(
                ScanTerm::build($this->sharedPrefix . "-10"),
                ScanTerm::build($this->sharedPrefix . "-29")
            ),
            ScanOptions::build()->idsOnly(false)
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testPrefixScanIdsOnly()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "19");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );
        $results = $this->collection->scan(
            new PrefixScan($this->sharedPrefix . "-1"),
            ScanOptions::build()->idsOnly(true)
        );
        $this->validateScan($results, $expectedIds, true);
    }

    public function testSamplingScanIdsOnly()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $limit = 20;
        $results = $this->collection->scan(
            SamplingScan::build($limit),
            ScanOptions::build()->idsOnly(true)
        );
        $this->validateSamplingScan($results, $limit, true);
    }

    public function testSamplingScanWithSeed()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $limit = 20;
        $results = $this->collection->scan(
            SamplingScan::build($limit, 42),
            ScanOptions::build()->idsOnly(true)
        );
        $this->validateSamplingScan($results, $limit, true);
    }

    public function testRangeScanBatchByteLimit()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "29");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );

        foreach ($this->batchByteLimitValues as $value) {
            $results = $this->collection->scan(
                new RangeScan(
                    ScanTerm::build($this->sharedPrefix . "-10"),
                    ScanTerm::build($this->sharedPrefix . "-29")
                ),
                ScanOptions::build()->batchByteLimit($value)
            );
            $this->validateScan($results, $expectedIds);
        }
    }

    public function testPrefixScanBatchByteLimit()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "19");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );

        foreach ($this->batchByteLimitValues as $value) {
            $results = $this->collection->scan(
                PrefixScan::build($this->sharedPrefix . "-1"),
                ScanOptions::build()->batchByteLimit($value)
            );
            $this->validateScan($results, $expectedIds);
        }
    }

    public function testSamplingScanBatchByteLimit()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $limit = 20;

        foreach ($this->batchByteLimitValues as $value) {
            $results = $this->collection->scan(
                SamplingScan::build($limit),
                ScanOptions::build()->batchByteLimit($value)
            );
            $this->validateSamplingScan($results, $limit);
        }
    }

    public function testRangeScanConcurrency()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "29");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );

        foreach ($this->concurrencyValues as $value) {
            $results = $this->collection->scan(
                new RangeScan(
                    ScanTerm::build($this->sharedPrefix . "-10"),
                    ScanTerm::build($this->sharedPrefix . "-29")
                ),
                ScanOptions::build()->concurrency($value)
            );
            $this->validateScan($results, $expectedIds);
        }
    }

    public function testPrefixScanConcurrency()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "19");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );

        foreach ($this->concurrencyValues as $value) {
            $results = $this->collection->scan(
                new PrefixScan($this->sharedPrefix . "-1"),
                ScanOptions::build()->concurrency($value)
            );
            $this->validateScan($results, $expectedIds);
        }
    }

    public function testSamplingScanConcurrency()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $limit = 20;

        foreach ($this->concurrencyValues as $value) {
            $results = $this->collection->scan(
                new SamplingScan($limit),
                ScanOptions::build()->concurrency($value)
            );
            $this->validateSamplingScan($results, $limit);
        }
    }

    public function testRangeScanBatchItemLimit()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "29");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );

        foreach ($this->batchItemLimitValues as $value) {
            $results = $this->collection->scan(
                new RangeScan(
                    ScanTerm::build($this->sharedPrefix . "-10"),
                    ScanTerm::build($this->sharedPrefix . "-29")
                ),
                ScanOptions::build()->batchItemLimit($value)
            );
            $this->validateScan($results, $expectedIds);
        }
    }

    public function testPrefixScanBatchItemLimit()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "19");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );

        foreach ($this->batchItemLimitValues as $value) {
            $results = $this->collection->scan(
                new PrefixScan($this->sharedPrefix . "-1"),
                ScanOptions::build()->batchItemLimit($value)
            );
            $this->validateScan($results, $expectedIds);
        }
    }

    public function testSamplingScanBatchItemLimit()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $limit = 15;

        foreach ($this->batchItemLimitValues as $value) {
            $results = $this->collection->scan(
                new SamplingScan($limit),
                ScanOptions::build()->batchItemLimit($value)
            );
            $this->validateSamplingScan($results, $limit);
        }
    }

    public function testRangeScanMultipleOptions()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = range("10", "29");
        $expectedIds = array_map(
            function ($val) {
                return $this->sharedPrefix . "-" . $val;
            },
            $expectedIds
        );

        $results = $this->collection->scan(
            new RangeScan(
                ScanTerm::build($this->sharedPrefix . "-10"),
                ScanTerm::build($this->sharedPrefix . "-29")
            ),
            ScanOptions::build()->batchByteLimit(100)->batchItemLimit(20)->idsOnly(false)
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testRangeScanCollectionDoesNotExist()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $collection = $this->openBucket()->scope("_default")->collection("does_not_exist");
        $this->expectException(CollectionNotFoundException::class);
        $collection->scan(
            new RangeScan()
        );
    }

    public function testRangeScanSameFromTo()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = [$this->sharedPrefix . "-10"];

        $results = $this->collection->scan(
            new RangeScan(
                ScanTerm::build($this->sharedPrefix . "-10"),
                ScanTerm::build($this->sharedPrefix . "-10")
            )
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testRangeScanSameFromToExclusive()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = [];
        $results = $this->collection->scan(
            new RangeScan(
                ScanTerm::build($this->sharedPrefix . "-10", true),
                ScanTerm::build($this->sharedPrefix . "-10", true)
            )
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testRangeScanInvertedBounds()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $expectedIds = [];
        $results = $this->collection->scan(
            new RangeScan(
                ScanTerm::build($this->sharedPrefix . "-20", true),
                ScanTerm::build($this->sharedPrefix . "-10", true)
            )
        );
        $this->validateScan($results, $expectedIds);
    }

    public function testSamplingScanNonPositiveLimit()
    {
        $this->expectException(\Couchbase\Exception\InvalidArgumentException::class);
        $this->collection->scan(
            new SamplingScan(0)
        );
    }

    public function testRangeScanNonPositiveConcurrency()
    {
        $this->expectException(\Couchbase\Exception\InvalidArgumentException::class);
        $this->collection->scan(
            RangeScan::build(),
            ScanOptions::build()->concurrency(0)
        );
    }

    public function testRangeScanFeatureNotAvailable()
    {
        $this->skipIfUnsupported(!$this->version()->supportsRangeScan());

        $this->expectException(FeatureNotAvailableException::class);
        $this->collection->scan(
            RangeScan::build()
        );
    }
}

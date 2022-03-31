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

use Couchbase\Cluster;
use Couchbase\Extension;
use Couchbase\SearchOptions;
use Couchbase\TermSearchFacet;
use Couchbase\TermSearchQuery;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class SearchTest extends Helpers\CouchbaseTestCase
{
    function createSearchIndex(Cluster $cluster, string $indexName, string $bucketName)
    {
        Extension\searchIndexUpsert(
            $cluster->core(),
            [
            'name' => $indexName,
            'type' => 'fulltext-index',
            'sourceType' => 'couchbase',
            'sourceName' => $bucketName
            ]
        );
    }

    function testSearchReturnsResultsAndFacets()
    {
        $this->skipIfCaves();

        $cluster = $this->connectCluster();

        $num = 5;
        $name = sprintf("test_%s", $this->uniqueId());
        $service = sprintf("search_%s", $this->uniqueId());
        $this->createSearchDocs($cluster, $num, $service);
        $this->createSearchIndex($cluster, $name, $this->env()->bucketName());

        $query = new TermSearchQuery($service);
        $query = $query->field('service');

        $facets = [
            'type' => new TermSearchFacet('service', 5)
        ];

        $opts = new SearchOptions();
        $opts = $opts->includeLocations(true)->facets($facets);

        /** @var \Couchbase\SearchResult $result */
        $result = $this->retryFor(
            30,
            250,
            function () use ($cluster, $name, $query, $opts, $num) {
                $result = $cluster->searchQuery($name, $query, $opts);
                if (count($result->rows()) < $num) {
                    throw new Exception(sprintf("Expected %d rows but was %d", $num, count($result->rows())));
                }

                return $result;
            }
        );

        $this->assertCount($num, $result->rows());
        $meta = $result->metadata();
        $this->assertNotEmpty($meta->clientContextId());
        $this->assertEquals(5, $meta->totalHits());
        $this->assertEquals(0, $meta->errorCount());
        $this->assertNotEmpty($meta->maxScore());
        $this->assertNotEmpty($meta->took());

        $facets = $result->facets();
        $this->assertArrayHasKey('type', $facets);

        /** @var \Couchbase\SearchFacetResult $facet */
        $facet = $facets['type'];
        $this->assertEquals('service', $facet->field());
        $this->assertEquals(0, $facet->missing());
        $this->assertEquals(5, $facet->total());
        $this->assertEquals(0, $facet->other());

        /** @var []\Couchbase\TermFacetResult $facet */
        $terms = $facet->terms();
        $this->assertCount(1, $terms);
        /** @var \Couchbase\TermFacetResult $term */
        $term = $terms[0];
        $this->assertEquals($service, $term->term());
        $this->assertEquals(5, $term->count());
    }
}

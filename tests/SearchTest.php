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

use Couchbase\BooleanSearchQuery;
use Couchbase\ClusterInterface;
use Couchbase\CollectionInterface;
use Couchbase\ConjunctionSearchQuery;
use Couchbase\DateRangeSearchFacet;
use Couchbase\DateRangeSearchQuery;
use Couchbase\DisjunctionSearchQuery;
use Couchbase\DocIdSearchQuery;
use Couchbase\DurabilityLevel;
use Couchbase\Exception\FeatureNotAvailableException;
use Couchbase\Exception\IndexNotFoundException;
use Couchbase\GeoBoundingBoxSearchQuery;
use Couchbase\GeoDistanceSearchQuery;
use Couchbase\Management\SearchIndex;
use Couchbase\Management\SearchIndexManager;
use Couchbase\MatchAllSearchQuery;
use Couchbase\MatchNoneSearchQuery;
use Couchbase\MatchPhraseSearchQuery;
use Couchbase\MatchSearchQuery;
use Couchbase\MutationState;
use Couchbase\NumericRangeSearchFacet;
use Couchbase\NumericRangeSearchQuery;
use Couchbase\PhraseSearchQuery;
use Couchbase\RegexpSearchQuery;
use Couchbase\SearchHighlightMode;
use Couchbase\SearchOptions;
use Couchbase\SearchRequest;
use Couchbase\SearchSortField;
use Couchbase\SearchSortGeoDistance;
use Couchbase\SearchSortId;
use Couchbase\SearchSortMissing;
use Couchbase\SearchSortScore;
use Couchbase\SearchSortType;
use Couchbase\TermRangeSearchQuery;
use Couchbase\TermSearchFacet;
use Couchbase\TermSearchQuery;
use Couchbase\UpsertOptions;
use Couchbase\VectorQuery;
use Couchbase\VectorQueryCombination;
use Couchbase\VectorSearch;
use Couchbase\VectorSearchOptions;
use Couchbase\WildcardSearchQuery;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class SearchTest extends Helpers\CouchbaseTestCase
{
    private ClusterInterface $cluster;
    private CollectionInterface $collection;
    private SearchIndexManager $indexManager;

    /**
     * @return number of the documents in dataset
     */
    public function loadDataset(): int
    {
        $dataset = json_decode(file_get_contents(__DIR__ . "/beer-data.json"), true);

        $options = UpsertOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        foreach ($dataset as $id => $document) {
            $this->collection->upsert($id, $document, $options);
        }

        return count($dataset);
    }

    public function createSearchIndex(int $datasetSize): void
    {
        fprintf(STDERR, "Create 'beer-search' to index %d docs\n", $datasetSize);
        $indexDump = json_decode(file_get_contents(__DIR__ . "/beer-search.json"), true);
        $index = SearchIndex::build("beer-search", self::env()->bucketName());
        $index->setParams($indexDump["params"]);
        $this->indexManager->upsertIndex($index);

        $previousIndexed = 0;
        $numberOfDocumentsHasNotChanged = 0;
        $start = time();
        while (true) {
            try {
                $indexedDocuments = $this->indexManager->getIndexedDocumentsCount("beer-search");
                fprintf(STDERR, "%ds, Indexing 'beer-search': %d docs\n", time() - $start, $indexedDocuments);
                if ($indexedDocuments >= $datasetSize) {
                    // the indexer settled on the same number of the documents
                    // since last check
                    if ($previousIndexed == $indexedDocuments) {
                        $numberOfDocumentsHasNotChanged += 1;
                    } else {
                        // the indexer still working, just very slow
                        $numberOfDocumentsHasNotChanged = 0;
                    }
                    if ($numberOfDocumentsHasNotChanged > 3) {
                        // the indexer returns the same number of the indexed
                        // document three times in a row, maybe it is done
                        // already?
                        break;
                    }
                }
                $previousIndexed = $indexedDocuments;
                sleep(4);
            } catch (\Couchbase\Exception\IndexNotReadyException $ex) {
            }
        }
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->cluster = $this->connectCluster();
        $this->collection = $this->openBucket(self::env()->bucketName())->defaultCollection();

        if (self::env()->useCouchbase()) {
            $this->indexManager = $this->cluster->searchIndexes();
            try {
                $this->indexManager->getIndex("beer-search");
            } catch (IndexNotFoundException $ex) {
                $this->createSearchIndex($this->loadDataset());
            }
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // close cluster here?
    }

    public function testSearchWithLimit()
    {
        $this->skipIfCaves();

        $query = new MatchPhraseSearchQuery("hop beer");
        $options = SearchOptions::build()->limit(3);

        $result = $this->cluster->searchQuery("beer-search", $query, $options);

        $this->assertNotNull($result);
        $this->assertNotEmpty($result->rows());
        $this->assertCount(2, $result->rows());
        $this->assertEquals(2, $result->metaData()->totalHits());

        foreach ($result->rows() as $hit) {
            $this->assertNotNull($hit['id']);
            $this->assertStringStartsWith("beer-search", $hit['index']);
            $this->assertGreaterThan(0, $hit['score']);
        }
    }

    public function testSearchQueryWithRequestApi()
    {
        $this->skipIfCaves();

        $query = new MatchPhraseSearchQuery("hop beer");
        $options = SearchOptions::build()->limit(3);
        $request = SearchRequest::build($query);

        $result = $this->cluster->search("beer-search", $request, $options);

        $this->assertNotNull($result);
        $this->assertNotEmpty($result->rows());
        $this->assertCount(2, $result->rows());
        $this->assertEquals(2, $result->metaData()->totalHits());

        foreach ($result->rows() as $hit) {
            $this->assertNotNull($hit['id']);
            $this->assertStringStartsWith("beer-search", $hit['index']);
            $this->assertGreaterThan(0, $hit['score']);
        }
    }

    public function testSearchWithNoHits()
    {
        $this->skipIfCaves();

        $query = new MatchPhraseSearchQuery("doesnotexistintheindex");
        $options = SearchOptions::build()->limit(3);

        $result = $this->cluster->searchQuery("beer-search", $query, $options);

        $this->assertNotNull($result);
        $this->assertEmpty($result->rows());
        $this->assertEquals(0, $result->metaData()->totalHits());
    }


    public function testSearchWithConsistency()
    {
        $this->skipIfCaves();

        $id = $this->uniqueId();
        $query = new MatchPhraseSearchQuery($id);
        $options = SearchOptions::build()->limit(3);

        $result = $this->cluster->searchQuery("beer-search", $query, $options);

        $this->assertNotNull($result);
        $this->assertEmpty($result->rows());
        $this->assertEquals(0, $result->metaData()->totalHits());

        $result = $this->collection->upsert($id, ["type" => "beer", "name" => $id]);
        $mutationState = new MutationState();
        $mutationState->add($result);

        $options->consistentWith("beer-search", $mutationState);

        // Eventual consistency for consistent with...
        $result = $this->retryFor(
            1,
            200,
            function () use ($query, $options) {
                $result = $this->cluster->searchQuery("beer-search", $query, $options);
                if (count($result->rows()) == 0) {
                    throw new Exception("Excepted rows to not to be empty");
                }

                return $result;
            }
        );

        $this->assertNotNull($result);
        $this->assertNotEmpty($result->rows());
        $this->assertEquals(1, $result->metaData()->totalHits());
        $this->assertEquals($id, $result->rows()[0]['id']);
    }

    public function testSearchWithFields()
    {
        $this->skipIfCaves();

        $nameField = "name";
        $query = new MatchPhraseSearchQuery("hop beer");
        $options = SearchOptions::build()->limit(3)->fields([$nameField]);

        $result = $this->cluster->searchQuery("beer-search", $query, $options);

        $this->assertNotNull($result);
        $this->assertNotEmpty($result->rows());
        $this->assertCount(2, $result->rows());
        $this->assertEquals(2, $result->metaData()->totalHits());

        foreach ($result->rows() as $hit) {
            $this->assertNotNull($hit['id']);
            $this->assertStringStartsWith("beer-search", $hit['index']);
            $this->assertGreaterThan(0, $hit['score']);
            $this->assertNotEmpty($hit['fields']);
            $this->assertNotNull($hit['fields']['name']);
        }
    }

    public function testSearchWithSkip()
    {
        $this->skipIfCaves();

        $query = new MatchPhraseSearchQuery("hop beer");
        $options_none = SearchOptions::build()->limit(3);
        $options_low = SearchOptions::build()->limit(3)->skip(10);
        $options_excess = SearchOptions::build()->limit(3)->skip(7000);

        $result_none = $this->cluster->searchQuery("beer-search", $query, $options_none);
        $result_low = $this->cluster->searchQuery("beer-search", $query, $options_low);
        $result_excess = $this->cluster->searchQuery("beer-search", $query, $options_excess);

        $this->assertNotNull($result_none);
        $this->assertNotNull($result_low);
        $this->assertNotNull($result_excess);
        $this->assertNotEquals($result_none->rows(), $result_low->rows());
        $this->assertEmpty($result_excess->rows());
    }

    public function testSearchWithSort()
    {
        $this->skipIfCaves();

        $query = new MatchPhraseSearchQuery("hop beer");
        $options = SearchOptions::build()
            ->limit(3)
            ->sort(
                [
                'hello',
                (new SearchSortId())->descending(true),
                new SearchSortScore(),
                new SearchSortGeoDistance("foo", 27.4395527, 53.8835622),
                (new SearchSortField("bar"))
                    ->type(SearchSortType::NUMBER)
                    ->missing(SearchSortMissing::FIRST),
                ]
            );

        $result = $this->cluster->searchQuery("beer-search", $query, $options);

        $this->assertNotNull($result);
        $this->assertNotEmpty($result->rows());
        $this->assertCount(2, $result->rows());
        $this->assertEquals(2, $result->metaData()->totalHits());

        foreach ($result->rows() as $hit) {
            $this->assertNotNull($hit['id']);
            $this->assertStringStartsWith("beer-search", $hit['index']);
            $this->assertGreaterThan(0, $hit['score']);
        }
    }

    /**
     * @throws \Couchbase\Exception\InvalidArgumentException
     */
    public function testSearchWithRanges()
    {
        $this->skipIfCaves();

        $query = (new NumericRangeSearchQuery())->field("abv")->min(2.0)->max(3.2);
        $options = SearchOptions::build()->fields(["abv"]);
        $result = $this->cluster->searchQuery("beer-search", $query, $options);

        $this->assertNotNull($result);
        $this->assertNotEmpty($result->rows());
        $this->assertEquals(count($result->rows()), $result->metaData()->totalHits());

        foreach ($result->rows() as $hit) {
            $this->assertNotNull($hit['id']);
            $this->assertStringStartsWith("beer-search", $hit['index']);
            $this->assertGreaterThan(0, $hit['score']);
            $this->assertNotEmpty($hit['fields']);
            $this->assertNotNull($hit['fields']['abv']);
            $this->assertGreaterThanOrEqual(2.0, $hit['fields']['abv']);
            $this->assertLessThan(3.3, $hit['fields']['abv']);
        }

        $startDate = new DateTime('2010-11-01 10:00:00');
        $startStr = $startDate->format(DATE_RFC3339);
        $query = new ConjunctionSearchQuery(
            [
                (new TermSearchQuery("beer"))->field("type"),
                (new DateRangeSearchQuery())->field("updated")->start($startStr)->end(mktime(20, 0, 0, 12, 1, 2010)),
            ]
        );
        $options = SearchOptions::build()->fields(["updated", "type"]);
        $result = $this->cluster->searchQuery("beer-search", $query, $options);

        $this->assertNotNull($result);
        $this->assertNotEmpty($result->rows());
        $this->assertEquals(count($result->rows()), $result->metaData()->totalHits());

        $endDate = new DateTime('2010-12-01 20:00:00');
        foreach ($result->rows() as $hit) {
            $this->assertNotNull($hit['id']);
            $this->assertStringStartsWith("beer-search", $hit['index']);
            $this->assertGreaterThan(0, $hit['score']);
            $this->assertNotEmpty($hit['fields']);
            $this->assertNotNull($hit['fields']['updated']);
            $hitDate = new DateTime($hit['fields']['updated']);
            $diff = $startDate->diff($hitDate);
            $this->assertEquals(
                0,
                $diff->invert,
                sprintf(
                    "The hit->update date (%s) should go after start date (%s)",
                    $hitDate->format(DATE_RFC3339),
                    $startDate->format(DATE_RFC3339)
                )
            );
            $diff = $endDate->diff($hitDate);
            $this->assertEquals(
                1,
                $diff->invert,
                sprintf(
                    "The hit->update date (%s) should go before or equals to end date (%s)",
                    $hitDate->format(DATE_RFC3339),
                    $startDate->format(DATE_RFC3339)
                )
            );
        }
    }

    public function testCompoundSearchQueries()
    {
        $this->skipIfCaves();

        $nameQuery = (new MatchSearchQuery("green"))->field("name")->boost(3.4);
        $descriptionQuery = (new MatchSearchQuery("fuggles"))->field("description")->fuzziness(1);

        $disjunctionQuery = new DisjunctionSearchQuery([$nameQuery, $descriptionQuery]);
        $options = SearchOptions::build()->fields(["type", "name", "description"]);
        $result = $this->cluster->searchQuery("beer-search", $disjunctionQuery, $options);
        $this->assertGreaterThanOrEqual(10, $result->metaData()->totalHits());
        $this->assertNotEmpty($result->rows());
        $this->assertMatchesRegularExpression('/green/i', $result->rows()[0]['fields']['name']);
        $this->assertDoesNotMatchRegularExpression('/fuggles/i', $result->rows()[0]['fields']['name']);
        $this->assertMatchesRegularExpression('/fuggles/i', $result->rows()[0]['fields']['description']);
        $this->assertDoesNotMatchRegularExpression('/green/i', $result->rows()[0]['fields']['description']);

        $disjunctionQuery->min(2);
        $options = SearchOptions::build()->fields(["type", "name", "description"]);
        $result = $this->cluster->searchQuery("beer-search", $disjunctionQuery, $options);
        $this->assertNotEmpty($result->rows());
        $this->assertLessThan(10, $result->metaData()->totalHits());
        $disjunctionResult = $result;

        $conjunctionQuery = new ConjunctionSearchQuery([$nameQuery, $descriptionQuery]);
        $options = SearchOptions::build()->fields(["type", "name", "description"]);
        $result = $this->cluster->searchQuery("beer-search", $conjunctionQuery, $options);
        $this->assertNotEmpty($result->rows());
        $this->assertSameSize($disjunctionResult->rows(), $result->rows());
        $this->assertEquals(
            $disjunctionResult->rows()[0]['fields']['name'],
            $result->rows()[0]['fields']['name']
        );
        $this->assertEquals(
            $disjunctionResult->rows()[0]['fields']['description'],
            $result->rows()[0]['fields']['description']
        );
    }

    public function testSearchWithFragments()
    {
        $this->skipIfCaves();

        $query = new MatchSearchQuery("hop beer");
        $options = SearchOptions::build()
            ->limit(3)
            ->highlight(SearchHighlightMode::HTML, ["name"]);

        $result = $this->cluster->searchQuery("beer-search", $query, $options);
        $this->assertNotEmpty($result->rows());

        foreach ($result->rows() as $hit) {
            $this->assertNotNull($hit['id']);
            $this->assertNotEmpty($hit['fragments']);
            $this->assertNotNull($hit['fragments']['name']);
            foreach ($hit['fragments']['name'] as $fragment) {
                $this->assertMatchesRegularExpression('/<mark>/', $fragment);
            }
        }
    }

    public function testSearchWithFacets()
    {
        $this->skipIfCaves();

        $query = (new TermSearchQuery("beer"))->field("type");
        $options = SearchOptions::build()
            ->facets(
                [
                    "foo" => new TermSearchFacet("name", 3),
                    "bar" => (new DateRangeSearchFacet("updated", 1))
                        ->addRange("old", null, mktime(0, 0, 0, 1, 1, 2014)), // "2014-01-01T00:00:00" also acceptable
                    "baz" => (new NumericRangeSearchFacet("abv", 2))
                        ->addRange("strong", 4.9)
                        ->addRange("light", null, 4.89),
                ]
            );

        $result = $this->cluster->searchQuery("beer-search", $query, $options);

        $this->assertNotEmpty($result->rows());
        $this->assertNotEmpty($result->facets());

        $this->assertNotNull($result->facets()['foo']);
        $this->assertEquals('name', $result->facets()['foo']->field());
        $this->assertEquals('ale', $result->facets()['foo']->terms()[0]->term());
        $this->assertGreaterThanOrEqual(10, $result->facets()['foo']->terms()[0]->count());

        $this->assertNotNull($result->facets()['bar']);
        $this->assertEquals('updated', $result->facets()['bar']->field());
        $this->assertEquals('old', $result->facets()['bar']->dateRanges()[0]->name());
        $this->assertGreaterThanOrEqual(30, $result->facets()['bar']->dateRanges()[0]->count());

        $this->assertNotNull($result->facets()['baz']);
        $this->assertEquals('abv', $result->facets()['baz']->field());
        $this->assertEquals('light', $result->facets()['baz']->numericRanges()[0]->name());
        $this->assertGreaterThan(0, $result->facets()['baz']->numericRanges()[0]->max());
        $this->assertGreaterThanOrEqual(15, $result->facets()['baz']->numericRanges()[0]->count());
    }

    public function testNullInNumericRangeFacet()
    {
        $facet = (new NumericRangeSearchFacet("abv", 2))->addRange("light", null, 4.89)->addRange("staut", null, 7.89);
        $this->assertNotNull(json_encode($facet));
    }

    public function testSearchQuery()
    {
        $query = (new BooleanSearchQuery())
            ->must(
                (new ConjunctionSearchQuery(
                    [
                        (new DocIdSearchQuery())->docIds('bar', 'baz'),
                        new MatchSearchQuery('hello world'),
                    ]
                ))
                    ->and(new MatchAllSearchQuery())
            )
            ->should(
                (new DisjunctionSearchQuery(
                    [
                        new MatchNoneSearchQuery(),
                        (new DateRangeSearchQuery())->start('2010-11-01T10:00:00+00:00')->end('2010-12-01T10:00:00+00:00'),
                        (new TermRangeSearchQuery())->min('hello')->max('world'),
                        new GeoDistanceSearchQuery(1.0, 3.0, "10mi"),
                        new GeoBoundingBoxSearchQuery(1.0, 3.0, 4.0, 5.0),
                    ]
                ))
                    ->or((new NumericRangeSearchQuery())->min(3)->max(42.5))
                    ->or((new WildcardSearchQuery('user*'))->field('type'))
            )
            ->mustNot(
                new DisjunctionSearchQuery(
                    [
                        (new PhraseSearchQuery('foo', 'bar', 'baz'))->field('description'),
                        (new RegexpSearchQuery('user.*'))->field('_class_name'),
                    ]
                )
            );
        $result = json_encode($query);
        $this->assertNotNull($result);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());

        $options = SearchOptions::build()
            ->fields(["foo", "bar", "baz"])
            ->highlight(SearchHighlightMode::SIMPLE, ["foo", "bar", "baz"])
            ->facets(
                [
                    "foo" => new TermSearchFacet("name", 3),
                    "bar" => (new DateRangeSearchFacet("updated", 2))->addRange("old", null, "2014-01-01T00:00:00"),
                    "baz" => (new NumericRangeSearchFacet("abv", 2))->addRange("string", 4.9)->addRange("light", null, 4.89),
                ]
            );
        $result = json_encode($options);
        $this->assertNotNull($result);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
    }

    public function testBooleanSearchQuery()
    {
        $matchQuery = new MatchSearchQuery('hello world');

        $disjunctionQuery = new DisjunctionSearchQuery([$matchQuery]);
        $boolQuery = (new BooleanSearchQuery())->mustNot($disjunctionQuery);

        $result = json_encode($boolQuery);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertEquals('{"must_not":{"disjuncts":[{"match":"hello world"}]}}', $result);
    }

    public function testVectorSearchThrowsIndexNotFound()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsVectorSearch());

        $vectorQueryOne = VectorQuery::build("foo", [0.32, -0.536, 0.842])->boost(0.5)->numCandidates(4);
        $vectorQueryTwo = VectorQuery::build("bar", [-0.00810353, 0.6433, 0.52364]);

        $searchRequest = SearchRequest::build(
            VectorSearch::build(
                [$vectorQueryOne, $vectorQueryTwo],
                VectorSearchOptions::build()
                ->vectorQueryCombination(VectorQueryCombination::AND)
            )
        );

        $this->expectException(IndexNotFoundException::class);
        $this->cluster->search("does-not-exist", $searchRequest);
    }

    public function testVectorSearchEncoding()
    {
        $vectorQueryOne = VectorQuery::build("foo", [0.32, -0.536, 0.842])->boost(0.5)->numCandidates(4);
        $vectorQueryTwo = VectorQuery::build("bar", [-0.00810353, 0.6433, 0.52364]);
        $searchRequest = SearchRequest::export(SearchRequest::build(VectorSearch::build([$vectorQueryOne, $vectorQueryTwo])));

        $encodedVectorSearch = json_encode($searchRequest['vectorSearch']);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertEquals('[{"field":"foo","boost":0.5,"vector":[0.32,-0.536,0.842],"k":4},{"field":"bar","vector":[-0.00810353,0.6433,0.52364],"k":3}]', $encodedVectorSearch);

        $encodedSearchQuery = json_encode($searchRequest['searchQuery']);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertEquals('{"match_none":"null"}', $encodedSearchQuery);
    }

    public function testScopeSearch()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsScopeSearchIndexes());

        $searchRequest = SearchRequest::build(new MatchNoneSearchQuery());
        $this->expectException(IndexNotFoundException::class);
        $this->openBucket()->defaultScope()->search("unknown-index", $searchRequest);
    }

    public function testScopeSearchThrowsFeatureNotAvailable()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported(!$this->version()->supportsScopeSearchIndexes());

        $searchRequest = SearchRequest::build(new MatchNoneSearchQuery());
        $this->expectException(FeatureNotAvailableException::class);
        $this->openBucket()->defaultScope()->search("unknown-index", $searchRequest);
    }
}

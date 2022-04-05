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
use Couchbase\ConjunctionSearchQuery;
use Couchbase\DateRangeSearchFacet;
use Couchbase\DateRangeSearchQuery;
use Couchbase\DisjunctionSearchQuery;
use Couchbase\MatchPhraseSearchQuery;
use Couchbase\MatchSearchQuery;
use Couchbase\MutationState;
use Couchbase\NumericRangeSearchFacet;
use Couchbase\NumericRangeSearchQuery;
use Couchbase\SearchHighlightMode;
use Couchbase\SearchOptions;
use Couchbase\TermSearchFacet;
use Couchbase\TermSearchQuery;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class SearchTest extends Helpers\CouchbaseTestCase
{
    private Cluster $cluster;

    public function setUp(): void
    {
        parent::setUp();

        $this->cluster = $this->connectCluster();
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
        $options = new SearchOptions();
        $options->limit(3);

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

    function testSearchWithNoHits()
    {
        $this->skipIfCaves();

        $query = new MatchPhraseSearchQuery("doesnotexistintheindex");
        $options = new SearchOptions();
        $options->limit(3);

        $result = $this->cluster->searchQuery("beer-search", $query, $options);

        $this->assertNotNull($result);
        $this->assertEmpty($result->rows());
        $this->assertEquals(0, $result->metaData()->totalHits());
    }

    function testSearchWithConsistency()
    {
        $this->skipIfCaves();

        $id = $this->uniqueId();
        $query = new MatchPhraseSearchQuery($id);
        $options = new SearchOptions();
        $options->limit(3);

        $result = $this->cluster->searchQuery("beer-search", $query, $options);

        $this->assertNotNull($result);
        $this->assertEmpty($result->rows());
        $this->assertEquals(0, $result->metaData()->totalHits());

        $collection = $this->cluster->bucket('beer-sample')->defaultCollection();
        $result = $collection->upsert($id, ["type" => "beer", "name" => $id]);
        $mutationState = new MutationState();
        $mutationState->add($result);

        $options->consistentWith("beer-search", $mutationState);

        // Eventual consistency for consistent with...
        $result = $this->retryFor(
            5,
            50,
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

    function testSearchWithFields()
    {
        $this->skipIfCaves();

        $nameField = "name";
        $query = new MatchPhraseSearchQuery("hop beer");
        $options = new SearchOptions();
        $options->limit(3)->fields([$nameField]);

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

    function testSearchWithSort()
    {
        $this->skipIfCaves();

        $query = new MatchPhraseSearchQuery("hop beer");
        $options = new SearchOptions();
        $options->limit(3)->sort(
            [
            'hello',
            (new \Couchbase\SearchSortId())->descending(true),
            new \Couchbase\SearchSortScore(),
            new \Couchbase\SearchSortGeoDistance("foo", 27.4395527, 53.8835622),
            (new \Couchbase\SearchSortField("bar"))
                ->type(\Couchbase\SearchSortType::NUMBER)
                ->missing(\Couchbase\SearchSortMissing::FIRST)
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

    function testSearchWithRanges()
    {
        $this->skipIfCaves();

        $query = (new NumericRangeSearchQuery())->field("abv")->min(2.0)->max(3.2);
        $options = new SearchOptions();
        $options->fields(["abv"]);
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
            (new DateRangeSearchQuery())->field("updated")->start($startStr)->end(mktime(20, 0, 0, 12, 1, 2010))
            ]
        );
        $options = new SearchOptions();
        $options->fields(["updated", "type"]);
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
            $this->assertEquals(0, $diff->invert, "The hit->update date ({$hitDate->format(DATE_RFC3339)}) should go after start date ({$startDate->format(DATE_RFC3339)})");
            $diff = $endDate->diff($hitDate);
            $this->assertEquals(1, $diff->invert, "The hit->update date ({$hitDate->format(DATE_RFC3339)}) should go before or equals to end date ({$startDate->format(DATE_RFC3339)})");
        }
    }

    function testCompoundSearchQueries()
    {
        $this->skipIfCaves();

        $nameQuery = (new MatchSearchQuery("green"))->field("name")->boost(3.4);
        $descriptionQuery = (new MatchSearchQuery("hop"))->field("description")->fuzziness(1);

        $disjunctionQuery = new DisjunctionSearchQuery([$nameQuery, $descriptionQuery]);
        $options = new SearchOptions();
        $options->fields(["type", "name", "description"]);
        $result = $this->cluster->searchQuery("beer-search", $disjunctionQuery, $options);
        $this->assertGreaterThan(1000, $result->metaData()->totalHits());
        $this->assertNotEmpty($result->rows());
        $this->assertMatchesRegularExpression('/green/i', $result->rows()[0]['fields']['name']);
        $this->assertDoesNotMatchRegularExpression('/hop/i', $result->rows()[0]['fields']['name']);
        $this->assertMatchesRegularExpression('/hop/i', $result->rows()[0]['fields']['description']);
        $this->assertDoesNotMatchRegularExpression('/green/i', $result->rows()[0]['fields']['description']);

        $disjunctionQuery->min(2);
        $options = new SearchOptions();
        $options->fields(["type", "name", "description"]);
        $result = $this->cluster->searchQuery("beer-search", $disjunctionQuery, $options);
        $this->assertNotEmpty($result->rows());
        $this->assertLessThan(10, $result->metaData()->totalHits());
        $disjunction2Result = $result;

        $conjunctionQuery = new ConjunctionSearchQuery([$nameQuery, $descriptionQuery]);
        $options = new SearchOptions();
        $options->fields(["type", "name", "description"]);
        $result = $this->cluster->searchQuery("beer-search", $conjunctionQuery, $options);
        $this->assertNotEmpty($result->rows());
        $this->assertEquals(count($disjunction2Result->rows()), count($result->rows()));
        $this->assertEquals(
            $disjunction2Result->rows()[0]['fields']['name'],
            $result->rows()[0]['fields']['name']
        );
        $this->assertEquals(
            $disjunction2Result->rows()[0]['fields']['description'],
            $result->rows()[0]['fields']['description']
        );
    }

    function testSearchWithFragments()
    {
        $this->skipIfCaves();

        $query = new MatchSearchQuery("hop beer");
        $options = new SearchOptions();
        $options->limit(3)->highlight(SearchHighlightMode::HTML, ["name"]);

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

    function testSearchWithFacets()
    {
        $this->skipIfCaves();

        $query = (new TermSearchQuery("beer"))->field("type");
        $options = new SearchOptions();
        $options->facets(
            [
            "foo" => new TermSearchFacet("name", 3),
            "bar" => (new DateRangeSearchFacet("updated", 1))
                ->addRange("old", null, mktime(0, 0, 0, 1, 1, 2014)), // "2014-01-01T00:00:00" also acceptable
            "baz" => (new NumericRangeSearchFacet("abv", 2))
                ->addRange("strong", 4.9, null)
                ->addRange("light", null, 4.89)
            ]
        );

        $result = $this->cluster->searchQuery("beer-search", $query, $options);

        $this->assertNotEmpty($result->rows());
        $this->assertNotEmpty($result->facets());

        $this->assertNotNull($result->facets()['foo']);
        $this->assertEquals('name', $result->facets()['foo']->field());
        $this->assertEquals('ale', $result->facets()['foo']->terms()[0]->term());
        $this->assertGreaterThan(1000, $result->facets()['foo']->terms()[0]->count());

        $this->assertNotNull($result->facets()['bar']);
        $this->assertEquals('updated', $result->facets()['bar']->field());
        $this->assertEquals('old', $result->facets()['bar']->dateRanges()[0]->name());
        $this->assertGreaterThan(5000, $result->facets()['bar']->dateRanges()[0]->count());

        $this->assertNotNull($result->facets()['baz']);
        $this->assertEquals('abv', $result->facets()['baz']->field());
        $this->assertEquals('light', $result->facets()['baz']->numericRanges()[0]->name());
        $this->assertGreaterThan(0, $result->facets()['baz']->numericRanges()[0]->max());
        $this->assertGreaterThan(100, $result->facets()['baz']->numericRanges()[0]->count());
    }

    function testNullInNumericRangeFacet()
    {
        $facet = (new NumericRangeSearchFacet("abv", 2))->addRange("light", null, 4.89)->addRange("staut", null, 7.89);
        $this->assertNotNull(json_encode($facet));
    }

    function testSearchQuery()
    {
        $query = (new \Couchbase\BooleanSearchQuery())
            ->must(
                (new \Couchbase\ConjunctionSearchQuery(
                    [
                    (new \Couchbase\DocIdSearchQuery())->docIds('bar', 'baz'),
                    new \Couchbase\MatchSearchQuery('hello world')
                    ]
                ))
                    ->and(new \Couchbase\MatchAllSearchQuery())
            )
            ->should(
                (new \Couchbase\DisjunctionSearchQuery(
                    [
                    new \Couchbase\MatchNoneSearchQuery(),
                    (new \Couchbase\DateRangeSearchQuery())->start('2010-11-01T10:00:00+00:00')->end('2010-12-01T10:00:00+00:00'),
                    (new \Couchbase\TermRangeSearchQuery())->min('hello')->max('world'),
                    new \Couchbase\GeoDistanceSearchQuery(1.0, 3.0, "10mi"),
                    new \Couchbase\GeoBoundingBoxSearchQuery(1.0, 3.0, 4.0, 5.0),
                    ]
                ))
                    ->or((new \Couchbase\NumericRangeSearchQuery())->min(3)->max(42.5))
                    ->or((new \Couchbase\WildcardSearchQuery('user*'))->field('type'))
            )
            ->mustNot(
                new \Couchbase\DisjunctionSearchQuery(
                    [
                    (new \Couchbase\PhraseSearchQuery('foo', 'bar', 'baz'))->field('description'),
                    (new \Couchbase\RegexpSearchQuery('user.*'))->field('_class_name')
                    ]
                )
            );
        $result = json_encode($query);
        $this->assertNotNull($result);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());

        $options = new \Couchbase\SearchOptions();
        $options->fields(["foo", "bar", "baz"]);
        $options->highlight(\Couchbase\SearchHighlightMode::SIMPLE, ["foo", "bar", "baz"]);
        $options->facets(
            [
            "foo" => new \Couchbase\TermSearchFacet("name", 3),
            "bar" => (new \Couchbase\DateRangeSearchFacet("updated", 2))->addRange("old", null, "2014-01-01T00:00:00"),
            "baz" => (new \Couchbase\NumericRangeSearchFacet("abv", 2))->addRange("string", 4.9, null)->addRange("light", null, 4.89)
            ]
        );
        $result = json_encode($options);
        $this->assertNotNull($result);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
    }

    function testBooleanSearchQuery()
    {
        $match_query = new \Couchbase\MatchSearchQuery('hello world');

        $disjunction_query = new \Couchbase\DisjunctionSearchQuery([$match_query]);
        $bool_query = (new \Couchbase\BooleanSearchQuery())->mustNot($disjunction_query);

        $result = json_encode($bool_query);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $this->assertEquals('{"must_not":{"disjuncts":[{"match":"hello world"}]}}', $result);
    }
}

/*
curl -XPUT -H "Content-Type: application/json" \
-u Administrator:password http://localhost:8094/api/index/beer-search -d \
'{
  "type": "fulltext-index",
  "name": "beer-search",
  "sourceType": "couchbase",
  "sourceName": "beer-sample",
  "planParams": {
    "maxPartitionsPerPIndex": 171,
    "indexPartitions": 6
  },
  "params": {
    "doc_config": {
      "docid_prefix_delim": "",
      "docid_regexp": "",
      "mode": "type_field",
      "type_field": "type"
    },
    "mapping": {
      "analysis": {},
      "default_analyzer": "standard",
      "default_datetime_parser": "dateTimeOptional",
      "default_field": "_all",
      "default_mapping": {
        "dynamic": true,
        "enabled": true
      },
      "default_type": "_default",
      "docvalues_dynamic": true,
      "index_dynamic": true,
      "store_dynamic": false,
      "type_field": "_type",
      "types": {
        "beer": {
          "dynamic": true,
          "enabled": true,
          "properties": {
            "abv": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "abv",
                  "store": true,
                  "type": "number"
                }
              ]
            },
            "category": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "category",
                  "store": true,
                  "type": "text"
                }
              ]
            },
            "description": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "description",
                  "store": true,
                  "type": "text"
                }
              ]
            },
            "name": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "name",
                  "store": true,
                  "type": "text"
                }
              ]
            },
            "style": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "style",
                  "store": true,
                  "type": "text"
                }
              ]
            },
            "updated": {
              "dynamic": false,
              "enabled": true,
              "fields": [
                {
                  "docvalues": true,
                  "include_in_all": true,
                  "include_term_vectors": true,
                  "index": true,
                  "name": "updated",
                  "store": true,
                  "type": "datetime"
                }
              ]
            }
          }
        }
      }
    },
    "store": {
      "indexType": "scorch"
    }
  },
  "sourceParams": {}
}'
*/

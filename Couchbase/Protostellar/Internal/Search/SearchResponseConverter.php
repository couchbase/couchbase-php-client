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

namespace Couchbase\Protostellar\Internal\Search;

use Couchbase\Protostellar\Generated\Search\V1\SearchQueryResponse\DateRangeFacetResult;
use Couchbase\Protostellar\Generated\Search\V1\SearchQueryResponse\MetaData;
use Couchbase\Protostellar\Generated\Search\V1\SearchQueryResponse\NumericRangeFacetResult;
use Couchbase\Protostellar\Generated\Search\V1\SearchQueryResponse\TermFacetResult;
use Couchbase\Protostellar\Internal\SharedUtils;

class SearchResponseConverter
{
    public static function convertSearchResult(array $response): array
    {
        $finalArray = [];
        foreach ($response as $result) {
            $finalArray["rows"][] = self::convertHits(SharedUtils::toArray($result->getHits()));
            $finalArray["facets"][] = self::convertFacetResult(SharedUtils::toAssociativeArray($result->getFacets()));
            if ($result->hasMetadata()) {
                $finalArray["meta"] = self::convertMetadata($result->getMetadata());
            }
        }
        $finalArray["rows"] = call_user_func_array('array_merge', $finalArray["rows"]);
        $finalArray["facets"] = call_user_func_array('array_merge', $finalArray["facets"]);
        return $finalArray;
    }

    private static function convertFacetResult(array $facets): array
    {
        $facetRes = [];
        foreach ($facets as $name => $facet) {
            if ($facet->hasTermFacet()) {
                $facetRes[] = self::convertTermFacetResult($name, $facet->getTermFacet());
            } elseif ($facet->hasDateRangeFacet()) {
                $facetRes[] = self::convertDateRangeFacetResult($name, $facet->getDateRangeFacet());
            } elseif ($facet->hasNumericRangeFacet()) {
                $facetRes[] = self::convertNumericRangeFacetResult($name, $facet->getNumericRangeFacet());
            }
        }
        return $facetRes;
    }

    private static function convertTermFacetResult(string $name, TermFacetResult $facetResult): array
    {
        $processTerms = function (array $termResult): array {
            $result = [];
            foreach ($termResult as $term) {
                $termArr = [];
                $termArr["term"] = $term->getName();
                $termArr["count"] = $term->getSize();
                $result[] = $termArr;
            }
            return $result;
        };

        $facet = [];
        $facet["name"] = $name;
        $facet["field"] = $facetResult->getField();
        $facet["total"] = $facetResult->getTotal();
        $facet["missing"] = $facetResult->getMissing();
        $facet["other"] = $facetResult->getOther();
        $facet["terms"] = $processTerms(SharedUtils::toArray($facetResult->getTerms()));
        return $facet;
    }

    private static function convertDateRangeFacetResult(string $name, DateRangeFacetResult $facetResult): array
    {
        $processDateRange = function (array $dateRangeResult): array {
            $result = [];
            foreach ($dateRangeResult as $dateRange) {
                $dateRangeArr = [];
                $dateRangeArr["name"] = $dateRange->getName();
                $dateRangeArr["count"] = $dateRange->getSize();
                if ($dateRange->hasStart()) {
                    $dateRangeArr["start"] = date(DATE_RFC3339, intval($dateRange->getStart()->getSeconds()));
                }
                if ($dateRange->hasEnd()) {
                    $dateRangeArr["end"] = date(DATE_RFC3339, intval($dateRange->getEnd()->getSeconds()));
                }
                $result[] = $dateRangeArr;
            }
            return $result;
        };

        $facet = [];
        $facet["name"] = $name;
        $facet["field"] = $facetResult->getField();
        $facet["total"] = $facetResult->getTotal();
        $facet["missing"] = $facetResult->getMissing();
        $facet["other"] = $facetResult->getOther();
        $facet["dateRanges"] = $processDateRange(SharedUtils::toArray($facetResult->getDateRanges()));
        return $facet;
    }

    private static function convertNumericRangeFacetResult(string $name, NumericRangeFacetResult $facetResult): array
    {
        $processNumericRange = function (array $numericRanges): array {
            $result = [];
            foreach ($numericRanges as $numericRange) {
                $numericRangeArr = [];
                $numericRangeArr["name"] = $numericRange->getName();
                $numericRangeArr["count"] = $numericRange->getSize();
                $numericRangeArr["min"] = $numericRange->getMin();
                $numericRangeArr["max"] = $numericRange->getMax();
                $result[] = $numericRangeArr;
            }
            return $result;
        };

        $facet = [];
        $facet["name"] = $name;
        $facet["field"] = $facetResult->getField();
        $facet["total"] = $facetResult->getTotal();
        $facet["missing"] = $facetResult->getMissing();
        $facet["other"] = $facetResult->getOther();
        $facet["numericRanges"] = $processNumericRange(SharedUtils::toArray($facetResult->getNumericRanges()));
        return $facet;
    }

    private static function convertMetadata(MetaData $metadata): array
    {
        $meta = [];
        $meta["clientContextId"] = ""; //ClientContextId not returned from STG
        $metrics = $metadata->getMetrics();
        $mericsArr = [];
        $mericsArr["tookNanoseconds"] = (intval($metrics->getExecutionTime()->getSeconds()) * 1e9) + $metrics->getExecutionTime()->getNanos();
        $mericsArr["successPartitionCount"] = $metrics->getSuccessPartitionCount();
        $mericsArr["errorPartitionCount"] = $metrics->getErrorPartitionCount();
        $mericsArr["totalRows"] = intval($metrics->getTotalRows());
        $mericsArr["maxScore"] = $metrics->getMaxScore();
        $meta["metrics"] = $mericsArr;
        return $meta;
    }

    private static function convertHits(array $hits): array
    {
        $rows = [];
        foreach ($hits as $hit) {
            $row = [];
            $row['id'] = $hit->getId();
            $row['score'] = $hit->getScore();
            $row['index'] = $hit->getIndex();
            $row['explanation'] = $hit->getExplanation();
            $row['locations'] = self::convertLocations(SharedUtils::toArray($hit->getLocations()));
            $row['fragments'] = self::convertFragments(SharedUtils::toAssociativeArray($hit->getFragments()));
            $row['fields'] = self::convertFields(SharedUtils::toAssociativeArray($hit->getFields()));
            $rows[] = $row;
        }
        return $rows;
    }

    private static function convertFragments(array $fragments): array
    {
        $resArr = [];
        foreach ($fragments as $key => $value) {
            $resArr[$key] = SharedUtils::toArray($value->getContent());
        }
        return $resArr;
    }

    private static function convertFields(array $field): string
    {
        $resArr = [];
        foreach ($field as $key => $value) {
            $resArr[$key] = json_decode($value);
        }
        return json_encode($resArr);
    }

    private static function convertLocations(array $locations): array
    {
        $locationsRes = [];
        foreach ($locations as $location) {
            $locationArr = [];
            $locationArr["field"] = $location->getField();
            $locationArr["term"] = $location->getTerm();
            $locationArr["position"] = $location->getPosition();
            $locationArr["start"] = $location->getStart();
            $locationArr["end"] = $location->getEnd();
            $locationArr["arrayPositions"] = SharedUtils::toArray($location->getArrayPositions());
            $locationsRes[] = $locationArr;
        }
        return $locationsRes;
    }
}

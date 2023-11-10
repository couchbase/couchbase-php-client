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

namespace Couchbase\Protostellar\Internal\Search;

use Couchbase\BooleanFieldSearchQuery;
use Couchbase\BooleanSearchQuery;
use Couchbase\ConjunctionSearchQuery;
use Couchbase\Coordinate;
use Couchbase\DateRangeSearchQuery;
use Couchbase\DisjunctionSearchQuery;
use Couchbase\DocIdSearchQuery;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\GeoBoundingBoxSearchQuery;
use Couchbase\GeoDistanceSearchQuery;
use Couchbase\GeoPolygonQuery;
use Couchbase\MatchAllSearchQuery;
use Couchbase\MatchNoneSearchQuery;
use Couchbase\MatchPhraseSearchQuery;
use Couchbase\MatchSearchQuery;
use Couchbase\NumericRangeSearchQuery;
use Couchbase\PhraseSearchQuery;
use Couchbase\PrefixSearchQuery;
use Couchbase\Protostellar\Generated\Search\V1\BooleanFieldQuery;
use Couchbase\Protostellar\Generated\Search\V1\BooleanQuery;
use Couchbase\Protostellar\Generated\Search\V1\ConjunctionQuery;
use Couchbase\Protostellar\Generated\Search\V1\DateRange;
use Couchbase\Protostellar\Generated\Search\V1\DateRangeFacet;
use Couchbase\Protostellar\Generated\Search\V1\DateRangeQuery;
use Couchbase\Protostellar\Generated\Search\V1\DisjunctionQuery;
use Couchbase\Protostellar\Generated\Search\V1\DocIdQuery;
use Couchbase\Protostellar\Generated\Search\V1\Facet;
use Couchbase\Protostellar\Generated\Search\V1\FieldSorting;
use Couchbase\Protostellar\Generated\Search\V1\GeoBoundingBoxQuery;
use Couchbase\Protostellar\Generated\Search\V1\GeoDistanceQuery;
use Couchbase\Protostellar\Generated\Search\V1\GeoDistanceSorting;
use Couchbase\Protostellar\Generated\Search\V1\IdSorting;
use Couchbase\Protostellar\Generated\Search\V1\LatLng;
use Couchbase\Protostellar\Generated\Search\V1\MatchAllQuery;
use Couchbase\Protostellar\Generated\Search\V1\MatchNoneQuery;
use Couchbase\Protostellar\Generated\Search\V1\MatchPhraseQuery;
use Couchbase\Protostellar\Generated\Search\V1\MatchQuery;
use Couchbase\Protostellar\Generated\Search\V1\NumericRange;
use Couchbase\Protostellar\Generated\Search\V1\NumericRangeFacet;
use Couchbase\Protostellar\Generated\Search\V1\NumericRangeQuery;
use Couchbase\Protostellar\Generated\Search\V1\PhraseQuery;
use Couchbase\Protostellar\Generated\Search\V1\PrefixQuery;
use Couchbase\Protostellar\Generated\Search\V1\Query;
use Couchbase\Protostellar\Generated\Search\V1\QueryStringQuery;
use Couchbase\Protostellar\Generated\Search\V1\RegexpQuery;
use Couchbase\Protostellar\Generated\Search\V1\ScoreSorting;
use Couchbase\Protostellar\Generated\Search\V1\SearchQueryRequest;
use Couchbase\Protostellar\Generated\Search\V1\SearchQueryRequest\HighlightStyle;
use Couchbase\Protostellar\Generated\Search\V1\Sorting;
use Couchbase\Protostellar\Generated\Search\V1\TermFacet;
use Couchbase\Protostellar\Generated\Search\V1\TermQuery;
use Couchbase\Protostellar\Generated\Search\V1\TermRangeQuery;
use Couchbase\Protostellar\Generated\Search\V1\WildcardQuery;
use Couchbase\QueryStringSearchQuery;
use Couchbase\RegexpSearchQuery;
use Couchbase\SearchHighlightMode;
use Couchbase\SearchQuery;
use Couchbase\SearchSortMissing;
use Couchbase\TermRangeSearchQuery;
use Couchbase\TermSearchQuery;
use Couchbase\WildcardSearchQuery;
use stdClass;

class SearchRequestConverter
{
    /**
     * @throws InvalidArgumentException
     */
    public static function getSearchRequest(string $indexName, SearchQuery $query, array $exportedOptions): SearchQueryRequest
    {
        $request = [
            "index_name" => $indexName,
            "query" => self::handleQuery($query)
        ];
        //TODO: Scan consistency
        if (isset($exportedOptions["limit"])) {
            $request['limit'] = $exportedOptions["limit"];
        }
        if (isset($exportedOptions["skip"])) {
            $request["skip"] = $exportedOptions["skip"];
        }
        if (isset($exportedOptions["explain"])) {
            $request["include_explanation"] = $exportedOptions["explain"];
        }
        if (isset($exportedOptions["highlightStyle"])) {
            $request["highlight_style"] = self::convertStyle($exportedOptions["highlightStyle"]);
        }
        if (isset($exportedOptions["highlightFields"])) {
            $request["highlight_fields"] = $exportedOptions["highlightFields"];
        }
        if (isset($exportedOptions["fields"])) {
            $request["fields"] = $exportedOptions["fields"];
        }
        if (isset($exportedOptions["sortSpecs"])) {
            $request["sort"] = self::convertSort($exportedOptions["sortSpecs"]);
        }
        if (isset($exportedOptions["disableScoring"])) {
            $request["disable_scoring"] = $exportedOptions["disableScoring"];
        }
        if (isset($exportedOptions["collections"])) {
            $request["collections"] = $exportedOptions["collections"];
        }
        if (isset($exportedOptions["includeLocations"])) {
            $request["include_locations"] = $exportedOptions["includeLocations"];
        }
        if (isset($exportedOptions["facets"])) {
            $request["facets"] = self::convertFacets($exportedOptions["facets"]);
        }

        return new SearchQueryRequest($request);
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function handleQuery(SearchQuery $query): Query
    {
        switch (true) {
            case $query instanceof BooleanFieldSearchQuery:
                return self::getBooleanFieldQuery($query);
            case $query instanceof BooleanSearchQuery:
                return self::getBooleanQuery($query);
            case $query instanceof ConjunctionSearchQuery:
                return self::getConjunctionQuery($query);
            case $query instanceof DateRangeSearchQuery:
                return self::getDateRangeQuery($query);
            case $query instanceof DisjunctionSearchQuery:
                return self::getDisjunctionQuery($query);
            case $query instanceof DocIdSearchQuery:
                return self::getDocIdQuery($query);
            case $query instanceof GeoBoundingBoxSearchQuery:
                return self::getGeoBoundingBoxQuery($query);
            case $query instanceof GeoDistanceSearchQuery:
                return self::getGeoDistanceQuery($query);
            case $query instanceof GeoPolygonQuery:
                return self::getGeoPolygonQuery($query);
            case $query instanceof MatchAllSearchQuery:
                return self::getMatchAllQuery($query);
            case $query instanceof MatchNoneSearchQuery:
                return self::getMatchNoneQuery($query);
            case $query instanceof MatchPhraseSearchQuery:
                return self::getMatchPhraseQuery($query);
            case $query instanceof MatchSearchQuery:
                return self::getMatchQuery($query);
            case $query instanceof NumericRangeSearchQuery:
                return self::getNumericRangeQuery($query);
            case $query instanceof PhraseSearchQuery:
                return self::getPhraseQuery($query);
            case $query instanceof PrefixSearchQuery:
                return self::getPrefixQuery($query);
            case $query instanceof QueryStringSearchQuery:
                return self::getQueryStringQuery($query);
            case $query instanceof RegexpSearchQuery:
                return self::getRegexpQuery($query);
            case $query instanceof TermSearchQuery:
                return self::getTermQuery($query);
            case $query instanceof TermRangeSearchQuery:
                return self::getTermRangeQuery($query);
            case $query instanceof WildcardSearchQuery:
                return self::getWildcardQuery($query);
            default:
                throw new InvalidArgumentException("Unknown search query type");
        }
    }

    private static function getBooleanFieldQuery(BooleanFieldSearchQuery $query): Query
    {
        $exported = BooleanFieldSearchQuery::export($query);
        $queryArr = [
            "value" => $exported['bool']
        ];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        return new Query(
            [
            "boolean_field_query" => new BooleanFieldQuery($queryArr)
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function getBooleanQuery(BooleanSearchQuery $query): Query
    {
        $exported = BooleanSearchQuery::export($query);
        $min = self::getShouldMin($exported['should']);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["must"])) {
            $queryArr["must"] = self::handleQuery($exported["must"]);
        }
        if (isset($exported["must_not"])) {
            $queryArr["must_not"] = self::handleQuery($exported["must_not"]);
        }
        if (isset($exported["should"])) {
            $queryArr["should"] = self::handleQuery($exported["should"]);
        }
        if ($min) {
            $queryArr["should_min"] = $min;
        }
        return new Query(
            [
                "boolean_query" => new BooleanQuery($queryArr)
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function getConjunctionQuery(ConjunctionSearchQuery $query): Query
    {
        $exported = ConjunctionSearchQuery::export($query);
        $queryArr = [];
        $conjunctQueries = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        foreach ($exported['conjuncts'] as $arrayQuery) {
            $conjunctQueries[] = self::handleQuery($arrayQuery);
        }
        $queryArr["queries"] = $conjunctQueries;
        return new Query(
            [
                "conjunction_query" => new ConjunctionQuery($queryArr)
            ]
        );
    }

    private static function getDateRangeQuery(DateRangeSearchQuery $query): Query
    {
        $exported = DateRangeSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        if (isset($exported["datetime_parser"])) {
            $queryArr["date_time_parser"] = $exported["datetime_parser"];
        }
        if (isset($exported["start"])) {
            $queryArr["start_date"] = $exported["start"];
        }
        if (isset($exported["end"])) {
            $queryArr["end_date"] = $exported["end"];
        }
        return new Query(
            [
                "date_range_query" => new DateRangeQuery($queryArr)
            ]
        );
    }

    private static function getDisjunctionQuery(DisjunctionSearchQuery $query): Query
    {
        $exported = DisjunctionSearchQuery::export($query);
        $queryArr = [];
        $disjunctQueries = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["min"])) {
            $queryArr["minimum"] = $exported["min"];
        }
        foreach ($exported['disjuncts'] as $arrayQuery) {
            $disjunctQueries[] = self::handleQuery($arrayQuery);
        }
        $queryArr["queries"] = $disjunctQueries;
        return new Query(
            [
                "disjunction_query" => new DisjunctionQuery($queryArr)
            ]
        );
    }

    private static function getDocIdQuery(DocIdSearchQuery $query): Query
    {
        $exported = DocIdSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        $queryArr["ids"] = $exported["ids"];
        return new Query(
            [
                "doc_id_query" => new DocIdQuery($queryArr)
            ]
        );
    }

    private static function getGeoBoundingBoxQuery(GeoBoundingBoxSearchQuery $query): Query
    {
        $exported = GeoBoundingBoxSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        $queryArr["top_left"] = self::getLatLng($exported["top_left"][0], $exported["top_left"][1]);
        $queryArr["bottom_right"] = self::getLatLng($exported["bottom_right"][0], $exported["bottom_right"][1]);
        return new Query(
            [
                "geo_bounding_box_query" => new GeoBoundingBoxQuery($queryArr)
            ]
        );
    }

    private static function getGeoDistanceQuery(GeoDistanceSearchQuery $query): Query
    {
        $exported = GeoDistanceSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        $queryArr["center"] = self::getLatLng($exported["location"][0], $exported["location"][1]);
        $queryArr["distance"] = $exported["distance"];
        return new Query(
            [
                "geo_distance_query" => new GeoDistanceQuery($queryArr)
            ]
        );
    }

    private static function getGeoPolygonQuery(GeoPolygonQuery $query): Query
    {
        $exported = GeoPolygonQuery::export($query);
        $queryArr = [];
        $vertices = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        foreach ($exported["polygon_points"] as $point) {
            $vertices[] = self::getLatLng(...Coordinate::export($point));
        }
        $queryArr["vertices"] = $vertices;
        return new Query(
            [
                "geo_polygon_query" => new GeoPolygonQuery($queryArr)
            ]
        );
    }

    private static function getMatchAllQuery(MatchAllSearchQuery $query): Query
    {
        return new Query(
            [
               "match_all_query" => new MatchAllQuery()
            ]
        );
    }

    private static function getMatchNoneQuery(MatchNoneSearchQuery $query): Query
    {
        return new Query(
            [
                "match_none_query" => new MatchNoneQuery()
            ]
        );
    }

    private static function getMatchPhraseQuery(MatchPhraseSearchQuery $query): Query
    {
        $exported = MatchPhraseSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        if (isset($exported["analyzer"])) {
            $queryArr["analyzer"] = $exported["analyzer"];
        }
        $queryArr["phrase"] = $exported["match_phrase"];
        return new Query(
            [
                "match_phrase_query" => new MatchPhraseQuery($queryArr)
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function getMatchQuery(MatchSearchQuery $query): Query
    {
        $exported = MatchSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        if (isset($exported["analyzer"])) {
            $queryArr["analyzer"] = $exported["analyzer"];
        }
        if (isset($exported["fuzziness"])) {
            $queryArr["fuzziness"] = $exported["fuzziness"];
        }
        if (isset($exported["operator"])) {
            $queryArr["operator"] = self::getOperator($exported['operator']);
        }
        if (isset($exported["prefix_length"])) {
            $queryArr["prefix_length"] = $exported["prefix_length"];
        }
        $queryArr["value"] = $exported["match"];
        return new Query(
            [
                'match_query' => new MatchQuery($queryArr)
            ]
        );
    }

    private static function getNumericRangeQuery(NumericRangeSearchQuery $query): Query
    {
        $exported = NumericRangeSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        if (isset($exported["min"])) {
            $queryArr["min"] = $exported["min"];
        }
        if (isset($exported["max"])) {
            $queryArr["max"] = $exported["max"];
        }
        if (isset($exported["inclusive_min"])) {
            $queryArr["inclusive_min"] = $exported["inclusive_min"];
        }
        if (isset($exported["inclusive_max"])) {
            $queryArr["inclusive_max"] = $exported["inclusive_max"];
        }
        return new Query(
            [
                "numeric_range_query" => new NumericRangeQuery($queryArr)
            ]
        );
    }

    private static function getPhraseQuery(PhraseSearchQuery $query): Query
    {
        $exported = PhraseSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        $queryArr["terms"] = $exported["terms"];
        return new Query(
            [
                "phrase_query" => new PhraseQuery($queryArr)
            ]
        );
    }

    private static function getPrefixQuery(PrefixSearchQuery $query): Query
    {
        $exported = PrefixSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        $queryArr["prefix"] = $exported["prefix"];
        return new Query(
            [
                "prefix_query" => new PrefixQuery($queryArr)
            ]
        );
    }

    private static function getQueryStringQuery(QueryStringSearchQuery $query): Query
    {
        $exported = QueryStringSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) { // TODO: QueryStringQuery does not have "field"
            $queryArr["field"] = $exported["field"];
        }
        $queryArr["query_string"] = $exported["query"];
        return new Query(
            [
                "query_string_query" => new QueryStringQuery($queryArr)
            ]
        );
    }

    private static function getRegexpQuery(RegexpSearchQuery $query): Query
    {
        $exported = RegexpSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        $queryArr["regexp"] = $exported["regexp"];
        return new Query(
            [
                "regexp_query" => new RegexpQuery($queryArr)
            ]
        );
    }

    private static function getTermQuery(TermSearchQuery $query): Query
    {
        $exported = TermSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        if (isset($exported["fuzziness"])) {
            $queryArr["fuzziness"] = $exported["fuzziness"];
        }
        if (isset($exported["prefix_length"])) {
            $queryArr["prefix_length"] = $exported["prefix_length"];
        }
        $queryArr["term"] = $exported["term"];
        return new Query(
            [
                "term_query" => new TermQuery($queryArr)
            ]
        );
    }

    private static function getTermRangeQuery(TermRangeSearchQuery $query): Query
    {
        $exported = TermRangeSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        if (isset($exported["min"])) {
            $queryArr["min"] = $exported["min"];
        }
        if (isset($exported["max"])) {
            $queryArr["max"] = $exported["max"];
        }
        if (isset($exported["inclusive_min"])) {
            $queryArr["inclusive_min"] = $exported["inclusive_min"];
        }
        if (isset($exported["inclusive_max"])) {
            $queryArr["inclusive_max"] = $exported["inclusive_max"];
        }
        if (isset($exported["term"])) { // TODO: term is not part of a TermRangeQuery
            $queryArr["term"] = $exported["term"];
        }
        if (isset($exported["fuzziness"])) { // TODO: fuzziness is not part of TermRangeQuery
            $queryArr["fuzziness"] = $exported["fuzziness"];
        }
        return new Query(
            [
                "term_range_query" => new TermRangeQuery($queryArr)
            ]
        );
    }

    private static function getWildcardQuery(WildcardSearchQuery $query): Query
    {
        $exported = WildcardSearchQuery::export($query);
        $queryArr = [];
        if (isset($exported["boost"])) {
            $queryArr["boost"] = $exported["boost"];
        }
        if (isset($exported["field"])) {
            $queryArr["field"] = $exported["field"];
        }
        $queryArr["wildcard"] = $exported["wildcard"];
        return new Query(
            [
                'wildcard_query' => new WildcardQuery($queryArr)
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function convertStyle(string $style): int
    {
        switch ($style) {
            case SearchHighlightMode::SIMPLE:
                return HighlightStyle::HIGHLIGHT_STYLE_DEFAULT;
            case SearchHighlightMode::HTML:
                return HighlightStyle::HIGHLIGHT_STYLE_HTML;
            case SearchHighlightMode::ANSI:
                return HighlightStyle::HIGHLIGHT_STYLE_ANSI;
            default:
                throw new InvalidArgumentException("Unknown Search Highlight style");
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function convertSort(array $encodedSort): array
    {
        $sort = [];
        $decodedJSON = [];
        foreach ($encodedSort as $specs) {
            $decodedJSON[] = json_decode($specs);
        }
        foreach ($decodedJSON as $item) {
            if (gettype($item) == "string") {
                $sort[] = self::convertSimpleStringSort($item);
            } else {
                switch ($item->by) {
                    case "id":
                        $sort[] = self::convertIdSort($item);
                        break;
                    case "field":
                        $sort[] = self::convertFieldSort($item);
                        break;
                    case "geo_distance":
                        $sort[] = self::convertGeoDistanceSort($item);
                        break;
                    case "score":
                        $sort[] = self::convertScoreSort($item);
                        break;
                    default:
                        throw new InvalidArgumentException("Invalid FTS Search Sort type");
                }
            }
        }
        return $sort;
    }

    private static function convertSimpleStringSort(string $field): Sorting
    {
        $data = [];
        if (substr($field, 0, 1) == "-") {
            $data["descending"] = true;
            $data["field"] = substr($field, 1);
        } else {
            $data["descending"] = false;
            $data["field"] = $field;
        }
        $fieldSorting = new FieldSorting($data);
        return new Sorting(["field_sorting" => $fieldSorting]);
    }

    private static function convertIdSort(stdClass $spec): Sorting
    {
        $data = [];
        if (property_exists($spec, "desc")) {
            $data["descending"] = $spec->desc;
        }
        $idSorting = new IdSorting($data);
        return new Sorting(["id_sorting" => $idSorting]);
    }

    private static function convertFieldSort(stdClass $spec): Sorting
    {
        $data = [];
        $data["field"] = $spec->field;
        if (property_exists($spec, "desc")) {
            $data["descending"] = $spec->desc;
        }
        if (property_exists($spec, "type")) {
            $data["type"] = $spec->type;
        }
        if (property_exists($spec, "mode")) {
            $data["mode"] = $spec->mode;
        }
        if (property_exists($spec, "missing")) {
            $data["missing"] = $spec->missing;
        }
        $fieldSorting = new FieldSorting($data);
        return new Sorting(["field_sorting" => $fieldSorting]);
    }

    private static function convertGeoDistanceSort(stdClass $spec): Sorting
    {
        $data = [];
        $data["field"] = $spec->field;
        $data["center"] = self::getLatLng($spec->location[0], $spec->location[1]);
        if (property_exists($spec, "desc")) {
            $data["descending"] = $spec->desc;
        }
        if (property_exists($spec, "unit")) {
            $data["unit"] = $spec->unit;
        }
        $geoDistanceSort = new GeoDistanceSorting($data);
        return new Sorting(["geo_distance_sorting" => $geoDistanceSort]);
    }

    private static function convertScoreSort(stdClass $spec): Sorting
    {
        $data = [];
        if (property_exists($spec, "desc")) {
            $data["descending"] = $spec->desc;
        }
        $scoreSorting = new ScoreSorting($data);
        return new Sorting(["score_sorting" => $scoreSorting]);
    }

    private static function convertFacets(array $facets): array
    {
        $finalRes = [];
        $decodedFacets = [];
        foreach ($facets as $name => $facet) {
            $decodedFacets[$name] = json_decode($facet);
        }
        foreach ($decodedFacets as $name => $facet) {
            if (property_exists($facet, "date_ranges")) {
                $finalRes[$name] = self::handleDateRangeFacet($facet);
            } elseif (property_exists($facet, "numeric_ranges")) {
                $finalRes[$name] = self::handleNumericRangeFacet($facet);
            } else {
                $finalRes[$name] = self::handleTermFacet($facet);
            }
        }
        return $finalRes;
    }

    private static function handleDateRangeFacet(stdClass $facet): Facet
    {
        $dateRanges = [];
        foreach ($facet->date_ranges as $range) {
            $rangeData = [];
            $rangeData["name"] = $range->name;
            if (property_exists($range, "start")) {
                $rangeData["start"] = $range->start;
            }
            if (property_exists($range, "end")) {
                $rangeData["end"] = $range->end;
            }
            $dateRanges[] = new DateRange($rangeData);
        }
        $dateRangeFacet = new DateRangeFacet(
            [
                "field" => $facet->field,
                "size" => $facet->size,
                "date_ranges" => $dateRanges
            ]
        );
        return new Facet(["date_range_facet" => $dateRangeFacet]);
    }

    public static function handleNumericRangeFacet(stdClass $facet): Facet
    {
        $numericRanges = [];
        foreach ($facet->numeric_ranges as $range) {
            $rangeData = [];
            $rangeData["name"] = $range->name;
            if (property_exists($range, "min")) {
                $rangeData["min"] = $range->min;
            }
            if (property_exists($range, "max")) {
                $rangeData["max"] = $range->max;
            }
            $numericRanges[] = new NumericRange($rangeData);
        }
        $numericRangeFacet = new NumericRangeFacet(
            [
                "field" => $facet->field,
                "size" => $facet->size,
                "numeric_ranges" => $numericRanges
            ]
        );
        return new Facet(["numeric_range_facet" => $numericRangeFacet]);
    }

    public static function handleTermFacet(stdClass $facet): Facet
    {
        $termFacet = new TermFacet(
            [
                "field" => $facet->field,
                "size" => $facet->size
            ]
        );
        return new Facet(["term_facet" => $termFacet]);
    }

    /**
     * @throws InvalidArgumentException
     **/
    private static function getOperator(string $operator): int
    {
        switch ($operator) {
            case MatchSearchQuery::OPERATOR_AND:
                return MatchQuery\Operator::OPERATOR_AND;
            case MatchSearchQuery::OPERATOR_OR:
                return MatchQuery\Operator::OPERATOR_OR;
            default:
                throw new InvalidArgumentException("Unknown Match Query Operator");
        }
    }

    private static function getLatLng(float $long, float $lat): LatLng
    {
        return new LatLng(
            [
                "latitude" => $lat,
                "longitude" => $long
            ]
        );
    }

    private static function getShouldMin(DisjunctionSearchQuery $query): ?int
    {
        $exported = DisjunctionSearchQuery::export($query);
        if (isset($exported["min"])) {
            return intval($exported["min"]);
        }
        return null;
    }
}

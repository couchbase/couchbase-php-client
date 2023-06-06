<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/search/v1/search.proto

namespace GPBMetadata\Couchbase\Search\V1;

class Search
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Protobuf\Duration::initOnce();
        \GPBMetadata\Google\Protobuf\Timestamp::initOnce();
        $pool->internalAddGeneratedFile(
            '
ŬG
 couchbase/search/v1/search.protocouchbase.search.v1google/protobuf/timestamp.proto"^
BooleanFieldQuery
boost (H 
field (	H
value (B
_boostB
_field"
BooleanQuery
boost (H 8
must (2%.couchbase.search.v1.ConjunctionQueryH<
must_not (2%.couchbase.search.v1.DisjunctionQueryH:
should (2%.couchbase.search.v1.DisjunctionQueryHB
_boostB
_mustB
	_must_notB	
_should"]
ConjunctionQuery
boost (H +
queries (2.couchbase.search.v1.QueryB
_boost"Ì
DateRangeQuery
boost (H 
field (	H
date_time_parser (	H

start_date (	H
end_date (	HB
_boostB
_fieldB
_date_time_parserB
_start_dateB
	_end_date"
DisjunctionQuery
boost (H +
queries (2.couchbase.search.v1.Query
minimum (HB
_boostB

_minimum"7

DocIdQuery
boost (H 
ids (	B
_boost"-
LatLng
latitude (
	longitude ("³
GeoBoundingBoxQuery
boost (H 
field (	H-
top_left (2.couchbase.search.v1.LatLng1
bottom_right (2.couchbase.search.v1.LatLngB
_boostB
_field"
GeoDistanceQuery
boost (H 
field (	H+
center (2.couchbase.search.v1.LatLng
distance (	B
_boostB
_field"|
GeoPolygonQuery
boost (H 
field (	H-
vertices (2.couchbase.search.v1.LatLngB
_boostB
_field"
MatchAllQuery"
MatchNoneQuery"
MatchPhraseQuery
boost (H 
field (	H
phrase (	
analyzer (	HB
_boostB
_fieldB
	_analyzer"Ì

MatchQuery
boost (H 
field (	H
value (	
analyzer (	H
	fuzziness (H?
operator (2(.couchbase.search.v1.MatchQuery.OperatorH
prefix_length (H"-
Operator
OPERATOR_OR 
OPERATOR_ANDB
_boostB
_fieldB
	_analyzerB

_fuzzinessB
	_operatorB
_prefix_length"ß
NumericRangeQuery
boost (H 
field (	H
min (H
max (H
inclusive_min (H
inclusive_max (HB
_boostB
_fieldB
_minB
_maxB
_inclusive_minB
_inclusive_max"X
PhraseQuery
boost (H 
field (	H
terms (	B
_boostB
_field"Y
PrefixQuery
boost (H 
field (	H
prefix (	B
_boostB
_field"F
QueryStringQuery
boost (H 
query_string (	B
_boost"Y
RegexpQuery
boost (H 
field (	H
regexp (	B
_boostB
_field"İ
	TermQuery
boost (H 
field (	H
term (	
	fuzziness (H
prefix_length (HB
_boostB
_fieldB

_fuzzinessB
_prefix_length"Ü
TermRangeQuery
boost (H 
field (	H
min (	H
max (	H
inclusive_min (H
inclusive_max (HB
_boostB
_fieldB
_minB
_maxB
_inclusive_minB
_inclusive_max"]
WildcardQuery
boost (H 
field (	H
wildcard (	B
_boostB
_field"Ġ

QueryE
boolean_field_query (2&.couchbase.search.v1.BooleanFieldQueryH :
boolean_query (2!.couchbase.search.v1.BooleanQueryH B
conjunction_query (2%.couchbase.search.v1.ConjunctionQueryH ?
date_range_query (2#.couchbase.search.v1.DateRangeQueryH B
disjunction_query (2%.couchbase.search.v1.DisjunctionQueryH 7
doc_id_query (2.couchbase.search.v1.DocIdQueryH J
geo_bounding_box_query (2(.couchbase.search.v1.GeoBoundingBoxQueryH C
geo_distance_query (2%.couchbase.search.v1.GeoDistanceQueryH A
geo_polygon_query	 (2$.couchbase.search.v1.GeoPolygonQueryH =
match_all_query
 (2".couchbase.search.v1.MatchAllQueryH ?
match_none_query (2#.couchbase.search.v1.MatchNoneQueryH C
match_phrase_query (2%.couchbase.search.v1.MatchPhraseQueryH 6
match_query (2.couchbase.search.v1.MatchQueryH E
numeric_range_query (2&.couchbase.search.v1.NumericRangeQueryH 8
phrase_query (2 .couchbase.search.v1.PhraseQueryH 8
prefix_query (2 .couchbase.search.v1.PrefixQueryH C
query_string_query (2%.couchbase.search.v1.QueryStringQueryH 8
regexp_query (2 .couchbase.search.v1.RegexpQueryH 4

term_query (2.couchbase.search.v1.TermQueryH ?
term_range_query (2#.couchbase.search.v1.TermRangeQueryH <
wildcard_query (2".couchbase.search.v1.WildcardQueryH B
query"^
FieldSorting
field (	

descending (
missing (	
mode (	
type (	"r
GeoDistanceSorting
field (	

descending (+
center (2.couchbase.search.v1.LatLng
unit (	"
	IdSorting

descending (""
ScoreSorting

descending ("
Sorting:
field_sorting (2!.couchbase.search.v1.FieldSortingH G
geo_distance_sorting (2\'.couchbase.search.v1.GeoDistanceSortingH 4

id_sorting (2.couchbase.search.v1.IdSortingH :
score_sorting (2!.couchbase.search.v1.ScoreSortingH B	
sorting"Q
	DateRange
name (	
start (	H 
end (	HB
_startB
_end"b
DateRangeFacet
field (	
size (3
date_ranges (2.couchbase.search.v1.DateRange"P
NumericRange
name (	
min (H 
max (HB
_minB
_max"k
NumericRangeFacet
field (	
size (9
numeric_ranges (2!.couchbase.search.v1.NumericRange"(
	TermFacet
field (	
size ("Î
Facet?
date_range_facet (2#.couchbase.search.v1.DateRangeFacetH E
numeric_range_facet (2&.couchbase.search.v1.NumericRangeFacetH 4

term_facet (2.couchbase.search.v1.TermFacetH B
facet"Ê
SearchQueryRequest

index_name (	)
query (2.couchbase.search.v1.QueryQ
scan_consistency (27.couchbase.search.v1.SearchQueryRequest.ScanConsistency
limit (
skip (
include_explanation (O
highlight_style (26.couchbase.search.v1.SearchQueryRequest.HighlightStyle
highlight_fields (	
fields	 (	*
sort
 (2.couchbase.search.v1.Sorting
disable_scoring (
collections (	
include_locations (C
facets (23.couchbase.search.v1.SearchQueryRequest.FacetsEntry
bucket_name (	H 

scope_name (	HI
FacetsEntry
key (	)
value (2.couchbase.search.v1.Facet:8"3
ScanConsistency 
SCAN_CONSISTENCY_NOT_BOUNDED "a
HighlightStyle
HIGHLIGHT_STYLE_DEFAULT 
HIGHLIGHT_STYLE_HTML
HIGHLIGHT_STYLE_ANSIB
_bucket_nameB
_scope_name"
SearchQueryResponseE
hits (27.couchbase.search.v1.SearchQueryResponse.SearchQueryRowD
facets (24.couchbase.search.v1.SearchQueryResponse.FacetsEntryI
	meta_data (21.couchbase.search.v1.SearchQueryResponse.MetaDataH Ù
SearchQueryRow

id (	
score (
index (	
explanation (D
	locations (21.couchbase.search.v1.SearchQueryResponse.LocationY
	fragments (2F.couchbase.search.v1.SearchQueryResponse.SearchQueryRow.FragmentsEntryS
fields (2C.couchbase.search.v1.SearchQueryResponse.SearchQueryRow.FieldsEntryc
FragmentsEntry
key (	@
value (21.couchbase.search.v1.SearchQueryResponse.Fragment:8-
FieldsEntry
key (	
value (:8n
Location
field (	
term (	
position (
start (
end (
array_positions (
Fragment
content (	İ
FacetResultN

term_facet (28.couchbase.search.v1.SearchQueryResponse.TermFacetResultH Y
date_range_facet (2=.couchbase.search.v1.SearchQueryResponse.DateRangeFacetResultH _
numeric_range_facet (2@.couchbase.search.v1.SearchQueryResponse.NumericRangeFacetResultH B
search_facet7

TermResult
name (	
field (	
size (
TermFacetResult
field (	
total (
missing (
other (B
terms (23.couchbase.search.v1.SearchQueryResponse.TermResultJ
DateRangeResult
name (	
size ()
start (2.google.protobuf.Timestamp\'
end (2.google.protobuf.Timestampİ
DateRangeFacetResult
field (	
total (
missing (
other (M
date_ranges (28.couchbase.search.v1.SearchQueryResponse.DateRangeResultJJ
NumericRangeResult
name (	
size (
min (
max (²
NumericRangeFacetResult
field (	
total (
missing (
other (S
numeric_ranges (2;.couchbase.search.v1.SearchQueryResponse.NumericRangeResultJÑ
MetaDataG
metrics (26.couchbase.search.v1.SearchQueryResponse.SearchMetricsM
errors (2=.couchbase.search.v1.SearchQueryResponse.MetaData.ErrorsEntry-
ErrorsEntry
key (	
value (	:8È
SearchMetrics1
execution_time (2.google.protobuf.Duration

total_rows (
	max_score (
total_partition_count (
success_partition_count (
error_partition_count (c
FacetsEntry
key (	C
value (24.couchbase.search.v1.SearchQueryResponse.FacetResult:8B

_meta_data2u
SearchServiced
SearchQuery\'.couchbase.search.v1.SearchQueryRequest(.couchbase.search.v1.SearchQueryResponse" 0BÏ
+com.couchbase.client.protostellar.search.v1PZ@github.com/couchbase/goprotostellar/genproto/search_v1;search_v1Ê*Couchbase\\Protostellar\\Generated\\Search\\V1ê.Couchbase::Protostellar::Generated::Search::V1bproto3'
        , true);

        static::$is_initialized = true;
    }
}


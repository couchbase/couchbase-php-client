<?php

// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\Protostellar\Generated\Search\V1;

/**
 */
class SearchClient extends \Grpc\BaseStub
{
    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null)
    {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Search\V1\SearchQueryRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function SearchQuery(
        \Couchbase\Protostellar\Generated\Search\V1\SearchQueryRequest $argument,
                                                                       $metadata = [],
                                                                       $options = []
    )
    {
        return $this->_serverStreamRequest(
            '/couchbase.search.v1.Search/SearchQuery',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Search\V1\SearchQueryResponse', 'decode'],
            $metadata,
            $options
        );
    }
}

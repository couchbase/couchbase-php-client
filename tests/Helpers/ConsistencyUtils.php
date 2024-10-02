<?php

namespace Helpers;

use Exception;

const PORTS = [
    'mgmt' => 8091,
    'views' => 8092,
    'n1ql' => 8093,
    'fts' => 8094,
];

class ConsistencyUtils
{
    private string $hostname;
    private string $basicAuthString;
    private array $nodes;

    public function __construct(string $hostname, string $auth)
    {
        $this->hostname = $hostname;
        $this->basicAuthString = 'Authorization: Basic ' . base64_encode($auth);
    }

    public function resourceIsPresent(int $statusCode): bool
    {
        return $statusCode == 200;
    }

    public function resourceIsDropped(int $statusCode): bool
    {
        return $statusCode = 404;
    }

    /**
     * @throws Exception
     */
    public function waitUntilUserPresent(string $userName, string $domain = 'local'): void
    {
        fprintf(STDERR, "waiting until user %s present on all nodes\n", $userName);
        $this->waitUntilAllNodesMatchPredicate(
            "/settings/rbac/users/$domain/$userName",
            [$this, 'resourceIsPresent'],
            "User $userName is not present on all nodes",
            true,
            'mgmt'
        );
    }

    /**
     * @throws Exception
     */
    public function waitUntilUserDropped(string $userName, string $domain = 'local'): void
    {
        fprintf(STDERR, "waiting until user %s dropped on all nodes\n", $userName);
        $this->waitUntilAllNodesMatchPredicate(
            "/settings/rbac/users/$domain/$userName",
            [$this, 'resourceIsDropped'],
            "User $userName is not dropped on all nodes",
            true,
            'mgmt'
        );
    }

    /**
     * @throws Exception
     */
    public function waitUntilGroupPresent(string $groupName): void
    {
        fprintf(STDERR, "waiting until group %s present on all nodes\n", $groupName);
        $this->waitUntilAllNodesMatchPredicate(
            "/settings/rbac/groups/$groupName",
            [$this, 'resourceIsPresent'],
            "Group $groupName is not present on all nodes",
            true,
            'mgmt'
        );
    }

    public function waitUntilGroupDropped(string $groupName): void
    {
        fprintf(STDERR, "waiting until group %s dropped on all nodes\n", $groupName);
        $this->waitUntilAllNodesMatchPredicate(
            "/settings/rbac/groups/$groupName",
            [$this, 'resourceIsDropped'],
            "Group $groupName is not dropped on all nodes",
            true,
            'mgmt'
        );
    }

    public function waitUntilBucketPresent(string $bucketName): void
    {
        fprintf(STDERR, "waiting until bucket %s present on all nodes\n", $bucketName);
        $this->waitUntilAllNodesMatchPredicate(
            "/pools/default/buckets/$bucketName",
            [$this, 'resourceIsPresent'],
            "Bucket $bucketName is not present on all nodes",
            true,
            'mgmt'
        );
    }

    public function waitUntilBucketDropped(string $bucketName): void
    {
        fprintf(STDERR, "waiting until bucket %s dropped on all nodes\n", $bucketName);
        $this->waitUntilAllNodesMatchPredicate(
            "/pools/default/buckets/$bucketName",
            [$this, 'resourceIsDropped'],
            "Bucket $bucketName is not dropped on all nodes",
            true,
            'mgmt'
        );
    }

    public function waitUntilDesignDocumentPresent(string $bucketName, string $designDocumentName): void
    {
        fprintf(STDERR, "waiting until design document %s present on all nodes\n", $designDocumentName);
        $this->waitUntilAllNodesMatchPredicate(
            "/{$bucketName}/_design/$designDocumentName",
            [$this, 'resourceIsPresent'],
            "Design document $designDocumentName on bucket $bucketName is not present on all nodes",
            true,
            'views'
        );
    }

    public function waitUntilDesignDocumentDropped(string $bucketName, string $designDocumentName): void
    {
        fprintf(STDERR, "waiting until design document %s dropped on all nodes\n", $designDocumentName);
        $this->waitUntilAllNodesMatchPredicate(
            "/$bucketName/_design/$designDocumentName",
            [$this, 'resourceIsDropped'],
            "Design document $designDocumentName on bucket $bucketName is not dropped on all nodes",
            true,
            'views'
        );
    }

    public function waitUntilViewPresent(string $bucketName, string $designDocumentName, string $viewName): void
    {
        fprintf(STDERR, "waiting until view %s present on all nodes\n", $viewName);
        $this->waitUntilAllNodesMatchPredicate(
            "/$bucketName/_design/$designDocumentName/_view/$viewName",
            [$this, 'resourceIsPresent'],
            "View $viewName on design document $designDocumentName on bucket $bucketName is not present on all nodes",
            true,
            'views'
        );
    }

    public function waitUntilViewDropped(string $bucketName, string $designDocumentName, string $viewName): void
    {
        fprintf(STDERR, "waiting until view %s dropped on all nodes\n", $viewName);
        $this->waitUntilAllNodesMatchPredicate(
            "/$bucketName/_design/$designDocumentName/_view/$viewName",
            [$this, 'resourceIsDropped'],
            "View $viewName on design document $designDocumentName on bucket $bucketName is not dropped on all nodes",
            true,
            'views'
        );
    }

    public function waitUntilScopePresent(string $bucketName, string $scopeName): void
    {
        fprintf(STDERR, "waiting until scope %s present on all nodes\n", $scopeName);
        $scopePresent = function ($response) use ($scopeName) {
            foreach ($response->scopes as $scope) {
                if ($scope->name == $scopeName) {
                    return true;
                }
            }
            return false;
        };

        $this->waitUntilAllNodesMatchPredicate(
            "/pools/default/buckets/$bucketName/scopes",
            $scopePresent,
            "Scope $scopeName on bucket $bucketName is not present on all nodes",
            false,
            'mgmt'
        );
    }

    public function waitUntilScopeDropped(string $bucketName, string $scopeName): void
    {
        fprintf(STDERR, "waiting until scope %s dropped on all nodes\n", $scopeName);
        $scopeDropped = function ($response) use ($scopeName) {
            foreach ($response->scopes as $scope) {
                if ($scope->name == $scopeName) {
                    return false;
                }
            }
            return true;
        };

        $this->waitUntilAllNodesMatchPredicate(
            "/pools/default/buckets/$bucketName/scopes",
            $scopeDropped,
            "Scope $scopeName on bucket $bucketName is not dropped on all nodes",
            false,
            'mgmt'
        );
    }

    public function waitUntilCollectionPresent(string $bucketName, string $scopeName, string $collectionName): void
    {
        fprintf(STDERR, "waiting until collection %s present on all nodes\n", $collectionName);
        $collectionPresent = function ($response) use ($scopeName, $collectionName) {
            foreach ($response->scopes as $scope) {
                if ($scope->name == $scopeName) {
                    foreach ($scope->collections as $collection) {
                        if ($collection->name == $collectionName) {
                            return true;
                        }
                    }
                }
            }
            return false;
        };

        $this->waitUntilAllNodesMatchPredicate(
            "/pools/default/buckets/$bucketName/scopes",
            $collectionPresent,
            "Collection $collectionName on scope $scopeName on bucket $bucketName is not present on all nodes",
            false,
            'mgmt'
        );
    }

    public function waitUntilCollectionDropped(string $bucketName, string $scopeName, string $collectionName): void
    {
        fprintf(STDERR, "waiting until collection %s dropped on all nodes\n", $collectionName);
        $collectionDropped = function ($response) use ($scopeName, $collectionName) {
            foreach ($response->scopes as $scope) {
                if ($scope->name == $scopeName) {
                    foreach ($scope->collections as $collection) {
                        if ($collection->name == $collectionName) {
                            return false;
                        }
                    }
                }
            }
            return true;
        };

        $this->waitUntilAllNodesMatchPredicate(
            "/pools/default/buckets/$bucketName/scopes",
            $collectionDropped,
            "Collection $collectionName on scope $scopeName on bucket $bucketName is not dropped on all nodes",
            false,
            'mgmt'
        );
    }

    public function waitUntilBucketUpdated(string $bucketName, callable $predicate, string $errorMsg = null): void
    {
        fprintf(STDERR, "waiting until bucket %s has been updated\n", $bucketName);

        $this->waitUntilAllNodesMatchPredicate(
            "/pools/default/buckets/$bucketName",
            $predicate,
            $errorMsg ?? "Bucket $bucketName has not been updated on all nodes",
            false,
            'mgmt'
        );
    }

    public function waitUntilCollectionUpdated(string $bucketName, string $scopeName, string $collectionName, callable $predicate, string $errorMsg = null): void
    {
        fprintf(STDERR, "waiting until collection %s on scope %s on bucket %s has updated\n", $collectionName, $scopeName, $bucketName);

        $this->waitUntilAllNodesMatchPredicate(
            "/pools/default/buckets/$bucketName/scopes",
            $predicate,
            $errorMsg ?? "Collection $collectionName has not been updated on all nodes",
            false,
            'mgmt'
        );
    }

    /**
     * @throws Exception
     */
    public function waitUntilAllNodesMatchPredicate(string $path, callable $predicate, string $errorMsg, bool $onlyStatusCode, string $service, array $request = []): void
    {
        try {
            $deadline = $this->currentTimeMillis() + 200_000;
            while ($this->currentTimeMillis() < $deadline) {
                $predicateMatched = $this->allNodesMatchPredicate(
                    $path,
                    $predicate,
                    $onlyStatusCode,
                    $service,
                    $request
                );
                if ($predicateMatched) {
                    return;
                }
                usleep(100_000);
            }
            throw new Exception("Timed out waiting for nodes to match predicate");
        } catch (Exception $e) {
            throw new Exception(sprintf("%s - %s", $errorMsg, $e->getMessage()));
        }
    }

    /**
     * @throws Exception
     */
    public function waitForConfig(bool $isMock): void
    {
        if ($isMock) {
            $this->nodes = [];
            return;
        }

        $start = $this->currentTimeMillis();
        while (true) {
            try {
                $config = $this->getConfig();

                if (sizeof($config) != 0) {
                    $this->nodes = $config;
                    return;
                }
                usleep(10_000);
            } catch (Exception $e) {
                fprintf(STDERR, "Ignoring error waiting for config: %s", $e->getMessage());
            }
            if (($this->currentTimeMillis() - $start) > 2000) {
                throw new Exception("Timeout waiting for config");
            }
        }
    }

    /**
     * @throws Exception
     */
    public function waitUntilQueryIndexReady(string $bucketName, string $indexName, bool $isPrimary): void
    {
        fprintf(
            STDERR,
            "waiting until query index \"%s\" (is_primary=%s) of bucket \"%s\" present on all nodes\n",
            $indexName,
            $isPrimary ? "true" : "false",
            $bucketName
        );
        $indexPresent = function ($response) use ($bucketName, $indexName, $isPrimary) {
            foreach ($response->results as $result) {
                $index = $result->indexes;

                if (
                    $index->state == "online" &&
                    $index->keyspace_id == $bucketName &&
                    $index->name == $indexName &&
                    (isset($index->is_primary) && $index->is_primary) == $isPrimary
                ) {
                    return true;
                }
            }
            return false;
        };


        $this->waitUntilAllNodesMatchPredicate(
            "/query/service",
            $indexPresent,
            sprintf("index %s (primary=%s) is not present on all nodes\n", $indexName, $isPrimary ? "true" : "false"),
            false,
            "n1ql",
            [
                'method' => 'POST',
                'header'  => ['Content-type: application/json'],
                'content' => json_encode(
                    [
                    'statement' => 'SELECT * FROM system:indexes'
                    ]
                )
            ]
        );
    }

    /**
     * @throws Exception
     */
    private function allNodesMatchPredicate(string $path, callable $predicate, bool $onlyStatusCode, string $service, array $request): bool
    {
        foreach ($this->nodes as $hostname => $services) {
            if ($service != "mgmt" && $service != "views") {
                $found = false;
                foreach ($services as $name) {
                    if ($service == $name) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    continue;
                }
            }
            $url = "http://" . $hostname . ":" . PORTS[$service] . $path;
            $response = $this->runHttpRequest($url, $request, $onlyStatusCode);
            $predicateMatched = $predicate($response);
            if (!$predicateMatched) {
                return false;
            }
        }
        return true;
    }

    /**
     * @throws Exception
     */
    private function runHttpRequest(string $url, array $request, bool $onlyStatusCode)
    {
        $headers = $request['header'] ?? [];
        unset($request['header']);
        $opts = array('http' =>
            array_merge(
                [
                'method' => 'GET',
                'header' => array_merge([$this->basicAuthString], $headers),
                'ignore_errors' => true,
                ],
                $request
            )
        );
        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        $statusCode = $this->extractStatusCode($http_response_header[0]);
        if ($onlyStatusCode) {
            return $statusCode;
        } elseif ($statusCode != 200) {
            throw new Exception(sprintf("Non 200 status code from response (%d).\n%s", $statusCode, $response));
        }
        return json_decode($response);
    }

    /**
     * @throws Exception
     */
    private function getConfig(): array
    {
        $url = "http://" . $this->hostname . ":8091/pools/nodes";
        $opts = array('http' =>
            array(
                'method' => 'GET',
                'header' => $this->basicAuthString,
                'ignore_errors' => true,
            )
        );
        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        $statusCode = $this->extractStatusCode($http_response_header[0]);
        if ($statusCode != 200) {
            throw new Exception(sprintf("statusCode: %s", $statusCode));
        }
        $nodeData = json_decode($response);
        $nodeInfo = [];
        foreach ($nodeData->nodes as $node) {
            $nodeInfo[explode(":", $node->configuredHostname)[0]] = $node->services;
        }
        return $nodeInfo;
    }

    private function extractStatusCode($statusLine): int
    {
        preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
        $status = $match[1];
        return intval($status);
    }

    private function currentTimeMillis(): float
    {
        return floor(microtime(true) * 1000);
    }
}

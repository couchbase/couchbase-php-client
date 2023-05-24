<?php

use Couchbase\ClusterOptions;
use Couchbase\DurabilityLevel;
use Couchbase\GetOptions;
use Couchbase\UpsertOptions;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\TimeoutHandler;
use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";

class TimeoutsTest extends CouchbaseTestCaseProtostellar
{
    public function testSettingClusterOptionsGivesRightTimeout()
    {
        $clusterOptions = new ClusterOptions();
        $clusterOptions->authenticator(self::env()->buildPasswordAuthenticator());
        $clusterOptions->keyValueTimeout(6000);
        $client = new Client(self::env()->connectionString(), $clusterOptions);
        $exportedOptions = GetOptions::export(new GetOptions());
        $timeout = $client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $this->assertEquals(6e6, $timeout);
    }
    public function testSettingNoTimeoutGetsDefault()
    {
        $client = $this->getDefaultClient();
        $exportedOptions = GetOptions::export(new GetOptions());
        $timeout = $client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $this->assertEquals(2.5e6, $timeout);
    }

    public function testDurableTimeout()
    {
        $clusterOptions = new ClusterOptions();
        $clusterOptions->authenticator(self::env()->buildPasswordAuthenticator());
        $clusterOptions->keyValueTimeout(6000);
        $client = new Client(self::env()->connectionString(), $clusterOptions);
        $exportedOptions = UpsertOptions::export(UpsertOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY));
        $timeout = $client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $this->assertEquals(1e7, $timeout);
    }

    public function testDurableTimeoutSetToNone()
    {
        $clusterOptions = new ClusterOptions();
        $clusterOptions->authenticator(self::env()->buildPasswordAuthenticator());
        $clusterOptions->keyValueTimeout(6000);
        $client = new Client(self::env()->connectionString(), $clusterOptions);
        $exportedOptions = UpsertOptions::export(UpsertOptions::build()->durabilityLevel(DurabilityLevel::NONE));
        $timeout = $client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $this->assertEquals(6e6, $timeout);
    }

    public function testOperationTimeoutTakesPrecedence()
    {
        $clusterOptions = new ClusterOptions();
        $clusterOptions->authenticator(self::env()->buildPasswordAuthenticator());
        $clusterOptions->keyValueTimeout(6000);
        $client = new Client(self::env()->connectionString(), $clusterOptions);
        $exportedOptions = UpsertOptions::export(UpsertOptions::build()->timeout(3000));
        $timeout = $client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $this->assertEquals(3e6, $timeout);
    }

    public function testConnectionStringOptions()
    {
        $connString = "protostellar://test_conn_string?kv_timeout=6000";
        $clusterOptions = new ClusterOptions();
        $clusterOptions->authenticator(self::env()->buildPasswordAuthenticator());
        $client = new Client($connString, $clusterOptions);
        $timeout = $client->timeoutHandler()->getTimeout(TimeoutHandler::KV, UpsertOptions::export(UpsertOptions::build()));
        $this->assertEquals(6e6, $timeout);
    }
}

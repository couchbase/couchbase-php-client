<?php

use Couchbase\Cluster;
use Couchbase\ClusterInterface;
use Couchbase\Extension;
use Couchbase\ForkEvent;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class ForkTest extends Helpers\CouchbaseTestCase
{
    private ClusterInterface $cluster;

    public function setUp(): void
    {
        parent::setUp();

        $this->cluster = $this->connectCluster();
    }

    public function testForkWorkflow()
    {
        if (!extension_loaded("pcntl")) {
            $this->markTestSkipped("The 'pcntl' extension require to test Cluster::notifyFork helper");
        }
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $res = $collection->upsert($id, ["answer" => 42]);
        $cas = $res->cas();
        $this->assertNotNull($cas);

        Cluster::notifyFork(ForkEvent::PREPARE);

        $pid = pcntl_fork();
        $this->assertGreaterThanOrEqual(0, $pid);
        if ($pid == 0) {
            Cluster::notifyFork(ForkEvent::CHILD);
            $res = $collection->get($id);
            $this->assertEquals($cas, $res->cas());
            exit(0);
        } else {
            Cluster::notifyFork(ForkEvent::PARENT);
            $res = $collection->get($id);
            $this->assertEquals($cas, $res->cas());
        }
    }
}

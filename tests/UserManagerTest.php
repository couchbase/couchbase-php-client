<?php

use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\Exception\AuthenticationFailureException;
use Couchbase\Exception\GroupNotFoundException;
use Couchbase\Exception\UserNotFoundException;
use Couchbase\Management\AuthDomain;
use Couchbase\Management\Group;
use Couchbase\Management\Origin;
use Couchbase\Management\Role;
use Couchbase\Management\RoleAndDescription;
use Couchbase\Management\RoleAndOrigin;
use Couchbase\Management\User;
use Couchbase\Management\UserAndMetadata;
use Couchbase\Management\UserManagerInterface;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class UserManagerTest extends Helpers\CouchbaseTestCase
{
    private UserManagerInterface $manager;

    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfProtostellar();

        $this->manager = $this->connectCluster()->users();
    }

    public function waitForGroupCreated(string $groupName)
    {
        $seenNewGroup = 0;

        while ($seenNewGroup < 10) {
            try {
                return $this->manager->getGroup($groupName);
            } catch (Exception $ex) {
                usleep(100000);
                continue;
            }
            $seenNewGroup += 1;
        }
    }

    public function waitForUserCreated(string $userName)
    {
        $seenNewUser = 0;

        while ($seenNewUser < 10) {
            try {
                return $this->manager->getUser($userName);
            } catch (Exception $ex) {
                usleep(100000);
                continue;
            }
            $seenNewUser += 1;
        }
    }

    public function testGetRoles()
    {
        // Caves doesn't include display name and description for roles.
        $this->skipIfCaves();

        $roles = $this->manager->getRoles();
        $this->assertGreaterThan(0, count($roles));
        /** @var RoleAndDescription $roleAndDesc */
        foreach ($roles as $roleAndDesc) {
            if ($roleAndDesc->role()->name() == "query_manage_index") {
                $this->assertNotEmpty($roleAndDesc->description());
                $this->assertEquals("Query Manage Index", $roleAndDesc->displayName());
                $role = $roleAndDesc->role();
                $this->assertEquals("*", $role->bucket());
                if ($this->version()->supportsCollections()) {
                    $this->assertEquals("*", $role->scope());
                    $this->assertEquals("*", $role->collection());
                }
                break;
            }
        }
    }

    protected function assertGroup(Group $result, string $groupName, string $desc)
    {
        $this->assertEquals($groupName, $result->name());
        $this->assertEquals($desc, $result->description());
        $roles = $result->roles();
        $this->assertGreaterThan(0, count($roles));

        /** @var Role $role */
        foreach ($roles as $role) {
            switch ($role->name()) {
                case 'bucket_full_access':
                case 'bucket_admin':
                    if ($role->bucket() != 'travel-sample' && $role->bucket() != self::env()->bucketName()) {
                        $this->fail(sprintf('wrong bucket "%s" for group role "%s"', $role->bucket(), $role->name()));
                    }
                    break;
                default:
                    $this->fail(sprintf('unknown group role "%s"', $role->name()));
            }
        }
    }

    public function testGroups()
    {
        $this->skipIfCaves();
        $groupName = $this->uniqueId('test');
        $desc = 'Users who have full access to sample buckets';
        $defaultBucket = self::env()->bucketName();
        $group = Group::build()->setName($groupName)->setDescription($desc)->setRoles(
            [
                Role::build()->setName('bucket_admin')->setBucket('travel-sample'),
                Role::build()->setName('bucket_full_access')->setBucket('travel-sample'),
                Role::build()->setName('bucket_admin')->setBucket($defaultBucket),
                Role::build()->setName('bucket_full_access')->setBucket($defaultBucket),
            ]
        );

        $this->manager->upsertGroup($group);
        $this->waitForGroupCreated($groupName);

        $result = $this->manager->getGroup($groupName);
        $this->assertGroup($result, $groupName, $desc);
        $this->assertCount(4, $result->roles());

        $result = $this->manager->getAllGroups();
        $this->assertGreaterThan(0, count($result));
        $found = false;
        /** @var Group $role */
        foreach ($result as $group) {
            if ($group->name() == $groupName) {
                $this->assertGroup($group, $groupName, $desc);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        $this->manager->dropGroup($groupName);

        $this->expectException(GroupNotFoundException::class);
        $retry = 10;
        while ($retry > 0) {
            $this->manager->getGroup($groupName);
            $retry -= 1;
            usleep(100000);
        }
    }

    protected function assertUser(UserAndMetadata $result, string $username, string $displayName, Group $group, Role $userRole)
    {
        $this->assertEquals($username, $result->user()->username());
        $this->assertEquals($displayName, $result->user()->displayName());
        $this->assertCount(1, $result->user()->groups());
        $this->assertEquals($group->name(), $result->user()->groups()[0]);
        $this->assertCount(1, $result->user()->roles());
        $this->assertEquals($userRole, $result->user()->roles()[0]);
        $this->assertEquals(AuthDomain::LOCAL, $result->domain());
        $this->assertNotEmpty($result->passwordChanged());
        $this->assertCount(2, $result->effectiveRoles());
        /** @var RoleAndOrigin $effectiveRole */
        foreach ($result->effectiveRoles() as $effectiveRole) {
            $this->assertCount(1, $effectiveRole->origins());
            /** @var Origin $origin */
            $origin = $effectiveRole->origins()[0];
            $role = $effectiveRole->role();
            if ($origin->type() == 'user') {
                $this->assertEquals($userRole, $role);
            } elseif ($origin->type() == 'group') {
                $this->assertEquals($group->roles()[0], $role);
            } else {
                $this->fail(sprintf('unexpcted origin type for role $%s', $origin->type()));
            }
        }
    }

    public function testUsers()
    {
        $this->skipIfCaves();
        $groupName = $this->uniqueId('test');
        $desc = 'Users who have full access to sample buckets';
        $group = Group::build()->setName($groupName)->setDescription($desc)->setRoles(
            [
                Role::build()->setName('bucket_admin')->setBucket('travel-sample'),
            ]
        );

        $this->manager->upsertGroup($group);
        $this->waitForGroupCreated($groupName);

        $result = $this->manager->getGroup($groupName);
        $this->assertGroup($result, $groupName, $desc);

        $role = Role::build()->setName('bucket_full_access')->setBucket('*');

        $username = $this->uniqueId('test');
        $display = 'Guest User';
        $user = User::build()->setUsername($username)->setPassword('secret')->setDisplayName($display)
            ->setGroups([$groupName])->setRoles([$role]);
        $this->manager->upsertUser($user);
        $this->waitForUserCreated($username);

        $result = $this->manager->getUser($username);
        $this->assertUser($result, $username, $display, $group, $role);

        $result = $this->manager->getAllUsers();
        $this->assertGreaterThan(0, count($result));
        $found = false;
        /** @var UserAndMetadata $user */
        foreach ($result as $user) {
            if ($user->user()->username() == $username) {
                $this->assertUser($user, $username, $display, $group, $role);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        $this->manager->dropUser($username);
        $this->expectException(UserNotFoundException::class);
        $retry = 10;
        while ($retry > 0) {
            $this->manager->getUser($username);
            $retry -= 1;
            usleep(100000);
        }
    }

    public function testChangePassword()
    {
        $this->skipIfCaves();
        $role = Role::build()->setName('bucket_full_access')->setBucket('*');

        $username = $this->uniqueId('test');
        $display = 'Test User';
        $user = User::build()->setUsername($username)->setPassword("secret")->setDisplayName($display)->setRoles([$role]);
        $this->manager->upsertUser($user);
        $this->waitForUserCreated($username);

        $options = new ClusterOptions();
        $options->credentials($username, "secret");
        $cluster = $this->retryFor(
            5,
            100,
            function () use ($options) {
                return new Cluster(self::env()->connectionString(), $options);
            },
            "connect using new user with initial password"
        );

        $newOptions = new ClusterOptions();
        $newOptions->credentials($username, "newPassword");
        $this->wrapException(
            function () use ($newOptions) {
                new Cluster(self::env()->connectionString(), $newOptions);
            },
            AuthenticationFailureException::class
        );


        $manager = $cluster->users();
        $manager->changePassword("newPassword");

        $newCluster = $this->retryFor(
            5,
            100,
            function () use ($newOptions) {
                return new Cluster(self::env()->connectionString(), $newOptions);
            },
            "connect using new user with updated password"
        );
        $this->assertNotNull($newCluster);
    }
}

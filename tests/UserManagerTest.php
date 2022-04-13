<?php

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
use Couchbase\Management\UserManager;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class UserManagerTest extends Helpers\CouchbaseTestCase
{
    private UserManager $manager;

    public function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->connectCluster()->users();
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
                $this->assertEquals("*", $role->scope());
                $this->assertEquals("*", $role->collection());
                break;
            }
        }
    }

    function assertGroup(Group $result, string $groupName, string $desc)
    {
        $this->assertEquals($groupName, $result->name());
        $this->assertEquals($desc, $result->description());
        $roles = $result->roles();
        $this->assertCount(4, $roles);

        $this->assertGreaterThan(0, count($roles));
        /** @var Role $role */
        foreach ($roles as $role) {
            switch ($role->name()) {
                case 'bucket_full_access':
                case 'bucket_admin':
                    if (!($role->bucket() == 'travel-sample' || $role->bucket() == 'beer-sample')) {
                        $this->fail(sprintf('wrong bucket for group role $%s', $role->name()));
                    }
                    break;
                default:
                    $this->fail(sprintf('unknown group role $%s', $role->name()));
            }
        }
    }

    public function testGroups()
    {
        $this->skipIfCaves();
        $groupName = $this->uniqueId('test');
        $desc = 'Users who have full access to sample buckets';
        $group = Group::build()->setName($groupName)->setDescription($desc)->setRoles(
            [
            Role::build()->setName('bucket_admin')->setBucket('travel-sample'),
            Role::build()->setName('bucket_full_access')->setBucket('travel-sample'),
            Role::build()->setName('bucket_admin')->setBucket('beer-sample'),
            Role::build()->setName('bucket_full_access')->setBucket('beer-sample'),
            ]
        );
        ;
        $this->manager->upsertGroup($group);

        $result = $this->retryFor(
            5,
            100,
            function () use ($groupName) {
                return $this->manager->getGroup($groupName);
            }
        );
        $this->assertGroup($result, $groupName, $desc);

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
        $this->manager->getGroup($groupName);
    }

    function assertUser(UserAndMetadata $result, string $username, string $displayName, Group $group, Role $userRole)
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
        ;
        $this->manager->upsertGroup($group);

        $role = Role::build()->setName('bucket_full_access')->setBucket('*');

        $username = $this->uniqueId('test');
        $display = 'Guest User';
        $user = User::build()->setUsername($username)->setPassword('secret')->setDisplayName($display)
            ->setGroups([$groupName])->setRoles([$role]);
        $this->manager->upsertUser($user);

        $result = $this->retryFor(
            5,
            100,
            function () use ($username) {
                return $this->manager->getUser($username);
            }
        );

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
        $this->manager->getUser($username);
    }
}

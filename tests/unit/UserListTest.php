<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserList class.
 */
class UserListTest extends TestCase
{
    public function testCreate()
    {
        $userList = new UserList();
        $this->assertNotNull($userList, 'Create test');

        $users = ['user0', 'user1', 'user2'];

        foreach ($users as $user) {
            $userList->addUser($user);
        }
        $this->assertEquals($users, $userList->getUsers(), 'User add test');

        $userList->deleteUser('user1');
        $users = ['user0', 'user2'];
        $this->assertEquals($users, $userList->getUsers(), 'User delete test');

        $userList->addProject('user0', 10);
        $userList->addProject('user0', 20);

        $projects = [10 => 1, 20 => 1];
        $this->assertEquals($projects, $userList->getProjects('user0'), 'Projects test');

        $userList->removeProject('user0', 10);
        $projects = [20 => 1];
        $this->assertEquals($projects, $userList->getProjects('user0'), 'Remove project test');

        $json = $userList->toJson();
        $this->assertMatchesRegularExpression('/user0/', $json, 'JSON user0 test');
        $this->assertMatchesRegularExpression('/user2/', $json, 'JSON user2 test');
        $this->assertMatchesRegularExpression('/20/', 'JSON projec 20 test');

        $userList->fromJson($json);
        $json2 = $userList->toJson();

        $this->assertEquals($json, $json2, 'JSON from/to test');
    }
}

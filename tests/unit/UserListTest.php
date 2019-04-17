<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class UserListTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $module = null;
        $db = null;
        $userList = new UserList();
        $this->assertNotNull($userList);
    }
}

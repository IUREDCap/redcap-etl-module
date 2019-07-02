<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

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

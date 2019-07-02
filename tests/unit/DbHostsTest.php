<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class DbHostsTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $dbHosts = new DbHosts();
        $this->assertNotNull($dbHosts);
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class ServersTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $servers = new Servers();
        $this->assertNotNull($servers);
    }
}

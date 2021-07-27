<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class ServersTest extends TestCase
{
    public function testCreate()
    {
        $servers = new Servers();
        $this->assertNotNull($servers, 'Server created test');
    }

    public function testAddServers()
    {
        $servers = new Servers();
        $servers->addServer('local');

        $serverNames = $servers->getServers();
        $this->assertEquals(2, count($serverNames), 'Add count test');
        $this->assertTrue(in_array(ServerConfig::EMBEDDED_SERVER_NAME, $serverNames), 'Embedded server check');
        $this->assertTrue(in_array('local', $serverNames), 'Local server check');

        $exceptionCaught = false;
        try {
            # try to add an existing server
            $servers->addServer('local');
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Add existing server exception');
    }
}

<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class ServerConfigTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $serverConfig = new ServerConfig('test');
        $this->assertNotNull($serverConfig, 'Object creation test');
    }
}

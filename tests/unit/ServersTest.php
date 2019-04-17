<?php

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

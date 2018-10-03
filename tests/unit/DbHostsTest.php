<?php

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

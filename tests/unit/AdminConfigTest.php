<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class AdminConfigTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $adminConfig = new AdminConfig();
        $this->assertNotNull($adminConfig, 'Object creation test');
    }
}

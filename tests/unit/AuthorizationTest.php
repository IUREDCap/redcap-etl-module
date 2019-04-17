<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $authorization = new Authorization();
        $this->assertNotNull($authorization);
    }
}

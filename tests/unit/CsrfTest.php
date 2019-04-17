<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class CsrfTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $csrf = new Csrf();
        $this->assertNotNull($csrf);
    }
}

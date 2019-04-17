<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class HelpTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $help = new Help();
        $this->assertNotNull($help);
    }
}

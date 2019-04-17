<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class RedCapDbTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $redCapDb = new RedCapDb();
        $this->assertNotNull($redCapDb);
    }
}

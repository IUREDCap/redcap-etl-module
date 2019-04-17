<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $module = null;
        $db = null;
        $settings = new Settings($module, $db);
        $this->assertNotNull($settings);
    }
}

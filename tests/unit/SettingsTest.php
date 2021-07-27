<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    public function testCreate()
    {
        $module = null;
        $db = null;
        $settings = new Settings($module, $db);
        $this->assertNotNull($settings);
    }
}

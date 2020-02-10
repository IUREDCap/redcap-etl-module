<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class RedCapEtlModuleTest extends TestCase
{
    public function testCreate()
    {
        $module = new RedCapEtlModule();
        $this->assertNotNull($module);
    }
}

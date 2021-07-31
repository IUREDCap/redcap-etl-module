<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class WorkflowsTest extends TestCase
{
    public function testCreate()
    {
        $workflows = new Workflows();
        $this->assertNotNull($workflows, 'Object creation test');
    }
}

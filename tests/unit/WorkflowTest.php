<?php

#-------------------------------------------------------
# Copyright (C) 2021 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class WorkflowTest extends TestCase
{
    public function testCreate()
    {
        $workflow = new Workflow('test_user');
        $this->assertNotNull($workflow, 'Object creation test');
    }
}
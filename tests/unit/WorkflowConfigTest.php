<?php

#-------------------------------------------------------
# Copyright (C) 2021 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class WorkflowConfigTest extends TestCase
{
    public function testCreate()
    {
        $workflowConfig = new WorkflowConfig();
        $this->assertNotNull($workflowConfig, 'Object creation test');
    }
}

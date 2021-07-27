<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class ProjectInfoTest extends TestCase
{
    public function testCreate()
    {
        $projectInfo = new ProjectInfo();
        $this->assertNotNull($projectInfo, 'Object creation test');

        $configName = 'config1';
        $this->assertFalse($projectInfo->hasConfigName($configName), 'Initial config name check');

        $projectInfo->addConfigName($configName);
        $this->assertTrue($projectInfo->hasConfigName($configName), 'Has config name check');

        $projectInfo->removeConfigName($configName);
        $this->assertFalse($projectInfo->hasConfigName($configName), 'Remove config name check');
    }
}

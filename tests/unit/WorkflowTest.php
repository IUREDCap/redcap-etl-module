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

    public function testMoves()
    {
        $workflow = new Workflow('test_user');
        $this->assertNotNull($workflow, 'Object creation test');

        $workflow->addProject($projectId = 1, $username = 'user1');
        $workflow->addProject($projectId = 3, $username = 'user3');
        $workflow->addProject($projectId = 2, $username = 'user2');
        $workflow->addProject($projectId = 5, $username = 'user5');
        $workflow->addProject($projectId = 4, $username = 'user4');

        $workflow->moveTaskDown(1);
        $workflow->moveTaskUp(4);
        $actualProjectIds = $workflow->getProjectIds();
        $expectedProjectIds = [1, 2, 3, 4, 5];
        $this->assertEquals($expectedProjectIds, $actualProjectIds, 'Project IDs check');
    }
}

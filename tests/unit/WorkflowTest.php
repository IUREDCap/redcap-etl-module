<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

define('USERID', 'testuser');
define('PROJECT_ID', 123);
define('APP_PATH_WEBROOT_FULL', 'http://localhost/redcap/');

class WorkflowTest extends TestCase
{
    public function testCreate()
    {
        $workflow = new Workflow();
        $this->assertNotNull($workflow, 'Object creation test');
    }
}

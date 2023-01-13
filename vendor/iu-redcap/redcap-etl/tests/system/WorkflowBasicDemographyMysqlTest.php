<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

/**
 * Runs the basic demograhy workflow tests using MySQL as the database.
 */
class WorkflowBasicDemographyMysqlTest extends WorkflowBasicDemographySystemTest
{
    const CONFIG_FILE = __DIR__.'/../config/workflow-basic-demography-mysql.ini';
}

<?php

#-------------------------------------------------------
# Copyright (C) 2020 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

#---------------------------------------------
# Get log details for the specified ETL run
#---------------------------------------------
$module->checkAdminPagePermission();

require_once __DIR__ . '/../../dependencies/autoload.php';

use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\ModuleLog;

$etlRunLogId     = Filter::sanitizeInt($_POST['etl_run_log_id']);

$moduleLog = new ModuleLog($module);

$logInfo = $moduleLog->renderEtlRunDetails($etlRunLogId);

echo Filter::sanitizeLogDetails($logInfo);

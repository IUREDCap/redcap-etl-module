<?php

#-------------------------------------------------------
# Copyright (C) 2020 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

#---------------------------------------------
# Get log details for the specified cron job
#---------------------------------------------
$module->checkAdminPagePermission();

require_once __DIR__ . '/../../dependencies/autoload.php';

use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\ModuleLog;

$cronLogId     = Filter::sanitizeInt($_POST['cron_log_id']);

$moduleLog = new ModuleLog($module);

$logInfo = $moduleLog->renderCronJobs($cronLogId);

echo $logInfo;

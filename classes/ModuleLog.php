<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

class ModuleLog
{
    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }
    
    public function getData()
    {
        $query = "select log_id, timestamp, ui_id, project_id, message, username, cron";
        $logData = $this->module->queryLogs($query);
        return $logData;
    }
}

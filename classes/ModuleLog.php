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
    
    /**
     * Gets the specified log data from the external module logging tables.
     *
     */
    public function getData($type, $startDate = null, $endDate = null)
    {
        
        $query = "select log_id, timestamp, ui_id, project_id, message";
        
        if ($type === RedCapEtlModule::ETL_RUN) {
            $query .= ', log_type, cron, config, etl_username, etl_server';
        }
        $query .= " where log_type = '".Filter::escapeForMysql($type)."'";
        
        #----------------------------------------
        # Query start date condition (if any)
        #----------------------------------------
        if (!empty($startDate)) {
            $startTime = \DateTime::createFromFormat('m/d/Y', $startDate);
            $startTime = $startTime->format('Y-m-d');
            $query .= " and timestamp >= '".Filter::escapeForMysql($startTime)."'";
        }
        
        #---------------------------------------
        # Query end date condition (if any)
        #---------------------------------------
        if (!empty($endDate)) {
            $endTime = \DateTime::createFromFormat('m/d/Y', $endDate);
            $endTime->modify('+1 day');
            $endTime = $endTime->format('Y-m-d');
            $query .= " and timestamp < '".Filter::escapeForMysql($endTime)."'";
        }
        
        $logData = $this->module->queryLogs($query);
        return $logData;
    }
}

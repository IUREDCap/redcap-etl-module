<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

/**
 * Logging class that uses REDCap's built-in logging tables for external modules.
 */
class ModuleLog
{
    const LOG_FORMAT_VERSION = 1.0;

    # log type constants
    const ETL_CRON        = 'ETL cron';   # Cron jobs run
    const ETL_CRON_JOB    = 'ETL cron job';
    const ETL_RUN         = 'ETL run';
    const ETL_RUN_DETAILS = 'ETL run details';

    private $module;

    private $lastEtlRunLogId;

    public function __construct($module)
    {
        $this->module = $module;
    }


    /**
     * Logs a cron jobs run, indicating how many crom jobs were run.
     *
     * @param int $day the day (0-6) for which the cron job count should be logged.
     *
     * @return int the log ID for the logged cron jobs run.
     */
    public function logCronJobsRun($numCronJobs, $day, $hour)
    {
        $logParams = [
            'log_type'           => self::ETL_CRON,
            'log_format_version' => self::LOG_FORMAT_VERSION,
            'cron_day'           => $day,
            'cron_hour'          => $hour,
            'num_jobs'           => $numCronJobs
        ];

        $logMessage = 'REDCap-ETL cron jobs run';

        $logId = $this->module->log($logMessage, $logParams);

        return $logId;
    }

    public function logCronJob($projectId, $serverName, $configName, $logId)
    {
        $logParams = [
            'log_type'           => self::ETL_CRON_JOB,
            'log_format_version' => self::LOG_FORMAT_VERSION,
            'project_id'         => $projectId,
            'etl_server'         => $serverName,
            'config'             => $configName,
            'cron_log_id'        => $logId
        ];

        $logMessage = 'REDCap-ETL cron job';

        $this->module->log($logMessage, $logParams);
    }
    

    /**
     * Logs high-level run information to external module log.
     *
     * @return int log ID for the generated log entry
     */
    public function logEtlRun($projectId, $username, $isCronJob, $configName, $serverName)
    {
        $logParams = [
            'log_type'           => self::ETL_RUN,
            'log_format_version' => self::LOG_FORMAT_VERSION,
            'project_id'         => $projectId,
            'etl_username'       => $username,
            'cron'               => $isCronJob,
            'config'             => $configName,
            'etl_server'         => $serverName
        ];

        $logMessage = "ETL run";

        $logId = $this->module->log($logMessage, $logParams);
        $this->lastEtlRunLogId = $logId;

        return $logId;
    }

    /**
     * Log a message from an ETL run. This method is intended for
     * use as a callback method for the logger for the embeded server.
     */
    public function logEtlRunMessage($logMessage)
    {
        $logParams = [
            'log_type'           => self::ETL_RUN_DETAILS,
            'log_format_version' => self::LOG_FORMAT_VERSION,
            'etl_run_log_id'     => $this->lastEtlRunLogId
        ];

        $this->module->log($logMessage, $logParams);
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
        } elseif ($type === RedCapEtlModule::ETL_CRON) {
            $query .= ', log_type, cron_day, cron_hour, num_jobs';
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

    /**
     * Gets the cron jobs associated with the specified cron run log ID.
     */
    public function getCronJobs($logId)
    {
        $query = "select log_id, timestamp, ui_id, project_id, message, log_type, etl_server, config, cron_log_id";
        $query .= " where log_type = '".RedCapEtlModule::ETL_CRON_JOB."'"
            ." and cron_log_id = '".Filter::escapeForMysql($logId)."'";
        $cronJob = $this->module->queryLogs($query);
        return $cronJob;
    }
    
        
    public function renderCronJobs($logId)
    {
        $cronJobs = '';
        $cronJobs .= '<h4>Cron Jobs</h4>'."\n";
        $cronJobs .= '<table class="etl-log">'."\n";
        $cronJobs .= "<thead>\n";
        $cronJobs .= "<tr><th>Log ID</th><th>Cron Log ID</th><th>Server</th><th>Config</th><th>Project ID</th></tr>\n";
        $cronJobs .= "</thead>\n";
        $cronJobs .= "<tbody>\n";
        $cronJobsData = $this->getCronJobs($logId);
        
        $tableRows = '';
        foreach ($cronJobsData as $job) {
            $row = "<tr>";
            $row .= '<td style="text-align: right;">'.Filter::sanitizeInt($job['log_id'])."</td>";
            $row .= '<td style="text-align: right;">'.Filter::sanitizeInt($job['cron_log_id'])."</td>";
            $row .= "<td>".Filter::sanitizeString($job['etl_server'])."</td>";
            $row .= "<td>".Filter::sanitizeString($job['config'])."</td>";
            $row .= '<td style="text-align: right;">'.Filter::sanitizeString($job['project_id'])."</td>";
            $row .= "</tr>\n";
            $tableRows .= $row;
        }
        
        $cronJobs .= $tableRows;
        $cronJobs .= "</tbody>\n";
        $cronJobs .= "</table>\n";
        
        return $cronJobs;
    }
}

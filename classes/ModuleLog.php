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
    const WORKFLOW_RUN    = 'workflow run';
    const WORKFLOW_CRON_JOB    = 'workflow_cron_job';


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

    public function logCronJob($projectId, $serverName, $configName, $cronLogId)
    {
        $logParams = [
            'log_type'           => self::ETL_CRON_JOB,
            'log_format_version' => self::LOG_FORMAT_VERSION,
            'project_id'         => $projectId,
            'etl_server'         => $serverName,
            'config'             => $configName,
            'cron_log_id'        => $cronLogId
        ];

        $logMessage = 'REDCap-ETL cron job';

        $logId = $this->module->log($logMessage, $logParams);

        return $logId;
    }
    

    /**
     * Logs high-level run information to external module log.
     *
     * @return int log ID for the generated log entry
     */
    public function logEtlRun(
        $projectId,
        $username,
        $isCronJob,
        $configName,
        $serverName,
        $cronJobLogId = '',
        $cronDay = null,
        $cronHour = null
    ) {
        $logParams = [
            'log_type'           => self::ETL_RUN,
            'log_format_version' => self::LOG_FORMAT_VERSION,
            'project_id'         => $projectId,
            'etl_username'       => $username,
            'cron'               => $isCronJob,
            'cron_job_log_id'    => $cronJobLogId,
            'cron_day'           => $cronDay,
            'cron_hour'          => $cronHour,
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
        
        if ($type === self::ETL_RUN) {
            $query .= ', log_type, cron, config, etl_username, etl_server, cron_job_log_id, cron_day, cron_hour';
            $query .= " where (log_type = '" . Filter::escapeForMysql($type) . "'";
            $query .= " or log_type = '" . self::WORKFLOW_RUN . "')";
        } elseif ($type === self::ETL_CRON) {
            $query .= ', log_type, cron_day, cron_hour, num_jobs';
            $query .= " where log_type = '" . Filter::escapeForMysql($type) . "'";
        }
        #$query .= " where log_type = '" . Filter::escapeForMysql($type) . "'";
        
        #----------------------------------------
        # Query start date condition (if any)
        #----------------------------------------
        if (!empty($startDate)) {
            $startTime = \DateTime::createFromFormat('m/d/Y', $startDate);
            $startTime = $startTime->format('Y-m-d');
            $query .= " and timestamp >= '" . Filter::escapeForMysql($startTime) . "'";
            #$query .= " where timestamp >= '" . Filter::escapeForMysql($startTime) . "'";
        }
        
        #---------------------------------------
        # Query end date condition (if any)
        #---------------------------------------
        if (!empty($endDate)) {
            $endTime = \DateTime::createFromFormat('m/d/Y', $endDate);
            $endTime->modify('+1 day');
            $endTime = $endTime->format('Y-m-d');
            $query .= " and timestamp < '" . Filter::escapeForMysql($endTime) . "'";
        }
        
        $logData = $this->module->queryLogs($query);
        return $logData;
    }
    
    
    public function generateCsvDownload($logType, $startDate, $endDate)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Pragma: no-cache');
        header('Expires: 0');

        if ($logType === self::ETL_RUN) {
            header('Content-Disposition: attachment; filename=redcap-etl-runs.csv');
                    
            $header = [
                'Log ID', 'Time', 'Project ID', 'Server', 'Config',
                'User ID', 'Username', 'Cron?', 'Cron Day', 'Cron Hour'
            ];
            
            $out = fopen('php://output', 'w');
            fputcsv($out, ['REDCap-ETL Processes']);
            fputcsv($out, ['']);
            fputcsv($out, ['Start Date', $startDate]);
            fputcsv($out, ['End Date', $endDate]);
            fputcsv($out, ['']);
                                    
            fputcsv($out, $header);

            $entries = $this->getData(self::ETL_RUN, $startDate, $endDate);
            foreach ($entries as $entry) {
                $i = 0;
                $csvEntry = array();
                $csvEntry[$i++] = $entry['log_id'];
                $csvEntry[$i++] = $entry['timestamp'];
                $csvEntry[$i++] = $entry['project_id'];
                $csvEntry[$i++] = $entry['etl_server'];
                $csvEntry[$i++] = $entry['config'];
                $csvEntry[$i++] = $entry['ui_id'];
                $csvEntry[$i++] = $entry['etl_username'];
                
                if ($entry['cron']) {
                    $csvEntry[$i++] = 'yes';
                } else {
                    $csvEntry[$i++] = 'no';
                }
                
                $csvEntry[$i++] = $entry['cron_day'];
                $csvEntry[$i++] = $entry['cron_hour'];
                fputcsv($out, $csvEntry);
            }
            fclose($out);
        } elseif ($logType === self::ETL_CRON) {
            header('Content-Disposition: attachment; filename=redcap-etl-cron-jobs.csv');
            
            $header = [
                'Log ID', 'Time', 'Cron Day', 'Cron Hour', 'Jobs'
            ];
            $out = fopen('php://output', 'w');
            fputcsv($out, ['REDCap-ETL Cron Jobs']);
            fputcsv($out, ['']);
            fputcsv($out, ['Start Date', $startDate]);
            fputcsv($out, ['End Date', $endDate]);
            fputcsv($out, ['']);
            
            fputcsv($out, $header);
            
            $entries = $this->getData(self::ETL_CRON, $startDate, $endDate);
            foreach ($entries as $entry) {
                $i = 0;
                $csvEntry = array();
                $csvEntry[$i++] = $entry['log_id'];
                $csvEntry[$i++] = $entry['timestamp'];
                $csvEntry[$i++] = $entry['cron_day'];
                $csvEntry[$i++] = $entry['cron_hour'];
                $csvEntry[$i++] = $entry['num_jobs'];
                fputcsv($out, $csvEntry);
            }
            fclose($out);
        }
    }

    /**
     * Gets the cron jobs associated with the specified cron run log ID.
     */
    public function getCronJobs($logId)
    {
        $query = "select log_id, timestamp, ui_id, project_id, message, log_type, etl_server, config, cron_log_id";
        $query .= " where log_type = '" . self::ETL_CRON_JOB . "'"
            . " and cron_log_id = '" . Filter::escapeForMysql($logId) . "'";
        $cronJob = $this->module->queryLogs($query);
        return $cronJob;
    }
    
    /**
     * Gets the log id from the ETL run of a cron job, given the Cron log ID.
     */
    public function getEtlRunLogIdForCronJob($cronLogId)
    {
        $query = "select log_id";
        $query .= " where log_type = '" . self::ETL_RUN . "'"
            . " and cron_log_id = '" . Filter::escapeForMysql($cronLogId) . "'";
        $result = $this->module->queryLogs($query);
        
        $logId = null;
        if (array_key_exists('log_id', $result)) {
            $logId = $result['log_id'];
        }
        return $logId;
    }

    /**
     * Gets the details for the ETL run with specified log ID (but this only works for ETL processes
     * run on the embedded server).
     */
    public function getEtlRunDetails($etlRunLogId)
    {
        $query = "select log_id, timestamp, ui_id, project_id, message, log_type, etl_server, config, etl_run_log_id";
        $query .= " where log_type = '" . self::ETL_RUN_DETAILS . "'"
            . " and etl_run_log_id = '" . Filter::escapeForMysql($etlRunLogId) . "'";
        $etlRunDetails = $this->module->queryLogs($query);
        return $etlRunDetails;
    }
    
    public function renderCronJobs($logId)
    {
        $cronJobs = '';
        $cronJobs .= '<h4>Cron Jobs</h4>' . "\n";
        $cronJobs .= '<table class="etl-log">' . "\n";
        $cronJobs .= "<thead>\n";
        $cronJobs .= "<tr><th>Log ID</th><th>Cron Log ID</th><th>Server</th><th>Config</th><th>Project ID</th></tr>\n";
        $cronJobs .= "</thead>\n";
        $cronJobs .= "<tbody>\n";
        $cronJobsData = $this->getCronJobs($logId);
        
        $tableRows = '';
        foreach ($cronJobsData as $job) {
            $row = "<tr>";
            $row .= '<td style="text-align: right;">' . Filter::sanitizeInt($job['log_id']) . "</td>";
            $row .= '<td style="text-align: right;">' . Filter::sanitizeInt($job['cron_log_id']) . "</td>";
            $row .= "<td>" . Filter::sanitizeString($job['etl_server']) . "</td>";
            $row .= "<td>" . Filter::sanitizeString($job['config']) . "</td>";
            $row .= '<td style="text-align: right;">' . Filter::sanitizeString($job['project_id']) . "</td>";
            
            $row .= "</tr>\n";
            $tableRows .= $row;
        }
        
        $cronJobs .= $tableRows;
        $cronJobs .= "</tbody>\n";
        $cronJobs .= "</table>\n";
        
        return $cronJobs;
    }

    public function renderEtlRunDetails($logId)
    {
        $details = '';
        $details .= '<h4>ETL Run</h4>' . "\n";
        $details .= '<table class="etl-log">' . "\n";
        $details .= "<thead>\n";
        $details .= "<tr><th>Log ID</th><th>Time</th></th><th>Message</th></tr>\n";
        $details .= "</thead>\n";
        $details .= "<tbody>\n";
        $logEntries = $this->getEtlRunDetails($logId);

        $tableRows = '';
        foreach ($logEntries as $logEntry) {
            $row = "<tr>";
            $row .= '<td style="text-align: right;">' . Filter::sanitizeInt($logEntry['log_id']) . "</td>";
            $row .= '<td style="text-align: right; padding: 0px 6px 0px 6px;">'
                . Filter::sanitizeString($logEntry['timestamp']) . "</td>";
            $row .= "<td>" . Filter::sanitizeString($logEntry['message']) . "</td>";
            $row .= "</tr>\n";
            $tableRows .= $row;
        }
        
        $details .= $tableRows;
        $details .= "</tbody>\n";
        $details .= "</table>\n";
        
        return $details;
    }

    public function logWorkflowRun(
        $username,
        $isCronJob,
        $workflowName,
        $serverName,
        $cronJobLogId = '',
        $cronDay = null,
        $cronHour = null
    ) {
        $logParams = [
            'log_type'           => self::WORKFLOW_RUN,
            'log_format_version' => self::LOG_FORMAT_VERSION,
            'project_id'         => null,
            'etl_username'       => $username,
            'cron'               => $isCronJob,
            'cron_job_log_id'    => $cronJobLogId,
            'cron_day'           => $cronDay,
            'cron_hour'          => $cronHour,
            'config'             => 'Workflow '.$workflowName,
            'etl_server'         => $serverName
        ];

        $logMessage = "ETL workflow run";

        $logId = $this->module->log($logMessage, $logParams);
        $this->lastEtlRunLogId = $logId;

        return $logId;
    }

    public function logWorkflowCronJob($workflowName, $serverName, $cronLogId)
    {
        $logParams = [
            'log_type'           => self::WORKFLOW_CRON_JOB,
            'log_format_version' => self::LOG_FORMAT_VERSION,
            'project_id'         => null,
            'etl_server'         => $serverName,
            'config'             => 'Workflow '.$workflowName,
            'cron_log_id'        => $cronLogId
        ];

        $logMessage = 'REDCap-ETL Workflow cron job';

        $logId = $this->module->log($logMessage, $logParams);

        return $logId;
    }
   
}

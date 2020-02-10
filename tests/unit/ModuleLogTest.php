<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class ModuleLogTest extends TestCase
{
    private $moduleMock;

    public function setup()
    {
        $this->moduleMock = new ModuleMock();
    }

    public function testCreate()
    {
        $moduleLog = new ModuleLog($this->moduleMock);
        $this->assertNotNull($moduleLog);
    }

    public function testLogCronJobsRun()
    {
        $moduleLog = new ModuleLog($this->moduleMock);

        $message = 'REDCap-ETL cron jobs run';
        $numJobs = 2;
        $day = 1;
        $hour = 7;
        $logId = $moduleLog->logCronJobsRun($numJobs, $day, $hour);

        $entry = $this->moduleMock->getLogEntry($logId);
        $this->assertNotNull($entry, 'Log entry found');

        $entryMessage = $entry['message'];
        $this->assertEquals($message, $entryMessage, 'Message check');

        $params = $entry['params'];
        $expectedParams = [
            'log_type' => ModuleLog::ETL_CRON,
            'log_format_version' => ModuleLog::LOG_FORMAT_VERSION,
            'cron_day' => $day,
            'cron_hour' => $hour,
            'num_jobs' => $numJobs
        ];
        $this->assertEquals($expectedParams, $params, 'Params check');
    }
}

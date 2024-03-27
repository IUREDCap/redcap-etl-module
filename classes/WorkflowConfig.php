<?php

#-------------------------------------------------------
# Copyright (C) 2021 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

class WorkflowConfig implements \JsonSerializable
{
    /** @var workflow status */
    private $status;

    /** @var Configuration Configuration object of global properties. */
    private $globalPropertiesConfig;

    /** @var array map from task name to Configuration object for that task. */
    private $taskConfigs;

    public function __construct()
    {
        $this->status                 = Workflow::WORKFLOW_INCOMPLETE;
        $this->globalPropertiesConfig = null; # Configuration
        $this->taskConfigs            = array(); # map from task name to Configuration
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        return $this->status = $status;
    }

    /**
     * @param string $taskName the name of the task being added.
     * @param Configuration $configuration the Configuration object for the task.
     */
    public function addTaskConfiguration($taskName, $configuration)
    {
        $this->taskConfigs[$taskName] = $configuration;
    }

    public function setGlobalPropertiesConfig($globalPropertiesConfig)
    {
        $this->globalPropertiesConfig = $globalPropertiesConfig;
    }


    /**
     * Convert workflow to JSON, and exclude specified tasks (if any).
     *
     * Example workflow JSON:
     *
     *    {
     *        "workflow": {
     *            "global_properties": {
     *                "workflow_name": "workflow1",
     *                "batch_size": 10,
     *                ...
     *            },
     *            "tasks": {
     *                "task1": {
     *                    "redcap_api_url": "http://localhost/redcap/api/",
     *                    "data_source_api_token": "11347CC74A8B98AC31BA9F78215814968",
     *                    ...
     *                },
     *                "task2": {
     *                    ...
     *                },
     *
     *            }
     *        }
     *    }
     */
    public function toJson($excludedTaskNames = [])
    {
        $workflowArray = [
            "workflow" => [
                "global_properties" => array_filter($this->globalPropertiesConfig->getPropertiesArray()),
                "tasks"             => array()
            ]
        ];

        foreach ($this->taskConfigs as $taskName => $taskConfig) {
            $workflowArray['workflow']['tasks'][$taskName] = $taskConfig->getPropertiesArray();
        }

        $json = json_encode($workflowArray, JSON_PRETTY_PRINT);

        if ($json === false) {
            throw new \Exception('Unable to convert workflow to JSON.');
        }

        #error_log("{$json}", 3, __DIR__.'/../workflow.json');
        return $json;
    }

    /**
     * Gets an array version of the workflow config in a format the can be run with REDCap-ETL.
     */
    public function toArray()
    {
        $workflowArray = array_filter($this->globalPropertiesConfig->getPropertiesArray());

        foreach ($this->taskConfigs as $taskName => $taskConfig) {
            $workflowArray[$taskName] = $taskConfig->getPropertiesArray();
        }

        #error_log(print_r($workflowArray, true), 3, __DIR__.'/../workflow.txt');
        return $workflowArray;
    }


    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function getGlobalPropertiesConfig()
    {
        return $this->globalPropertiesConfig;
    }

    public function getTaskConfigs()
    {
        return $this->taskConfigs;
    }
}

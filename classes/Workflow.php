<?php

#-------------------------------------------------------
# Copyright (C) 2021 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

class Workflow implements \JsonSerializable
{
    # Workflow status constants
    const WORKFLOW_INCOMPLETE = 'Incomplete';
    const WORKFLOW_READY  = 'Ready';
    const WORKFLOW_REMOVED = 'Removed';

    private $metadata;
    private $globalProperties;
    private $cron;
    private $tasks;

    public function __construct($username)
    {
        $this->metadata         = array();
        $this->globalProperties = array();
        $this->cron             = array(); # cron job details
        $this->tasks            = array(); # map from task name to task info

        # Set metadata
        $this->metadata['workflowStatus'] = self::WORKFLOW_INCOMPLETE;
        $now = new \DateTime();
        $now->format('Y-m-d H:i:s');
        $now->getTimestamp();
        $this->metadata['dateAdded']      = $now;
        $this->metadata['addedBy']        = $username;
        $this->metadata['updatedBy']      = null;
        $this->metadata['dateUpdated']    = null;
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
                "global_properties" => $this->globalProperties,
                "tasks"             => array()
            ]
        ];

        foreach ($this->tasks as $taskName => $taskProperties) {
            if (in_array($taskName, $excludedTaskNames)) {
                ; // task is excluded, so skip it
            } else {
                $workflowArray['tasks'][$taskName] = $taskProperties;
            }
        }

        $json = json_encode($workflowArray);

        if ($json === false) {
            throw new \Exception('Unable to convert workflow to JSON.');
        }

        return $json;
    }

    public function toArray($excludedTaskNames = [])
    {
        $workflowArray = $this->globalProperties;

        foreach ($this->tasks as $taskName => $taskProperties) {
            if (in_array($taskName, $excludedTaskNames)) {
                ; // task is excluded, so skip it
            } else {
                $workflowArray[$taskName] = $taskProperties;
            }
        }

        return $workflowArray;
    }


    public function initializeFromArray($workflowArray)
    {
        foreach (get_object_vars($this) as $var => $value) {
            $this->$var = $workflowArray[$var];
        }
    }

    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function getTasks()
    {
        return $this->tasks;
    }

    public function sequenceTasks($username)
    {
        #sort project-tasks by sequence number
        array_multisort(array_column($this->tasks, 'taskSequenceNumber'), SORT_ASC, SORT_NUMERIC, $this->tasks);

        #renumber sequence to ensure the no sequence numbers duplicated or omitted
        $i = 1;
        foreach ($this->tasks as $key => $task) {
            $this->tasks[$key]['taskSequenceNumber'] = $i;
            ++$i;
        }

        if ($username) {
            $this->metadata['updatedBy'] = $username;
            $now = new \DateTime();
            $now->format('Y-m-d H:i:s');
            $now->getTimestamp();
            $this->metadata['dateUpdated'] = $now;
        }
    }


    /**
     * Adds a project/task to the workflow. The key for the project/task is the project ID.
     * Project/task data includes:
     *     - a task sequence number: This is a default sequence number based on the number of project ids
     *       already in the workflow. Project IDs are assumed to be integer values.
     *     - a task name: This is a default task name that includes the project ID
     *     - the etl configuration to use: This is set to a default of null.
     */
    public function addProject($projectId, $username)
    {
        $message = 'When adding project to workflow, ';

        if (empty($projectId)) {
            $message .= 'no project id was specified.';
            throw new \Exception($message);
        }

        $sequence = 0;

        # If is workflow is empty, then this is the first project and the default sequenence is 1.
        if (count($this->tasks) == 0) {
            $sequence = 1;
        } else {
            # the default sequence is the number of integer keys (projectIds) plus 1.
            $sequence = count(array_filter($this->tasks, 'is_int', ARRAY_FILTER_USE_KEY)) + 1;
        }

        # add the project to workflow
        $task = array();
        $task["projectId"] = $projectId;
        $task["taskSequenceNumber"] = $sequence;
        $task["taskName"] = $this->getDefaultTaskName($projectId);
        $task["projectEtlConfig"] = null;
        $this->tasks[] = $task;

        #Set workflow status to incomplete, since an ETL config still needs to be selected for this project.
        $this->metadata['workflowStatus'] = self::WORKFLOW_INCOMPLETE;
        if ($username) {
            $this->setUpdatedInfo($username);
        }
    }

    public function getDefaultTaskName($projectId)
    {
        $taskNumber = 1;
        $taskName = 'Project ' . $projectId . ' task';
        while ($this->hasTaskName($taskName)) {
            $taskNumber++;
            $taskName = 'Project ' . $projectId . ' task ' . $taskNumber;
        }
        return $taskName;
    }

    public function getStatus()
    {
        return $this->metadata['workflowStatus'];
    }

    public function setStatus($status)
    {
        return $this->metadata['workflowStatus'] = $status;
    }

    public function setUpdatedInfo($username)
    {
        $this->metadata['updatedBy'] = $username;

        $now = new \DateTime();
        $now->format('Y-m-d H:i:s');
        $now->getTimestamp();
        $this->metadata["dateUpdated"] = $now;
    }

    public function getCopy($username)
    {
        $copy = new Workflow($username);

        $copy->setStatus($this->getStatus());

        $copy->globalProperties = $this->globalProperties;
        $copy->cron             = $this->cron;
        $copy->tasks            = $this->tasks;

        return $copy;
    }

    public function deleteTask($taskKey, $username)
    {
        $message = 'When deleting task from workflow, ';

        if (empty($taskKey) && ($taskKey != 0)) {
            $message .= 'no task key was specified.';
            throw new \Exception($message);
        }

        if (count($this->tasks) === 1) {
            $message = 'Attempt to delete task from workflow that has only one task.'
               . ' All workflows must contain at least one task so that they are associated with'
               . ' a project and can be accessed.';
            throw new \Exception($message);
        }

        $delSeqNum = $this->tasks[$taskKey]['taskSequenceNumber'];

        foreach ($this->tasks as $key => $project) {
            if ($project['taskSequenceNumber'] > $delSeqNum) {
                --$this->tasks[$key]['taskSequenceNumber'];
            }
        }

        unset($this->tasks[$taskKey]);

        #workflow status
        $etlConfigs = array_column($this->tasks, 'projectEtlConfig');
        $emptyEtlConfig = empty($etlConfigs) || in_array(null, $etlConfigs, true)
            || in_array('', $etlConfigs, true);
        if (!$emptyEtlConfig) {
            $this->metadata['workflowStatus'] = self::WORKFLOW_READY;
        } elseif ($this->metadata['workflowStatus'] !== WORKFLOW_REMOVED) {
            $this->metadata['workflowStatus'] = self::WORKFLOW_INCOMPLETE;
        }

        if ($username) {
            $this->setUpdatedInfo($username);
        }
    }

    public function renameTask($taskKey, $newTaskName, $projectId, $username)
    {
        $message = 'When renaming task from workflow, ';

        if (empty($taskKey) && ($taskKey != 0)) {
            $message .= 'no task key was specified.';
            throw new \Exception($message);
        }

        if (!isset($newTaskName)) {
            $newTaskName = 'Task for project ' . $projectId;
        }

        $workflow = $this->tasks[$taskKey]['taskName'] = $newTaskName;

        if ($username) {
            $this->setUpdatedInfo($username);
        }
    }

    public function assignWorkflowTaskEtlConfig(
        $projectId,
        $taskKey,
        $etlConfig,
        $username
    ) {
        $message = 'When assigning ETL config to workflow, ';

        if (empty($taskKey) && ($taskKey != 0)) {
            $message .= 'no task key was specified.';
            throw new \Exception($message);
        }

        $this->tasks[$taskKey]['projectEtlConfig'] = $etlConfig;

        #workflow status
        $etlConfigs = array_column($this->tasks, 'projectEtlConfig');

        $emptyEtlConfig = empty($etlConfigs) || in_array(null, $etlConfigs, true)
            || in_array('', $etlConfigs, true);
        if (!$emptyEtlConfig) {
            $this->metadata['workflowStatus'] = self::WORKFLOW_READY;
        } elseif ($this->metadata['workflowStatus'] !== WORKFLOW_REMOVED) {
                $this->metadata['workflowStatus'] = self::WORKFLOW_INCOMPLETE;
        }

        #workflow metadata
        if ($username) {
            $this->setUpdatedInfo($username);
        }
    }

    public function getGlobalProperties()
    {
        return $this->globalProperties;
    }

    public function setGlobalProperties($properties, $username)
    {
        $this->globalProperties = $properties;

        #workflow metadata
        if ($username) {
            $this->setUpdatedInfo($username);
        }
    }

    public function setGlobalProperty($name, $value)
    {
        $this->globalProperties[$name] = $value;
    }

    public function setCronSchedule($server, $schedule, $username)
    {
        $this->cron[Configuration::CRON_SERVER] = $server;
        $this->cron[Configuration::CRON_SCHEDULE] = $schedule;

        #workflow metadata
        if ($username) {
            $this->setUpdatedInfo($username);
        }
    }

    public function getCron()
    {
        return $this->cron;
    }

    public function getCronServer()
    {
        return $this->cron[Configuration::CRON_SERVER];
    }

    public function getCronSchedule()
    {
        return $this->cron[Configuration::CRON_SCHEDULE];
    }

    public function getEtlConfigs()
    {
        $etlConfigs = array_column($this->tasks, 'projectEtlConfig');
        return $etlConfigs;
    }

    public function getProjectIds()
    {
        $projectIds = array_column($this->tasks, 'projectId');
        return $projectIds;
    }

    public function getTaskNames()
    {
        $taskNames = array_column($this->tasks, 'taskName');
        return $taskNames;
    }

    public function hasTaskName($taskName)
    {
        $taskNames = array_column($this->tasks, 'taskName');
        $hasTaskName = in_array($taskName, $taskNames);
        return $hasTaskName;
    }

    public function getDateAddedDate()
    {
        return $this->metadata['dateAdded']['date'];
    }

    public function getAddedBy()
    {
        return $this->metadata['addedBy'];
    }

    public function getDateUpdatedDate()
    {
        return $this->metadata['dateUpdated']['date'];
    }

    public function getUpdatedBy()
    {
        return $this->metadata['updatedBy'];
    }
}

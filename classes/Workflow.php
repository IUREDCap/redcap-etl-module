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
        $this->tasks            = array(); # array of task info

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


    /**
     * Adds a project/task to the workflow. The key for the project/task is the project ID.
     * Project/task data includes:
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


        # add the project to workflow
        $task = array();
        $task["projectId"] = $projectId;
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

    /**
     * Moves task "up" in the array (to lower index)
     */
    public function moveTaskUp($upTaskKey)
    {
        if ($upTaskKey > 0 && $upTaskKey < count($this->tasks)) {
            $temp = $this->tasks[$upTaskKey - 1];
            $this->tasks[$upTaskKey - 1] = $this->tasks[$upTaskKey];
            $this->tasks[$upTaskKey] = $temp;
        }
    }

    /**
     * Moves task "down" in the array (to higher index)
     */
    public function moveTaskDown($downTaskKey)
    {
        if ($downTaskKey >= 0 && $downTaskKey < count($this->tasks) - 1) {
            $temp = $this->tasks[$downTaskKey + 1];
            $this->tasks[$downTaskKey + 1] = $this->tasks[$downTaskKey];
            $this->tasks[$downTaskKey] = $temp;
        }
    }
}

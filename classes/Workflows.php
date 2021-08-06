<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

class Workflows implements \JsonSerializable
{
    /** @var array map from workflow name to workflow information */
    private $workflows;

    public function __construct()
    {
        $this->workflows = array();
    }

    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function getWorkflows()
    {
        return $this->workflows;
    }



    /**
     * Creates the workflow and adds the workflow metadata data elements to it.
     */
    public function createWorkflow($workflowName, $username)
    {
        $workflow = new Workflow($username);
        $this->workflows[$workflowName] = $workflow;
    }

    public function getWorkflow($workflowName)
    {
        $workflow = $this->workflows[$workflowName];
        $username = null;
        $workflow->sequenceTasks($username);

        return $workflow;
    }

    public function getWorkflowTasks($workflowName)
    {
        $workflow = $this->workflows[$workflowName];
        $username = null;
        $workflow->sequenceTasks($username);

        return $workflow->getTasks();
    }


    /**
     * Adds a project/task to the workflow. The key for the project/task is the project ID.
     * Project/task data includes:
     *     - a task sequence number: This is a default sequence number based on the number of project ids
     *       already in the workflow. Project IDs are assumed to be integer values.
     *     - a task name: This is a default task name that includes the project ID
     *     - the etl configuration to use: This is set to a default of null.
     */
    public function addProjectToWorkflow($workflowName, $projectId, $username)
    {
        $message = 'When adding project to workflow, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        } elseif (!array_key_exists($workflowName, $this->workflows)) {
            $message .= 'workflow "' . $workflowName . '" was not found.';
            throw new \Exception($message);
        }

        $workflow = $this->workflows[$workflowName];
        $workflow->addProject($projectId, $username);
    }


    /**
     * Deletes a workflow from the workflows array.
     */
    public function deleteWorkflow($workflowName)
    {
        unset($this->workflows[$workflowName]);
    }

    public function reinstateWorkflow($workflowName, $username)
    {
        $message = 'When reinstating workflow, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        } elseif (!array_key_exists($workflowName, $this->workflows)) {
            $message .= 'workflow "' . $workflowName . '" was not found.';
            throw new \Exception($message);
        }

        $workflow = $this->workflows[$workflowName];

        $etlConfigs = $workflow->getEtlConfigs();
        $emptyEtlConfig = empty($etlConfigs) || in_array(null, $etlConfigs, true)
            || in_array('', $etlConfigs, true);
        if (!$emptyEtlConfig) {
            $workflow->setStatus(Workflow::WORKFLOW_READY);
        } else {
            $workflow->setStatus(Workflow::WORKFLOW_INCOMPLETE);
        }

        $workflow->setUpdatedInfo($username);
    }

    /**
     * Marks a workflow as being removed (inactive).
     */
    public function removeWorkflow($workflowName, $username)
    {
        $message = 'When removing workflow, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        } elseif (!array_key_exists($workflowName, $this->workflows)) {
            $message .= 'workflow "' . $workflowName . '" was not found.';
            throw new \Exception($message);
        }

        $workflow = $this->workflows[$workflowName];
        $workflow->setStatus(Workflow::WORKFLOW_REMOVED);
        $workflow->setUpdatedInfo($username);
    }

    public function copyWorkflow($fromWorkflowName, $toWorkflowName, $username)
    {
        $message = 'When copying workflow, ';
        if (empty($fromWorkflowName)) {
            $message .= 'no workflow name to copy was specified.';
            throw new \Exception($message);
        } elseif (!array_key_exists($fromWorkflowName, $this->workflows)) {
            $message .= 'workflow "' . $fromWorkflowName . '" was not found.';
            throw new \Exception($message);
        }

        $this->workflows[$toWorkflowName] = $this->workflows[$fromWorkflowName]->getCopy($username);
    }

    public function getWorkflowStatus($workflowName)
    {
        $message = 'When getting workflow status, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        } elseif (!array_key_exists($workflowName, $this->workflows)) {
            $message .= 'workflow "' . $workflowName . '" was not found.';
            throw new \Exception($message);
        }

        return $this->workflows[$workflowName]->getStatus();
    }

    public function workflowExists($workflowName)
    {
        $exists = false;
        if (array_key_exists($workflowName, $this->workflows)) {
            $exists = true;
        }
        return $exists;
    }


    public function deleteTaskFromWorkflow($workflowName, $taskKey, $username)
    {
        $message = 'When deleting task from workflow, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        } elseif (!array_key_exists($workflowName, $this->workflows)) {
            $message .= 'workflow "' . $workflowName . '" was not found.';
            throw new \Exception($message);
        }

        if (empty($taskKey) && ($taskKey != 0)) {
            $message .= 'no task key was specified.';
            throw new \Exception($message);
        }

        $workflow = $this->workflows[$workflowName];

        $workflow->deleteTask($taskKey, $username);
    }

    public function renameWorkflowTask($workflowName, $taskKey, $newTaskName, $projectId, $username)
    {
        $message = 'When renaming task from workflow, ';
        if (!isset($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        } elseif (!array_key_exists($workflowName, $this->workflows)) {
            $message .= 'workflow "' . $workflowName . '" was not found.';
            throw new \Exception($message);
        }

        if (empty($taskKey) && ($taskKey != 0)) {
            $message .= 'no task key was specified.';
            throw new \Exception($message);
        }

        if (!isset($newTaskName)) {
            $newTaskName = 'Task for project ' . $projectId;
        }

        $workflow = $this->workflows[$workflowName];

        $workflow->renameTask($taskKey, $newTaskName, $projectId, $username);
    }

    public function assignWorkflowTaskEtlConfig(
        $workflowName,
        $projectId,
        $taskKey,
        $etlConfig,
        $username
    ) {
        $message = 'When assigning ETL config to workflow, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        } elseif (!array_key_exists($workflowName, $this->workflows)) {
            $message .= 'workflow "' . $workflowName . '" was not found.';
            throw new \Exception($message);
        } elseif (empty($taskKey) && ($taskKey != 0)) {
            $message .= 'no task key was specified.';
            throw new \Exception($message);
        }

        $workflow = $this->workflows[$workflowName];

        $workflow->assignWorkflowTaskEtlConfig($projectId, $taskKey, $etlConfig, $username);
    }

    public function getWorkflowGlobalProperties($workflowName)
    {
        $message = 'When getting workflow global properties, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        } elseif (!array_key_exists($workflowName, $this->workflows)) {
            $message .= 'workflow "' . $workflowName . '" was not found.';
            throw new \Exception($message);
        }

        return $this->workflows[$workflowName]->getGlobalProperties();
    }

    public function setGlobalProperties($workflowName, $properties, $username)
    {
        $message = 'When setting workflow global properties, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        } elseif (!array_key_exists($workflowName, $this->workflows)) {
            $message .= 'workflow "' . $workflowName . '" was not found.';
            throw new \Exception($message);
        }

        $this->workflows[$workflowName]->setGlobalProperties($properties, $username);
    }

    public function setCronSchedule($workflowName, $server, $schedule, $username)
    {
        $message = 'When setting workflow cron schdule, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        }

        $this->workflows[$workflowName]->setCronSchedule($server, $schedule, $user);
    }

    public function getCronSchedule($workflowName)
    {
        return $this->workflows[$workflowName]->getCron();
    }

    public function getCronJobs($day, $time)
    {
        $cronJobs = array();
        foreach ($this->workflows as $workflowName => $workflow) {
            if (!empty($workflow->getCron())) {
                $times = $workflow->getCronSchedule();

                if (isset($times) && is_array($times)) {
                    for ($cronDay = 0; $cronDay < 7; $cronDay++) {
                        $cronTime = $times[$cronDay];
                        if (isset($cronTime) && $cronTime != "" && $time == $cronTime && $day == $cronDay) {
                            $job = array(
                                "workflowName"  => $workflowName,
                                "server"  => $workflow->getCronServer(),
                                "workflowStatus" => $workflow->getStatus()
                            );
                            array_push($cronJobs, $job);
                        }
                    }
                }
            }
        }
        return $cronJobs;
    }

    public function fromJson($json)
    {
        if (!empty($json)) {
            $values = json_decode($json, true);

            #print("<pre>");
            #print_r($values);
            #print("</pre>");

            $workflows = $values['workflows'];

            foreach ($workflows as $workflowName => $workflowValue) {
                $workflow = new Workflow(null);
                $workflow->initializeFromArray($workflowValue);
                $this->workflows[$workflowName] = $workflow;
            }
        }
    }

    public function toJson()
    {
        $json = json_encode($this);
        return $json;
    }
}

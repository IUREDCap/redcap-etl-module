<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

class Workflow implements \JsonSerializable
{
    private $workflows;

    const WORKFLOW_INCOMPLETE = 'Incomplete';
    const WORKFLOW_READY  = 'Ready';
    const WORKFLOW_REMOVED = 'Removed';

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
        $this->workflows[$workflowName] = array();
        $this->workflows[$workflowName]["metadata"] = array();
        $this->workflows[$workflowName]["properties"] = array();

        $now = new \DateTime();
        $now->format('Y-m-d H:i:s');
        $now->getTimestamp();
        $this->workflows[$workflowName]["metadata"]["dateAdded"] = $now;
        $this->workflows[$workflowName]["metadata"]["added_by"] = $username;
        $this->workflows[$workflowName]["metadata"]["workflowStatus"] = self::WORKFLOW_INCOMPLETE;
    }

    public function getWorkflow($workflowName, $removeMetadata = null)
    {
        $workflow = $this->workflows[$workflowName];
        unset($workflow["properties"]);
        if ($removeMetadata) {
            unset($workflow["metadata"]);
        }

        return $workflow;
    }

    public function sequenceWorkflow($workflowName, $workflow, $username)
    {

        $metadata = null;
        if (!array_key_exists('metadata', $workflow)) {
            $metadata = $this->workflows[$workflowName]["metadata"];
        }

        #sort project-tasks by sequence number
        array_multisort(array_column($workflow, 'taskSequenceNumber'), SORT_ASC, SORT_NUMERIC, $workflow);
        
        #renumber sequence to ensure the no sequence numbers duplicated or omitted
        $i = 1;
        foreach ($workflow as $key => $task) {
            $workflow[$key]['taskSequenceNumber'] = $i;
            ++$i;
        }

        #replace the old workflow with this updated workflow
        $this->workflows[$workflowName] = $workflow;

        #add the metadata back to the updated workflow
        if ($metadata) {
            $this->workflows[$workflowName]["metadata"] = $metadata;
        }

        if ($username) {
            $this->workflows[$workflowName]["metadata"]["updatedBy"] = $username;
            $now = new \DateTime();
            $now->format('Y-m-d H:i:s');
            $now->getTimestamp();
            $this->workflows[$workflowName]["metadata"]["dateUpdated"] = $now;
        }
    }


    public function updateWorkflow($workflowName, $workflow, $workflowStatus, $username)
    {
#delete this??

        $message = 'When updating workflow, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        }
     
        $metadata = null;
        if (!array_key_exists('metadata', $workflow)) {
            $metadata = $this->workflows[$workflowName]["metadata"];
        }

        #sort project-tasks by sequence number
        array_multisort(array_column($workflow, 'taskSequenceNumber'), SORT_ASC, SORT_NUMERIC, $workflow);
        
        #renumber sequence to ensure the no sequence numbers duplicated or omitted
        $i = 1;
        foreach ($workflow as $key => $task) {
            $workflow[$key]['taskSequenceNumber'] = $i;
            ++$i;
        }

        #replace the old workflow with this updated workflow
        $this->workflows[$workflowName] = $workflow;

        #add the metadata back to the updated workflow
        if ($metadata) {
            $this->workflows[$workflowName]["metadata"] = $metadata;
        }

        if ($workflowStatus === 'Ready') {
            $this->workflows[$workflowName]["metadata"]["workflowStatus"] = self::WORKFLOW_READY;
        }

        if ($username) {
            $this->workflows[$workflowName]["metadata"]["updatedBy"] = $username;
            $now = new \DateTime();
            $now->format('Y-m-d H:i:s');
            $now->getTimestamp();
            $this->workflows[$workflowName]["metadata"]["dateUpdated"] = $now;
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
    public function addProjectToWorkflow($workflowName, $projectId, $username)
    {

        $message = 'When adding project to workflow, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        }

        if (empty($projectId)) {
            $message .= 'no project id was specified.';
            throw new \Exception($message);
        }

        $sequence = 0;
        #If is workflow is empty, then this is the first project and the default sequenence is 1.
        if (count($this->workflows[$workflowName]) == 0) {
            $sequence = 1;

        #otherwise, the default sequence is the number of integer keys (projectIds) plus 1.
        } else {
            $sequence = count(array_filter($this->workflows[$workflowName], 'is_int', ARRAY_FILTER_USE_KEY)) + 1;
        }

        #add the project to workflow
        $workflow = array();
        $workflow["projectId"] = $projectId;
        $workflow["taskSequenceNumber"] = $sequence;
        $workflow["taskName"] = 'Task for project ' . $projectId;
        $workflow["projectEtlConfig"] = null;
        $this->workflows[$workflowName][] = $workflow;

        #Set workflow status to incomplete, since an ETL config still needs to be selected for this project.
        $this->workflows[$workflowName]["metadata"]["workflowStatus"] = self::WORKFLOW_INCOMPLETE;
        if ($username) {
            $this->workflows[$workflowName]["metadata"]["updatedBy"] = $username;
            $now = new \DateTime();
            $now->format('Y-m-d H:i:s');
            $now->getTimestamp();
            $this->workflows[$workflowName]["metadata"]["dateUpdated"] = $now;
        }
    }

    public function updateWorkflowProject($workflowName, $projectId, $task, $username)
    {
#delete this??

        $message = 'When updating workflow project, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        }

        if (empty($projectId)) {
            $message .= 'no project was specified.';
            throw new \Exception($message);
        }

        if (!array_key_exists($projectId, $this->workflows[$workflowName])) {
            $message .= 'specified project id is not part of this workflow.';
            throw new \Exception($message);
        }

        $this->workflows[$workflowName][$projectId]["taskSequenceNumber"] = $task["taskSequenceNumber"];
        $this->workflows[$workflowName][$projectId]["taskName"] = $task["taskName"];
        $this->workflows[$workflowName][$projectId]["projectEtlConfig"] = $task["projectEtlConfig"];

        if ($username) {
            $this->workflows[$workflowName]["metadata"]["updatedBy"] = $username;
            $now = new \DateTime();
            $now->format('Y-m-d H:i:s');
            $now->getTimestamp();
            $this->workflows[$workflowName]["metadata"]["dateUpdated"] = $now;
        }
    }

    /**
     * Deletes a workflow from the workflows array.
     */
    public function deleteWorkflow($workflowName)
    {
        unset($this->workflows[$workflowName]);
    }

    /**
     * Marks a workflow as being removed (inactive).
     */
    public function removeWorkflow($workflowName, $username)
    {
        $this->workflows[$workflowName]["metadata"]["workflowStatus"] = self::WORKFLOW_REMOVED;
        $this->workflows[$workflowName]["metadata"]["updatedBy"] = $username;

        $now = new \DateTime();
        $now->format('Y-m-d H:i:s');
        $now->getTimestamp();
        $this->workflows[$workflowName]["metadata"]["dateUpdated"] = $now;
    }
    
    public function copyWorkflow($fromWorkflowName, $toWorkflowName, $username)
    {
        $this->workflows[$toWorkflowName] = $this->workflows[$fromWorkflowName];
        $this->workflows[$toWorkflowName]["metadata"]["added_by"] = $username;

        $now = new \DateTime();
        $now->format('Y-m-d H:i:s');
        $now->getTimestamp();
        $this->workflows[$toWorkflowName]["metadata"]["dateAdded"] = $now;

        if ($this->workflows[$toWorkflowName]["metadata"]["updatedBy"]) {
            $this->workflows[$toWorkflowName]["metadata"]["updatedBy"] = null;
            $this->workflows[$toWorkflowName]["metadata"]["dateUpdated"] = null;
        }
    }

    public function getWorkflowStatus($workflowName)
    {
        return $this->workflows[$workflowName]["metadata"]["workflowStatus"];
    }

    public function workflowExists($workflowName)
    {
        $exists = false;
        #$workflows = json_decode($workflows_json, true);

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
        }

        if (empty($taskKey) && ($taskKey != 0)) {
            $message .= 'no task key was specified.';
            throw new \Exception($message);
        }

        $delSeqNum = $this->workflows[$workflowName][$taskKey]['taskSequenceNumber'];

        foreach ($this->workflows[$workflowName] as $key => $project) {
            if ($project['taskSequenceNumber'] > $delSeqNum) {
                --$this->workflows[$workflowName][$key]['taskSequenceNumber'];
            }
        }

        unset($this->workflows[$workflowName][$taskKey]);
 
        #workflow status
        $etlConfigs = array_column($this->workflows[$workflowName], 'projectEtlConfig');
        $emptyEtlConfig = empty($etlConfigs) || in_array(null, $etlConfigs, true)
            || in_array('', $etlConfigs, true);
        if (!$emptyEtlConfig) {
            $this->workflows[$workflowName]["metadata"]["workflowStatus"] = self::WORKFLOW_READY;
        } elseif ($this->workflows[$workflowName]["metadata"]["workflowStatus"] !== WORKFLOW_REMOVED) {
                $this->workflows[$workflowName]["metadata"]["workflowStatus"] = self::WORKFLOW_INCOMPLETE;
        }

        if ($username) {
            $this->workflows[$workflowName]["metadata"]["updatedBy"] = $username;
            $now = new \DateTime();
            $now->format('Y-m-d H:i:s');
            $now->getTimestamp();
            $this->workflows[$workflowName]["metadata"]["dateUpdated"] = $now;
        }
    }

    public function renameWorkflowTask($workflowName, $taskKey, $newTaskName, $projectId, $username)
    {
        $message = 'When renaming task from workflow, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        }

        if (empty($taskKey) && ($taskKey != 0)) {
            $message .= 'no task key was specified.';
            throw new \Exception($message);
        }

        if (empty($newTaskName)) {
            $newTaskName = 'Task for project ' . $projectId;
        }

        $workflow = $this->workflows[$workflowName][$taskKey]['taskName'] = $newTaskName;

        if ($username) {
            $this->workflows[$workflowName]["metadata"]["updatedBy"] = $username;
            $now = new \DateTime();
            $now->format('Y-m-d H:i:s');
            $now->getTimestamp();
            $this->workflows[$workflowName]["metadata"]["dateUpdated"] = $now;
        }
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
        } elseif (empty($taskKey) && ($taskKey != 0)) {
            $message .= 'no task key was specified.';
            throw new \Exception($message);
        }

        $this->workflows[$workflowName][$taskKey]['projectEtlConfig'] = $etlConfig;

        #workflow status
        $etlConfigs = array_column($this->workflows[$workflowName], 'projectEtlConfig');
        $emptyEtlConfig = empty($etlConfigs) || in_array(null, $etlConfigs, true)
            || in_array('', $etlConfigs, true);
        if (!$emptyEtlConfig) {
            $this->workflows[$workflowName]["metadata"]["workflowStatus"] = self::WORKFLOW_READY;
        } elseif ($this->workflows[$workflowName]["metadata"]["workflowStatus"] !== WORKFLOW_REMOVED) {
                $this->workflows[$workflowName]["metadata"]["workflowStatus"] = self::WORKFLOW_INCOMPLETE;
        }

        #workflow metadata
        if ($username) {
            $this->workflows[$workflowName]["metadata"]["updatedBy"] = $username;
            $now = new \DateTime();
            $now->format('Y-m-d H:i:s');
            $now->getTimestamp();
            $this->workflows[$workflowName]["metadata"]["dateUpdated"] = $now;
        }
    }

    public function getWorkflowGlobalProperties($workflowName)
    {
#print "==================wwwwwwwwwwwwwwwwwww00000000, 393 workflow.php, getWorkflowGlobalProperties, workflow: $workflowName, is ";
#print_r($this->workflows[$workflowName]);
#print "==================wwwwwwwwwwwwwwwwww00000000, 395 workflow.php, getWorkflowGlobalProperties, properties is ";
#print_r($this->workflows[$workflowName]["properties"]);
				
        return $this->workflows[$workflowName]["properties"];
    }

    public function setGlobalProperties($workflowName, $properties, $username)
    {
       $message = 'When settting workflow global properties, ';
        if (empty($workflowName)) {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        }

        $this->workflows[$workflowName]["properties"] = $properties;
print "==================wwwwwwwwwwwwwwwwww1111111111111111 workflow.php, setWorkflowGlobalProperties, workflow: $workflowName, is ";
print_r($this->workflows[$workflowName]);

        #workflow metadata
        if ($username) {
            $this->workflows[$workflowName]["metadata"]["updatedBy"] = $username;
            $now = new \DateTime();
            $now->format('Y-m-d H:i:s');
            $now->getTimestamp();
            $this->workflows[$workflowName]["metadata"]["dateUpdated"] = $now;
        }
    }
    
    public function getProjects($username)
    {
#delete?
        return $this->userList[$username];
    }
    
    public function addProject($username, $projectId)
    {
#delete?
        if (array_key_exists($username, $this->userList)) {
            $this->userList[$username][$projectId] = 1;
        }
    }

    public function removeProject($username, $projectId)
    {
#delete?
        if (array_key_exists($username, $this->userList)) {
            unset($this->userList[$username][$projectId]);
        }
    }
    
    public function fromJson($json)
    {
        if (!empty($json)) {
            $values = json_decode($json, true);
            foreach (get_object_vars($this) as $var => $value) {
                $this->$var = $values[$var];
            }
        }
    }

    public function toJson()
    {
        $json = json_encode($this);
        return $json;
    }
}

<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

use IU\REDCapETL\Database\DbConnectionFactory;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

#-------------------------------------------------------------------------
# Only allow this page to be included in the user ETL Configure page,
# and not accessed directly
#-------------------------------------------------------------------------
if (!defined('REDCAP_ETL_MODULE')) {
    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
    exit;
}
?>

<script>
    // Help dialog events
    $(document).ready(function() {
        $( function() {

            $('#workflow-tasks-help-link').click(function () {
                $('#workflow-tasks-help').dialog({dialogClass: 'redcap-etl-help', width: 400, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right-420 top-190', of: $(this)})
                    ;
                return false;
            });
        });
});
</script>

<?php

$newTaskKey = null;
$addProjectId = null;

$deleteTaskKey = null;
$deleteProjectId = null;

$renameTaskKey = null;
$renameProjectId = null;
$renameNewTaskName = null;

$moveTaskKey = null;

$etlConfig = null;
$etlTaskKey = null;
$etlProjectId = null;

$username = USERID;
$superUser = SUPER_USER;

#$workflowName = Filter::escapeForHtml($_GET['workflowName']);
$workflowName = Filter::stripTags($_POST['workflowName']);
if (empty($workflowName)) {
    $workflowName = Filter::stripTags($_GET['workflowName']);
    if (empty($workflowName)) {
        $workflowName = Filter::stripTags($_SESSION['workflowName']);
    }
}
$_SESSION['workflowName'] = $workflowName;



#Get projects that this user has access to
$db = new RedCapDb();
$userProjects = $db->getUserProjects($username);
$availableUserProjects = $userProjects;
array_unshift($availableUserProjects, '');

$selfUrl      = $module->getUrl(RedCapEtlModule::USER_ETL_CONFIG_PAGE);
                   #. '&workflowName=' . Filter::escapeForUrlParameter($workflowName);
$workflowsUrl = $module->getUrl('web/workflows.php');
$configUrl    = $module->getUrl(RedCapEtlModule::USER_ETL_CONFIG_PAGE);

#$globalPropertiesUrl = $module->getUrl('web/workflow_global_properties.php')
#                   . '&workflowName=' . Filter::escapeForUrlParameter($workflowName);

try {
    #-----------------------------------------------------------
    # Check that the user has permission to access this page
    #-----------------------------------------------------------
    $module->checkUserPagePermission(USERID);

    #-----------------------------------------------------------------
    # Process form submissions
    #-----------------------------------------------------------------
    $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);

    if (strcasecmp($submitValue, 'add task') === 0) {
        #--------------------------------------
        # Add task
        #--------------------------------------
        if (!array_key_exists('newTask', $_POST) || !isset($_POST['newTask'])) {
            $error = 'ERROR: No project was selected.';
        } else {
            $newTaskKey = Filter::stripTags($_POST['newTask']);
            $addProjectId = $availableUserProjects[$newTaskKey];
            $module->addProjectToWorkflow($workflowName, $addProjectId, $username);
        }
    } elseif ((strcasecmp($submitValue, 'up') === 0) || (strcasecmp($submitValue, 'down') === 0)) {
        #----------------------------------------------
        # Move task
        #----------------------------------------------
        $moveTaskKey = Filter::stripTags($_POST['moveTaskKey']);
        if (!empty($moveTaskKey) || ($moveTaskKey == 0)) {
            $module->moveWorkflowTask($workflowName, $submitValue, $moveTaskKey);
        }
    } elseif (strcasecmp($submitValue, 'delete') === 0) {
        #----------------------------------------------
        # Delete task
        #----------------------------------------------
        if (empty($warning) && empty($error)) {
            $deleteTaskKey = $_POST['deleteTaskKey'];
            $deleteProjectId = $_POST['deleteProjectId'];
            if (isset($deleteTaskKey)) {
                $module->deleteTaskfromWorkflow($workflowName, $deleteTaskKey, $deleteProjectId, $username);
            }
        }
    } elseif (strcasecmp($submitValue, 'etlConfig') === 0) {
        if (empty($warning) && empty($error)) {
            #----------------------------------------------
            # Specify ETL Config
            #----------------------------------------------
            $etlConfig = $_POST['projectEtlConfigSelect'];
            $etlTaskKey = $_POST['etlTaskKey'];
            $etlProjectId = $_POST['etlProjectId'];
            if (isset($etlTaskKey)) {
                $module->assignWorkflowTaskEtlConfig(
                    $workflowName,
                    $etlProjectId,
                    $etlTaskKey,
                    $etlConfig,
                    $username
                );
            }
        }
    } elseif (strcasecmp($submitValue, 'rename') === 0) {
        if (empty($warning) && empty($error)) {
            #----------------------------------------------
            # Rename task
            #----------------------------------------------
            $renameTaskKey = $_POST['renameTaskKey'];
            $renameProjectId = $_POST['renameProjectId'];
            $renameNewTaskName = $_POST['renameNewTaskName'];

            if (isset($renameTaskKey)) {
                #check to see if the new task name is the same as an ETL property name
                $matchFound = $module->checkWorkflowTaskNameAgainstEtlPropertyNames(
                    $renameProjectId,
                    $renameNewTaskName
                );
                if ($matchFound) {
                    $error = 'ERROR: Task new name cannot be set to the name of an existing ETL property. i'
                    . 'Please enter another name for the task.';
                } else {
                    $module->renameWorkflowTask(
                        $workflowName,
                        $renameTaskKey,
                        $renameNewTaskName,
                        $renameProjectId,
                        $username
                    );
                }
            }
        }
    }
} catch (\Exception $exception) {
    $error = 'ERROR: ' . $exception->getMessage();
}

$workflowStatus = $module->getWorkflowStatus($workflowName);

#Get the workflow's updated tasks list
$tasks = $module->getWorkflowTasks($workflowName);
$taskProjectIds = array_column($tasks, 'projectId');
?>

<div style="margin-top: 20px; margin-bottom: 12px;">
<h4>Workflow <?php echo $workflowName; ?></h4>
<span style="font-weight: bold;">Workflow Status:</span>
    <?php echo $workflowStatus ?>
</div>

<!--
<script>
    $( function() {
        $( "#accordion" ).accordion({
            active: false,
            collapsible: true,
            heightStyle: "content"
        });
    } );
</script>
-->

<div id="forms-container">

  
  <div id="tasks-container" class="tasks-container">

  <div style="float: right;">
    <a href="#" id="workflow-tasks-help-link" class="etl-help" title="help">?</a>
  </div>

  <div id="workflow-tasks-help" title="Workflow Tasks" style="display: none; clear: both;">
        <?php echo Help::getHelpWithPageLink('workflow-tasks', $module); ?>
  </div>

  <div style="font-weight: bold; font-size: 110%; text-align: center; margin-bottom: 20px;">Tasks</div>

<?php
#------------------------------------------------------------
# Add-task form
#------------------------------------------------------------
if (!empty($availableUserProjects)) {
    ?>
    <form action="<?php echo $selfUrl;?>" method="post" style="margin-bottom: 12px;" 
        name="addTaskForm" id="addTaskForm">
    <!--<fieldset class="config"> -->
    <!--<legend>Tasks</legend>-->
    <label for="newTask">REDCap project:</label>
    <select name="newTask" id="newTask">
    <?php
    foreach ($availableUserProjects as $key => $userProject) {
        if ($userProject) {
            echo '<option value="' . Filter::escapeForHtmlAttribute($key) . '">'
                . Filter::escapeForHtml($userProject['project_id'] . '-' . $userProject['app_title']) . "</option>\n";
        } else {
            echo '<option value=""></option>' . "\n";
        }
    }
    ?>
    </select>
    <input type="submit" name="submitValue" value="Add Task" />
    <br />&nbsp;
    <!--</fieldset> -->
    <?php Csrf::generateFormToken(); ?>
    </form>
<?php } ?>

<?php
#-----------------------------------------------
# Workflow configuration form
#-----------------------------------------------
?>
<form action="<?php echo $selfUrl;?>" method="post" name="updateWorkflowForm" id="updateWorkflowForm"> 
<input type="hidden" name="username" value="<?php echo Filter::escapeForHtmlAttribute($username);?>">
<input type="hidden" name="workflowName" value="<?php echo Filter::escapeForHtmlAttribute($workflowName);?>">

<hr />

<?php
   $numTasks = count($tasks);
?>

<p>WORKFLOW TASKS</p>
<table id="workflowTasks" name="workflowTasks" class="user-projects">
    <thead>
        <tr>
            <?php
            if ($numTasks > 1) {
                echo "<th></th>";
            }
            ?>
            <th>Task Name</th>
            <th>PID</th>
            <th>Project</th>
            <th>Project ETL Config</th>
            <th>Rename<br/>Task</th>
            <th>Specify<br/>ETL Config</th>
            <th>Delete<br/>Task</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $row = 1;
        foreach ($tasks as $taskKey => $task) {
            $projectId = $task['projectId'];
            if (empty($projectId) && $projectId !== 0) {
                $projectId = "No project Id";
            }
            $taskName = $task['taskName'];
            $pKey = array_search($projectId, array_column($userProjects, 'project_id'));
            $isAssignedUser = false;
            $hasPermissionToExport = false;
            $projectName = null;

            if ($pKey || $pKey === 0) {
                $isAssignedUser = true;
                $hasPermissionToExport = $userProjects[$pKey]['data_export_tool'] == 1 ? true : false;
                $projectName = $userProjects[$pKey]['app_title'];
                $projectEtlConfig = $task['projectEtlConfig'] ? $task['projectEtlConfig'] : "None specified";
            } else {
                $projectName = "(You are not a listed user on this project)";
                $projectEtlConfig = null;
            }

            if ($superUser) {
                $hasPermissionToExport = true;
                $projectName = $projectName ? $projectName : $db->getProjectName($projectId);
                $projectEtlConfig = $task['projectEtlConfig'] ? $task['projectEtlConfig'] : "None specified";
            }

            if ($row % 2 == 0) {
                echo '<tr class="even-row">' . "\n";
            } else {
                echo '<tr class="odd-row">' . "\n";
            }

            if ($numTasks > 1) {
                    echo '<td style="text-align:left;"> '
                        . '<input type="image" src="' . APP_PATH_IMAGES . 'arrow_down.png" alt="MOVE DOWN"'
                        . ' class="deleteConfig" style="cursor: pointer;"'
                        . ' id="moveTaskDown' . $row . '"/>';
                    echo'<input type="image" src="' . APP_PATH_IMAGES . 'arrow_up2.png" alt="MOVE UP"'
                        . ' class="deleteConfig" style="margin-left: 5px; cursor: pointer;"'
                        . ' id="moveTaskUp' . $row . '"/> ';
                    echo "</td>\n";
            }

            echo "<td>" . Filter::escapeForHtml($taskName) . "</td>\n";
            echo "<td>" . Filter::escapeForHtml($projectId) . "</td>\n";
            # echo "<td>" . Filter::escapeForHtml($projectName) . "</td>\n";

            # Project title with link to project
            echo "<td>\n";
            echo '<a href="' . APP_PATH_WEBROOT . 'index.php?pid='
                . Filter::escapeForUrlParameter($projectId) . '" target="_blank">'
                . Filter::escapeForHtml($projectName) . "</a>\n";
            echo "</td>\n";



            if ($projectEtlConfig === "None specified") {
                echo "<td>" . Filter::escapeForHtml($projectEtlConfig) . "</td>\n";
            } else {
                $configureUrl = $configUrl . '&configName=' . Filter::escapeForUrlParameter($projectEtlConfig)
                    . '&configType=task';
                echo "<td>" . '<a href="' . $configureUrl . '" target="_blank">'
                    . Filter::escapeForHtml($projectEtlConfig)
                    . '</a>' . "</td>\n";
            }

            #-----------------------------------------------------------
            # RENAME TASK BUTTON - disable if user does not have the needed
            # data export permission to access the project
            #-----------------------------------------------------------
            if ($hasPermissionToExport) {
                echo '<td style="text-align:center;">'
                    . '<input type="image" src="' . APP_PATH_IMAGES . 'page_white_edit.png" alt="RENAME TASK"'
                    . ' style="cursor: pointer;"'
                    . ' id="renameTask' . $row
                    . '"/>'
                    . "</td>\n";
            } else {
                echo '<td style="text-align:center;">'
                    . '<img src="' . APP_PATH_IMAGES . 'gear.png" alt="ETL GLOBAL VARIABLES" class="disabled" />'
                    . "</td>";
            }

            #-----------------------------------------------------------
            # SPECIFY ETL CONFIG BUTTON - disable if user does not have the needed
            # data export permission to access the project
            #-----------------------------------------------------------
            if ($hasPermissionToExport) {
                $values = $module->getAccessibleConfigurationNames($projectId);
                echo '<td style="text-align:center;">'
                    . '<input type="image" src="' . APP_PATH_IMAGES . 'page_white_edit.png" alt="RENAME TASK"'
                    . ' style="cursor: pointer;"'
                    . ' id="specifyEtlConfig' . $row
                    . '"/>'
                    . "</td>\n";
            } else {
                echo '<td style="text-align:center;">'
                    . '<img src="' . APP_PATH_IMAGES . 'gear.png" alt="ETL GLOBAL VARIABLES" class="disabled" />'
                    . "</td>";
            }

            #-----------------------------------------------------------
            # DELETE BUTTON - disable if user does not have the needed
            # data export permission to access the project
            #-----------------------------------------------------------
            if ($hasPermissionToExport) {
                echo '<td style="text-align:center;">'
                    . '<input type="image" src="' . APP_PATH_IMAGES . 'delete.png" alt="DELETE"'
                    . ' class="deleteConfig" style="cursor: pointer;"'
                    . ' id="deleteTask' . $row
                    . '"/>'
                    . "</td>\n";
            } else {
                echo '<td style="text-align:center;">'
                    . '<img src="' . APP_PATH_IMAGES . 'delete.png" alt="DELETE" class="disabled" />'
                    . "</td>\n";
            }
            echo "</tr>\n";
            $row++;
        }
        ?>
    </tbody>
</table>
<input type="hidden" name="moveTaskKey" id="moveTaskKey" value="">
<input type="hidden" name="submitValue" id="moveTask" value="">
<p>
<div style="clear: both;"></div>
</p>
<?php Csrf::generateFormToken(); ?>
</form>

</div> <!-- tasks-container -->


<div style="float: left;">
<?php
include(__DIR__ . '/global_properties_include.php');
?>
</div>

</div> <!-- forms-container -->
<div style="clear: both;"></div>





<?php
#--------------------------------------
# Move task up
#--------------------------------------
?>
<script>
   
    <?php
    # Set up click event handlers for the Move Task Up buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        echo '$("#moveTaskUp' . $row . '").click({key: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($key)
            . '", projectId: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            . '"}, moveTaskUp);' . "\n";
        $row++;
    }
    ?>

    function moveTaskUp(event) {
        var key = event.data.key;
        var submitValue = document.getElementsByName("submitValue");
        event.preventDefault();
        $('#moveTaskKey').val(key);
        $('#moveTask').val('"up"');
        $("#updateWorkflowForm").submit();
        //console.log(document.querySelectorAll("input"));
    }

</script>

<?php
#--------------------------------------
# Move task down
#--------------------------------------
?>
<script>
   
    <?php
    # Set up click event handlers for the Move Task Down buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        echo '$("#moveTaskDown' . $row . '").click({key: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($key)
            . '", projectId: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            . '"}, moveTaskDown);' . "\n";
        $row++;
    }
    ?>

    function moveTaskDown(event) {
        var key = event.data.key;
        var submitValue = document.getElementsByName("submitValue");
        event.preventDefault();
        $('#moveTaskKey').val(key);
        $('#moveTask').val('"down"');
        $("#updateWorkflowForm").submit();
    }
</script>


<?php
#--------------------------------------
# Rename task dialog
#--------------------------------------
?>
<script>
$(function() {
    // Rename workflow task form
    renameForm = $("#renameForm").dialog({
        autoOpen: false,
        height: 220,
        width: 450,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Rename task": function() {renameForm.submit();}
        },
        title: "Rename task"
    });

    <?php
    # Set up click event handlers for the Rename Task  buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        echo '$("#renameTask' . $row . '").click({key: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($key)
            . '", projectId: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            . '"}, renameTask);' . "\n";
        $row++;
    }
    ?>
    
    function renameTask(event) {
        var key = event.data.key;
        var projectId = event.data.projectId;
        event.preventDefault();

        $("#taskToRename").text('"'+projectId+'"');
        $('#renameProjectId').val(projectId);
        $('#renameTaskKey').val(key);
        $("#renameForm").dialog("open");
    }
});
</script>
<div id="renameDialog"
    title="Task Rename"
    style="display: none;"
    >
    <form id="renameForm" action="<?php echo $selfUrl;?>" method="post">
    To rename the task for Project ID <span id="taskToRename" style="font-weight: bold;"></span>,
    enter the new name below, and click on the
    <span style="font-weight: bold;">Rename task</span> button.
    <p>
    <span style="font-weight: bold;">New task name:</span>
    <input type="text" name="renameNewTaskName" id="renameNewTaskName" size="50">
    </p>
    <input type="hidden" name="renameTaskKey" id="renameTaskKey" value="">
    <input type="hidden" name="renameProjectId" id="renameProjectId" value="">
    <input type="hidden" name="submitValue" value="rename">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>


<?php
#--------------------------------------
# Specify ETL config dialog
#--------------------------------------
?>
<script>
$(function() {
    // Specify ETL config form
    etlConfigForm = $("#etlConfigForm").dialog({
        autoOpen: false,
        height: 220,
        width: 450,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Specify ETL": function() {etlConfigForm.submit();}
        },
        title: "Specify ETL"
    });

    <?php
    # Set up click event handlers for the Specify ETL Config  buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        $etlConfigs = $module->getAccessibleConfigurationNames($projectId);
        array_unshift($etlConfigs, '');
        echo '$("#specifyEtlConfig' . $row . '").click({key: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($key)
            . '", projectId: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            . '", etlConfigs: ['
            . "'"
            . implode("','", $etlConfigs)
            . "'"
            . ']}, specifyEtlConfig);' . "\n";
        $row++;
    }
    ?>
    
    function specifyEtlConfig(event) {
        var key = event.data.key;
        var projectId = event.data.projectId;
        var etlConfigs = event.data.etlConfigs;

        event.preventDefault();

        if (etlConfigs.length === 1) {
            etlConfigs[0] = 'No ETL configurations have been set up for this project';
            $(".ui-dialog-buttonpane button:contains('Specify')").button("disable");
        } else {
            //button needs to be enabled in case it was disabled via some other ETL task
            $(".ui-dialog-buttonpane button:contains('Specify')").button("enable");
        }
       
        $("#projectEtlConfigSelect").empty();
        for (const val of etlConfigs) {
            $("#projectEtlConfigSelect").append($(document.createElement('option')).prop({
                value: val,
                text: val
            }))
        }

        $("#taskEtlPid").text('"'+projectId+'"');
        $('#etlProjectId').val(projectId);
        $('#etlTaskKey').val(key);
        $("#etlConfigForm").dialog("open");
    }
});
</script>

<div id="specifyEtlConfigDialog"
    title="Specify ETL configuration"
    style="display: none;"
    >

    <form id="etlConfigForm" action="<?php echo $selfUrl;?>" method="post">
    To specify an ETL configuration for this task, select one of the ETL configurations for Project ID 
    <span id="taskEtlPid" style="font-weight: bold;"></span>, and click on the
    <span style="font-weight: bold;">Specify ETL</span> button. 
    <p>
    <span style="font-weight: bold;">ETL Configurations:</span>
    <select name="projectEtlConfigSelect" id="projectEtlConfigSelect">
    </select>

    </p>
    <input type="hidden" name="etlTaskKey" id="etlTaskKey" value="">
    <input type="hidden" name="etlProjectId" id="etlProjectId" value="">
    <input type="hidden" name="submitValue" value="etlConfig">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>

<?php
#--------------------------------------
# Delete workflow task dialog
#--------------------------------------
?>
<script>
$(function() {
    // Delete workflow task form
    deleteForm = $("#deleteForm").dialog({
        autoOpen: false,
        height: 170,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Delete task": function() {deleteForm.submit();}
        },
        title: "Delete Task"
    });
  
    <?php
    # Set up click event handlers for the Delete Task buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        echo '$("#deleteTask' . $row . '").click({key: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($key)
            . '", projectId: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            . '"}, deleteTask);' . "\n";
        $row++;
    }
    ?>
    
    function deleteTask(event) {
        var key = event.data.key;
        var projectId = event.data.projectId;
        event.preventDefault();
        $("#projectToDelete").text('"'+projectId+'"');
        $("#deleteProjectId").val(projectId);
        $('#deleteTaskKey').val(key);
        $("#deleteForm").dialog("open");
    }
});
</script>
<div id="deleteDialog"
    title="Task Delete"
    style="display: none;"
    >
    <form id="deleteForm" action="<?php echo $selfUrl;?>" method="post">
    To delete this task for Project Id <span id="projectToDelete" style="font-weight: bold;"></span>,
    click on the <span style="font-weight: bold;">Delete project</span> button.
    <input type="hidden" name="deleteTaskKey" id="deleteTaskKey" value="">
    <input type="hidden" name="deleteProjectId" id="deleteProjectId" value="">
    <input type="hidden" name="submitValue" value="delete">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>



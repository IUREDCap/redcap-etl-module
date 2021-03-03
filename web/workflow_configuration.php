<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;
use IU\RedCapEtlModule\ServerConfig;

$error   = '';
$warning = '';
$success = '';
$deleteTaskKey = null;
$deleteProjectId = null;

$propertiesProjectId = null;
$configureTaskKey = null;
$globalProperties = array();

$moveTaskKey = null;

$username = USERID;
$superUser = SUPER_USER;

$workflowName = Filter::escapeForHtml($_GET['workflowName']);

#Get the workflow's current task (project) list
$tasks = '';
$taskProjectIds = '';
if (!empty($workflowName)) {
    $workflowStatus = $module->getWorkflowStatus($workflowName);
    $tasks = $module->getWorkflow($workflowName, true);
    $taskProjectIds = array_keys($tasks);
    $allowableSequenceNumbers = range(1, count($taskProjectIds));
}
$allowableSequenceNumbers = range(1, count($taskProjectIds));

#Get projects that this user has access to
$db = new RedCapDb();
$userProjects = $db->getUserProjects($username);
$availableUserProjects = $userProjects;
array_unshift($availableUserProjects, '');

#$configuration = $module->getConfiguration($configurationName);
#    $exportRight = $module->getConfigurationExportRight($configuration);
#Authorization::hasEtlConfigurationPermission($module, $configuration)

#you are here, cleaning up code (namine -- task vs project, streamlining functions, etc., then permissions, then global parameters, then run, schedule for both workflow and etl)

$adminConfig  = $module->getAdminConfig();

$parentUrl    = $module->getUrl('web/workflows.php');
$selfUrl      = $module->getUrl('web/workflow_configuration.php')
                   .'&workflowName='.Filter::escapeForUrlParameter($workflowName);
$configureUrl = $module->getUrl("web/configure.php");
$globalPropertiesUrl = $module->getUrl("web/workflow_global_properties.php");
#$testUrl     = $module->getUrl("web/test.php");
#$scheduleUrl = $module->getUrl("web/schedule.php");
#$runUrl      = $module->getUrl("web/run.php");

try {
    #-----------------------------------------------------------
    # Check that the user has permission to access this page
    #-----------------------------------------------------------
    $module->checkUserPagePermission(USERID);

    #-----------------------------------------------------------------
    # Process form submissions
    #-----------------------------------------------------------------
    $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);
    $moveTaskKey = Filter::stripTags($_POST['moveTaskKey']);

    if (strcasecmp($submitValue, 'add') === 0) {
        #--------------------------------------
        # Add project
        #--------------------------------------
        if (!array_key_exists('newProject', $_POST) || empty($_POST['newProject'])) {
            $error = 'ERROR: No project was selected.';
        } else {
            $projectKey = Filter::stripTags($_POST['newProject']);
            $project = $availableUserProjects[$projectKey];
            $module->addProjectToWorkflow($workflowName, $project, $username);
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
        # Delete project
        #----------------------------------------------
        if (empty($warning) && empty($error)) {
            $deleteTaskKey = $_POST['deleteTaskKey'];
            if (isset($deleteTaskKey)) {
                $module->deleteTaskfromWorkflow($workflowName, $deleteTaskKey, $deleteProjectId, $username);
            }
        }
    } elseif (strcasecmp($submitValue, 'properties') === 0) {
        if (empty($warning) && empty($error)) {
            #----------------------------------------------
            # Update properties
            #----------------------------------------------
            header('Location: '.$globalPropertiesUrl);
            exit();
        }
    } elseif (strcasecmp($submitValue, 'etlConfig') === 0) {
        if (empty($warning) && empty($error)) {
            #----------------------------------------------
            # Specify ETL Config
            #----------------------------------------------
            $etlConfig = $_POST['projectEtlConfigSelect'];
            $etlTaskKey = $_POST['etlTaskKey'];
            $etlProjectId = $_POST['etlProjectId'];

            $module->assignWorkflowTaskEtlConfig(
                $workflowName,
                $etlProjectId,
                $etlTaskKey,
                $etlConfig,
                $username
            );
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
} catch (\Exception $exception) {
    $error = 'ERROR: '.$exception->getMessage();
}

#Get the workflow's updated tasks (project) list
$workflowStatus = $module->getWorkflowStatus($workflowName);
$tasks = $module->getWorkflow($workflowName, true);
$taskProjectIds = array_column($tasks, 'projectId');
$allowableSequenceNumbers = range(1, count($taskProjectIds));
?>

<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
?>

<!--
<script>
$(function() {
    $(document).on("change", '#updateWorkflowForm', (function() {
        alert('This works');
    });
});
</script>


$(function() {
    $("#updateWorkflowForm").on("change", "input:checkbox", function(e){
console.log("IN FUNCTION");
        e.preventDefault();
        document.getElementById("updateWorkflowForm").submit();
    });
});

    $(document).ready(function() {
        $("#updateWorkflowForm").on("change", "input:checkbox", function(){
            $("#updateWorkflowForm").submit();
        });
    });
</script>
-->


<div class="projhdr"> <!--h4 style="color:#800000;margin:0 0 10px;"> -->
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png" alt="">REDCap-ETL
</div>

<?php

$adminConfig  = $module->getAdminConfig();

$parentUrl    = $module->getUrl('web/workflows.php');
$selfUrl      = $module->getUrl('web/workflow_configuration.php')
                   .'&workflowName='.Filter::escapeForUrlParameter($workflowName);
$configureUrl = $module->getUrl("web/configure.php");
$globalPropertiesUrl = $module->getUrl("web/workflow_global_properties.php");
#$testUrl     = $module->getUrl("web/test.php");
#$scheduleUrl = $module->getUrl("web/schedule.php");
#$runUrl      = $module->getUrl("web/run.php");

$module->renderProjectPageContentHeader($configureUrl, $error, $warning, $success);
?>

<div  style="padding: 4px; margin-bottom: 20px; border: 1px solid #ccc; background-color: #ccc;">
    <div>
     <span style="font-weight: bold;">Workflow:</span>
        <?php echo $workflowName ?>
     </div>
     <div>
     <span style="font-weight: bold;">Workflow Status:</span>
        <?php echo $workflowStatus ?>
     </div>
</div>

<?php
#------------------------------------------------------------
# Add project form
#------------------------------------------------------------
if (!empty($availableUserProjects)) {
?>
<form action="<?php echo $selfUrl;?>" method="post" style="margin-bottom: 12px;" name="addProjectForm" id="addProjectForm">
    <label for="newProject">REDCap project:</label>
    <select name="newProject" id="newProject"> 
    <?php
    foreach ($availableUserProjects as $key => $userProject) {
        if ($userProject) {
            echo '<option value="'.Filter::escapeForHtmlAttribute($key).'">'
                .Filter::escapeForHtml($userProject['project_id'].'-'.$userProject['app_title'])."</option>\n";
        } else {
            echo '<option value=""></option>'."\n";
        }
    }
    ?>
    </select>
    <input type="submit" name="submitValue" value="Add" />
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
<table class="user-projects">
    <thead>
        <tr>
            <?php
            if ($numTasks > 1) {
                echo "<th></th>";
            }
            ?>
            <th>PID</th><th>Project</th><th>Task Name</th>
            <th>Project ETL Config</th><th>Rename<br/>Task</th>
            <th>Specify<br/>ETL Config</th><th>Global<br/>Properties</th><th>Delete<br/>Project</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $row = 1;
        foreach ($tasks as $taskKey => $task) {
            $projectId = $task['projectId'];
            $pKey = array_search($projectId, array_column($userProjects, 'project_id'));
            $noAccess = !($pKey > 0);
            if ($superUser) {
                $noAccess = false;
            }
            $hasApiExportPermission = (isset($pKey) && ($userProjects[$pKey]['api_export'] == 1 ? true : false))
                    || $superUser;

            if ($noAccess) {
                $projectName = "{Project $projectId}";
            } else {
                $projectName = $userProjects[$pKey]['app_title'];
            }

            $taskName = $task['taskName'];
            $projectEtlConfig = $task['projectEtlConfig'];
            $projectEtlConfig = $projectEtlConfig ? $projectEtlConfig : "No ETL configurations yet";
            
            if ($row % 2 == 0) {
                echo '<tr class="even-row">'."\n";
            } else {
                echo '<tr class="odd-row">'."\n";
            }
            
            if ($numTasks > 1) {
                    echo '<td style="text-align:left;"> '
                        .'<input type="image" src="'.APP_PATH_IMAGES.'arrow_down.png" alt="MOVE DOWN"'
                        .' class="deleteConfig" style="cursor: pointer;"'
                        .' id="moveTaskDown'.$row.'"/>';
                    echo'<input type="image" src="'.APP_PATH_IMAGES.'arrow_up2.png" alt="MOVE UP"'
                        .' class="deleteConfig" style="margin-left: 5px; cursor: pointer;"'
                        .' id="moveTaskUp'.$row.'"/> ';
                    echo "</td>\n";
            }

            echo "<td>".Filter::escapeForHtml($projectId)."</td>\n";
            echo "<td>".Filter::escapeForHtml($projectName)."</td>\n";
            echo "<td>".Filter::escapeForHtml($taskName)."</td>\n";
            echo "<td>".Filter::escapeForHtml($projectEtlConfig)."</td>\n";

            $selectDisabled = "";
            if (!$hasApiExportPermission) {
                $selectDisabled = "disabled";
            }
            if (!isset($pKey)) {
                $selectDisabled = "disabled";
            }
            if ($pKey || $pKey === 0) {
            } else {
                $selectDisabled = "disabled";
            }

            #-----------------------------------------------------------
            # RENAME TASK BUTTON - disable if user does not have the needed
            # data export permission to access the project
            #-----------------------------------------------------------
            if ($hasApiExportPermission && (!$noAccess)) {
                echo '<td style="text-align:center;">'
                    .'<input type="image" src="'.APP_PATH_IMAGES.'page_white_edit.png" alt="RENAME TASK"'
                    .' style="cursor: pointer;"'
                    .' id="renameTask'.$row
                    .'"/>'
                    ."</td>\n";
            } else {
                echo '<td style="text-align:center;">'
                    .'<img src="'.APP_PATH_IMAGES.'gear.png" alt="ETL GLOBAL VARIABLES" class="disabled" />'
                    ."</td>";
            }

            #-----------------------------------------------------------
            # SPECIFY ETL CONFIG BUTTON - disable if user does not have the needed
            # data export permission to access the project
            #-----------------------------------------------------------
            $values = $module->getAccessibleConfigurationNames($projectId);
            if ($hasApiExportPermission && (!$noAccess)) {
                echo '<td style="text-align:center;">'
                    .'<input type="image" src="'.APP_PATH_IMAGES.'page_white_edit.png" alt="RENAME TASK"'
                    .' style="cursor: pointer;"'
                    .' id="specifyEtlConfig'.$row
                    .'"/>'
                    ."</td>\n";
            } else {
                echo '<td style="text-align:center;">'
                    .'<img src="'.APP_PATH_IMAGES.'gear.png" alt="ETL GLOBAL VARIABLES" class="disabled" />'
                    ."</td>";
            }

            #-----------------------------------------------------------
            # GLOBAL PROPERTIES BUTTON - disable if user does not have the needed
            # data export permission to access the project
            #-----------------------------------------------------------
            if ($hasApiExportPermission && (!$noAccess)) {
                echo '<td style="text-align:center;">'
                    .'<input type="image" src="'.APP_PATH_IMAGES.'gear.png" alt="ETL GLOBAL PROPERTIES"'
                    .' style="cursor: pointer;"'
                    .' id="globalProperties'.$row
                    .'"/>'
                    ."</td>\n";
            } else {
                echo '<td style="text-align:center;">'
                    .'<img src="'.APP_PATH_IMAGES.'gear.png" alt="ETL GLOBAL VARIABLES" class="disabled" />'
                    ."</td>";
            }

            #-----------------------------------------------------------
            # DELETE BUTTON - disable if user does not have the needed
            # data export permission to access the project
            #-----------------------------------------------------------
            if ($hasApiExportPermission && (!$noAccess)) {
                echo '<td style="text-align:center;">'
                    .'<input type="image" src="'.APP_PATH_IMAGES.'delete.png" alt="DELETE"'
                    .' class="deleteConfig" style="cursor: pointer;"'
                    .' id="deleteProject'.$row
                    .'"/>'
                    ."</td>\n";
            } else {
                echo '<td style="text-align:center;">'
                    .'<img src="'.APP_PATH_IMAGES.'delete.png" alt="DELETE" class="disabled" />'
                    ."</td>\n";
            }
            echo "</tr>\n";
            $row++;
        }
        ?>
    </tbody>
</table>
<hr />
<input type="hidden" name="moveTaskKey" id="moveTaskKey" value="">
<input type="hidden" name="submitValue" id="moveTask" value="">
<p>
<div style="clear: both;"></div>
</p>
<?php Csrf::generateFormToken(); ?>
</form>


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
        echo '$("#moveTaskUp'.$row.'").click({key: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($key)
            .'", projectId: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            .'"}, moveTaskUp);'."\n";
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
        echo '$("#moveTaskDown'.$row.'").click({key: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($key)
            .'", projectId: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            .'"}, moveTaskDown);'."\n";
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
        echo '$("#renameTask'.$row.'").click({key: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($key)
            .'", projectId: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            .'"}, renameTask);'."\n";
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
        echo '$("#specifyEtlConfig'.$row.'").click({key: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($key)
            .'", projectId: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            .'", etlConfigs: ['
            ."'"
            .implode("','", $etlConfigs)
            ."'"
            .']}, specifyEtlConfig);'."\n";
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

        $("#taskEtlConfig").text('"'+projectId+'"');
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
    <span id="taskEtlConfig" style="font-weight: bold;"></span>, and click on the
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
# Delete workflow project dialog
#--------------------------------------
?>
<script>
$(function() {
    // Delete workflow project form
    deleteForm = $("#deleteForm").dialog({
        autoOpen: false,
        height: 170,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Delete project": function() {deleteForm.submit();}
        },
        title: "Delete Project"
    });
  
    <?php
    # Set up click event handlers for the Delete Project buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        echo '$("#deleteProject'.$row.'").click({key: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($key)
            .'", projectId: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            .'"}, deleteProject);'."\n";
        $row++;
    }
    ?>
    
    function deleteProject(event) {
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
    title="Project Delete"
    style="display: none;"
    >
    <form id="deleteForm" action="<?php echo $selfUrl;?>" method="post">
    To delete Project Id <span id="projectToDelete" style="font-weight: bold;"></span>,
    click on the <span style="font-weight: bold;">Delete project</span> button.
    <input type="hidden" name="deleteTaskKey" id="deleteTaskKey" value="">
    <input type="hidden" name="deleteProjectId" id="deleteProjectId" value="">
    <input type="hidden" name="submitValue" value="delete">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>

<?php
#--------------------------------------
# Global properties dialog
#--------------------------------------
?>
<script>
$(function() {
    // Task global properies form
    globalProperiesForm = $("#globalProperiesForm").dialog({
        autoOpen: false,
        height: 225,
        width: 500,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Assign Property": function() {globalProperiesForm.submit();}
        },
        title: "ETL Global Properties"
    });
  
    <?php
    # Set up click event handlers for the Global Properties buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        echo '$("#globalProperties'.$row.'").click({key: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($key)
            .'", projectId: "'
            .Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            .'"}, addGlobalProperties);'."\n";
        $row++;
    }
    ?>

    function addGlobalProperties(event) {
        event.preventDefault();
        var key = event.data.key;
        var projectId = event.data.projectId;
        $("#propertiesForProject").text('"'+projectId+'"');
        $("#propertiesProjectId").val(projectId);
        $('#configureTaskKey').val(key);
        $("#globalProperiesForm").dialog("open");
    }
});
</script>

<div id="propertiesDialog"
    title="Global Properties"
    style="display: none;"
    >
    <form id="globalProperiesForm" action="<?php echo $selfUrl;?>" method="post">
    <div>Reminder: Any global properties you assign for a task will override those in the ETL configuration for that task. <br /><br />To continue to assign global ETL properties for Project Id <span id="propertiesForProject" style="font-weight: bold;"></span>, click on the <span style="font-weight: bold;">Assign Global Properties</span> button.
    </div>
    <input type="hidden" name="configureTaskKey" id="configureTaskKey" value="">
    <input type="hidden" name="propertiesProjectId" id="propertiesProjectId" value="">
    <input type="hidden" name="submitValue" value="properties">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

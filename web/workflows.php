<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__ . '/../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

$error   = '';
$warning = '';
$success = '';

$deleteWorkflowName =  '';
$copyFromWorkflowName = '';
$copyToWorkflowName = '';
$renameWorkflowName = '';
$renameNewWorkflowName = '';
$workflowName = '';

try {
    #-----------------------------------------------------------
    # Check that the user has permission to access this page
    #-----------------------------------------------------------
    $module->checkUserPagePermission(USERID);

    #-----------------------------------------------------------------
    # Process form submissions (workflow add/copy/delete/rename)
    #-----------------------------------------------------------------
    $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);
    if (strcasecmp($submitValue, 'Add') === 0) {
        #--------------------------------------
        # Add workflow
        #--------------------------------------
        if (!array_key_exists('workflowName', $_POST) || !isset($_POST['workflowName'])) {
            $error = 'ERROR: No workflow name was specified.';
        } else {
            $workflowName = Filter::stripTags($_POST['workflowName']);
            
            # Make sure workflow name is validated before it is used
            Configuration::validateName($workflowName);
            
            # Add workflow; an exception should be thrown if the workflow
            # already exists
            $module->addWorkflow($workflowName);
        }
    } elseif (strcasecmp($submitValue, 'copy') === 0) {
        #--------------------------------------------
        # Copy workflow
        #--------------------------------------------
        $copyFromWorkflowName = Filter::stripTags($_POST['copyFromWorkflowName']);
        $copyToWorkflowName   = Filter::stripTags($_POST['copyToWorkflowName']);
        if (isset($copyFromWorkflowName) && isset($copyToWorkflowName)) {
            # Make sure config names are validated before it is used
            Configuration::validateName($copyFromWorkflowName);
            Configuration::validateName($copyToWorkflowName);
                        
            $module->copyWorkflow($copyFromWorkflowName, $copyToWorkflowName);
        }
    } elseif (strcasecmp($submitValue, 'delete') === 0) {
        #---------------------------------------------
        # Delete workflow
        #---------------------------------------------
        $deleteWorkflowName = Filter::stripTags($_POST['deleteWorkflowName']);
        $hasPermissions = $module->hasPermissionsForAllTasks($deleteWorkflowName, USERID);
        if (isset($deleteWorkflowName)) {
            # Make sure workflow name is validated before it is used
            Configuration::validateName($deleteWorkflowName);

            $hasPermissions = $module->hasPermissionsForAllTasks($deleteWorkflowName, USERID);
            if ($hasPermissions) {
                $module->deleteUserWorkflow($deleteWorkflowName, USERID);
            } else {
                $module->deleteUserWorkflow($deleteWorkflowName, USERID);
            }
        }
    } elseif (strcasecmp($submitValue, 'rename') === 0) {
        #----------------------------------------------
        # Rename workflow
        #----------------------------------------------
        $renameWorkflowName    = Filter::stripTags($_POST['renameWorkflowName']);
        $renameNewWorkflowName = Filter::stripTags($_POST['renameNewWorkflowName']);
        if (isset($renameWorkflowName) && isset($renameNewWorkflowName)) {
            # Make sure names are validated before it is used
            Configuration::validateName($renameWorkflowName);
            Configuration::validateName($renameNewWorkflowName);
            
            $module->renameWorkflow($renameWorkflowName, $renameNewWorkflowName);
        }
    }
} catch (\Exception $exception) {
    $error = 'ERROR: ' . $exception->getMessage();
}

?>

<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="' . $cssFile . '" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    " . $link . "\n</head>", $buffer);
echo $buffer;
?>

<div class="projhdr"> <!--h4 style="color:#800000;margin:0 0 10px;"> -->
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png" alt="">REDCap-ETL
</div>

<?php

$workflowNames = $module->getProjectAvailableWorkflows();
$adminConfig   = $module->getAdminConfig();

$selfUrl       = $module->getUrl(RedCapEtlModule::WORKFLOWS_PAGE);
$configUrl     = $module->getUrl(RedCapEtlModule::USER_ETL_CONFIG_PAGE);
$testUrl       = $module->getUrl('web/test.php');
$scheduleUrl   = $module->getUrl('web/schedule.php');
$runUrl        = $module->getUrl(RedCapEtlModule::RUN_PAGE);

$userEtlProjects = $module->getUserEtlProjects();
$projectId = $module->getProjectId();
$module->renderProjectPageContentHeader($selfUrl, $error, $warning, $success);
?>


<?php
#------------------------------------------------------------
# Add workflows form
#------------------------------------------------------------
?>
<form action="<?=$selfUrl;?>" method="post" style="margin-bottom: 12px;">
    <label for="workflowName">REDCap-ETL Workflow name:</label>
    <input name="workflowName" id="workflowName" type="text" size="40" />
    <input type="submit" name="submitValue" value="Add" />
    <?php Csrf::generateFormToken(); ?>
</form>

<table class="dataTable">
<thead>
<tr class="hrd">

    <th>Workflow Name</th>
    <th>Configure</th>
    <!-- <th>Test</th> -->
    <?php
    
    if ($adminConfig->getAllowOnDemand()) {
        echo "<th>Run</th>\n";
    }

    if ($adminConfig->getAllowCron()) {
        echo "<th>Schedule</th>\n";
    }
    ?>
    <th>Copy</th>
    <th>Rename</th>
    <th>Delete</th>

</tr>
</thead>


<tbody>
<?php
#Determine if the user has permissions to export for this project
$hasPermissionToExport = false;
$superUser = SUPER_USER;
if ($superUser) {
    $hasPermissionToExport = true;
} else {
    $db = new RedCapDb();
    $username = USERID;
    $availableUserProjects = $db->getUserProjects($username);
    $pid = Filter::escapeForHtmlAttribute($_GET["pid"]);
    $pKey = array_search($pid, $availableUserProjects);
    $hasPermissionToExport = $availableUserProjects[$pKey]['data_export_tool'] == 1 ? true : false;
}

#--------------------------------------------------------------
# Displays rows of table of workflows that contain the project
#--------------------------------------------------------------
$row = 1;
foreach ($workflowNames as $workflowName) {
    if ($row % 2 === 0) {
        echo '<tr class="even">' . "\n";
    } else {
        echo '<tr class="odd">' . "\n";
    }
    
    echo "<td>" . Filter::escapeForHtml($workflowName) . "</td>\n";
    
    #-------------------------------------------------------------------------------------
    # CONFIGURE BUTTON - disable if user does not have permission to access the project
    #-------------------------------------------------------------------------------------
    if ($hasPermissionToExport) {
        $configureUrl = $configUrl
            . '&workflowName=' . Filter::escapeForUrlParameter($workflowName)
            . '&configType=workflow'
            ;
        echo '<td style="text-align:center;">'
            . '<a href="' . $configureUrl . '">'
            . '<img alt="CONFIG" src="' . APP_PATH_IMAGES . 'gear.png"></a>'
            . "</td>\n";
    } else {
        echo '<td style="text-align:center;">'
            . '<img src="' . APP_PATH_IMAGES . 'gear.png" alt="CONFIG" class="disabled">'
            . "</td>\n";
    }
    
    #--------------------------------------------------------------------------------------
    # RUN BUTTON - display if running on demand allowed, but disable if user does not have
    # the needed data export permission to access the configuration
    #--------------------------------------------------------------------------------------
    if ($adminConfig->getAllowOnDemand()) {
        if ($hasPermissionToExport) {
            $runConfigurationUrl = $runUrl . '&workflowName=' . Filter::escapeForUrlParameter($workflowName)
                . '&configType=workflow';
            echo '<td style="text-align:center;">'
                . '<a href="' . $runConfigurationUrl . '"><img src="' . APP_PATH_IMAGES
                . 'application_go.png" alt="RUN"></a>'
                . "</td>\n";
        } else {
            echo '<td style="text-align:center;">'
                . '<img src="' . APP_PATH_IMAGES . 'application_go.png"  alt="RUN" class="disabled">'
                . "</td>\n";
        }
    }

    #--------------------------------------------------------------------------------------
    # SHEDULE BUTTON - display if ETL cron jobs allowed, but disable if user does not have
    # the needed data export permission to access the configuration
    #--------------------------------------------------------------------------------------
    if ($adminConfig->getAllowCron()) {
        if ($hasPermissionToExport) {
            $scheduleConfigurationUrl = $scheduleUrl . '&workflowName=' . Filter::escapeForUrlParameter($workflowName)
                . '&configType=workflow';
            echo '<td style="text-align:center;">'
                . '<a href="' . $scheduleConfigurationUrl . '"><img src="' . APP_PATH_IMAGES
                . 'clock_frame.png" alt="SCHEDULE"></a>'
                . "</td>\n";
        } else {
            echo '<td style="text-align:center;">'
                . '<img src="' . APP_PATH_IMAGES . 'clock_frame.png" alt="SCHEDULE" class="disabled">'
                . "</td>\n";
        }
    }

    #-----------------------------------------------------------
    # COPY BUTTON - disable if user does not have the needed
    # data export permission to access the project
    #-----------------------------------------------------------
    if ($hasPermissionToExport) {
        echo '<td style="text-align:center;">'
            . '<input type="image" src="' . APP_PATH_IMAGES . 'page_copy.png" alt="COPY"'
            . ' class="copyConfig" style="cursor: pointer;"'
            . ' id="copyWorkflow' . $row . '"/>'
            . "</td>\n";
    } else {
        echo '<td style="text-align:center;">'
           . '<img src="' . APP_PATH_IMAGES . 'page_copy.png" alt="COPY" class="disabled" />'
           . "</td>\n";
    }
    
    #-----------------------------------------------------------
    # RENAME BUTTON - disable if user does not have the needed
    # data export permission to access the project
    #-----------------------------------------------------------
    if ($hasPermissionToExport) {
        echo '<td style="text-align:center;">'
            . '<input type="image" src="' . APP_PATH_IMAGES . 'page_white_edit.png" alt="RENAME"'
            . ' class="renameConfig" style="cursor: pointer;"'
            . ' id="renameWorkflow' . $row . '"/>'
            . "</td>\n";
    } else {
        echo '<td style="text-align:center;">'
            . '<img src="' . APP_PATH_IMAGES . 'page_white_edit.png" alt="RENAME" class="disabled" />'
            . "</td>\n";
    }

    #-----------------------------------------------------------
    # REMOVE BUTTON - disable if user does not have the needed
    # data export permission to access the project
    #-----------------------------------------------------------
    if ($hasPermissionToExport) {
        echo '<td style="text-align:center;">'
            . '<input type="image" src="' . APP_PATH_IMAGES . 'delete.png" alt="REMOVE"'
            . ' class="deleteConfig" style="cursor: pointer;"'
            . ' id="deleteWorkflow' . $row . '"/>'
            . "</td>\n";
    } else {
        echo '<td style="text-align:center;">'
            . '<img src="' . APP_PATH_IMAGES . 'delete.png" alt="REMOVE" class="disabled" />'
            . "</td>\n";
    }
    
    echo "</tr>\n";
    $row++;
}

?>
</tbody>
</table>
<!-- ******************************-->
<br />

<?php
#--------------------------------------
# Copy workflow dialog
#--------------------------------------
?>
<script>
$(function() {
    copyForm = $("#copyForm").dialog({
        autoOpen: false,
        height: 220,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Copy workflow": function() {copyForm.submit(); $(this).dialog("close");}
        },
        title: "Copy workflow"
    });
    
    <?php
    # Set up click event handlers for the Copy Workflow buttons
    $row = 1;
    foreach ($workflowNames as $workflowName) {
        echo '$("#copyWorkflow' . $row . '").click({fromWorkflow: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($workflowName)
            . '"}, copyWorkflow);' . "\n";
        $row++;
    }
    ?>
    
    function copyWorkflow(event) {
        var workflowName = event.data.fromWorkflow;
        $("#workflowToCopy").text('"'+workflowName+'"');
        $('#copyFromWorkflowName').val(workflowName);
        $("#copyForm").dialog("open");
    }
});
</script>
<div id="copyDialog"
    title="Workflow Copy"
    style="display: none;"
    >
    <form id="copyForm" action="<?php echo $selfUrl;?>" method="post">
    To copy the workflow <span id="workflowToCopy" style="font-weight: bold;"></span>,
    enter the name of the new workflow below, and click on the
    <span style="font-weight: bold;">Copy workflow</span> button.
    <p>
    <span style="font-weight: bold;">New workflow name:</span>
    <input type="text" name="copyToWorkflowName" id="copyToWorkflowName">
    </p>
    <input type="hidden" name="copyFromWorkflowName" id="copyFromWorkflowName" value="">
    <input type="hidden" name="submitValue" value="copy">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>

<?php
#--------------------------------------
# Rename workflow dialog
#--------------------------------------
?>
<script>
$(function() {
    // Rename workflow form
    renameForm = $("#renameForm").dialog({
        autoOpen: false,
        height: 220,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Rename Workflow": function() {renameForm.submit();}
        },
        title: "Rename Workflow"
    });

    <?php
    # Set up click event handlers for the Rename Workflow buttons
    $row = 1;
    foreach ($workflowNames as $workflowName) {
        echo '$("#renameWorkflow' . $row . '").click({workflowName: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($workflowName)
            . '"}, renameWorkflow);' . "\n";
        $row++;
    }
    ?>
    
    function renameWorkflow(event) {
        var workflowName = event.data.workflowName;
        $("#workflowToRename").text('"'+workflowName+'"');
        $('#renameWorkflowName').val(workflowName);
        $("#renameForm").dialog("open");
    }
});
</script>
<div id="renameDialog"
    title="Workflow Rename"
    style="display: none;"
    >
    <form id="renameForm" action="<?php echo $selfUrl;?>" method="post">
    To rename the workflow <span id="workflowToRename" style="font-weight: bold;"></span>,
    enter the new name for the new workflow below, and click on the
    <span style="font-weight: bold;">Rename workflow</span> button.
    <p>
    <span style="font-weight: bold;">New workflow name:</span>
    <input type="text" name="renameNewWorkflowName" id="renameNewWorkflowName">
    </p>
    <input type="hidden" name="renameWorkflowName" id="renameWorkflowName" value="">
    <input type="hidden" name="submitValue" value="rename">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>


<?php
#--------------------------------------
# Delete workflow dialog
#--------------------------------------
?>
<script>
$(function() {
    // Delete ETL workflow form
    deleteForm = $("#deleteForm").dialog({
        autoOpen: false,
        height: 170,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Delete workflow": function() {deleteForm.submit();}
        },
        title: "Delete Workflow"
    });
  
    <?php
    # Set up click event handlers for the Delete Workflow buttons
    $row = 1;
    foreach ($workflowNames as $workflowName) {
        echo '$("#deleteWorkflow' . $row . '").click({workflowName: "'
           . Filter::escapeForJavaScriptInDoubleQuotes($workflowName)
           . '"}, deleteWorkflow);' . "\n";
        $row++;
    }
    ?>
    
    function deleteWorkflow(event) {
        var workflowName = event.data.workflowName;
        $("#workflowToDelete").text('"'+workflowName+'"');
        $('#deleteWorkflowName').val(workflowName);
        $("#deleteForm").dialog("open");
    }
});
</script>
<div id="deleteDialog"
    title="Workflow Delete"
    style="display: none;"
    >
    <form id="deleteForm" action="<?php echo $selfUrl;?>" method="post">
    To delete the Workflow configuration <span id="workflowToDelete" style="font-weight: bold;"></span>,
    click on the <span style="font-weight: bold;">Delete workflow</span> button.
    <input type="hidden" name="deleteWorkflowName" id="deleteWorkflowName" value="">
    <input type="hidden" name="submitValue" value="delete">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>



<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>


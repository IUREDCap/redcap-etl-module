<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

#---------------------------------------------
# Check that the user has access permission
#---------------------------------------------
$module->checkAdminPagePermission();


require_once __DIR__ . '/../../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapEtlModule;
use IU\RedCapEtlModule\ServerConfig;

$username = USERID;
$selfUrl = $module->getUrl(RedCapEtlModule::ADMIN_WORKFLOWS_PAGE);

$submit = Filter::sanitizeLabel($_POST['submit']);
$searchText = null;
if ($submit === 'Search') {
    $searchText = Filter::sanitizeString($_POST['search-text']);
}

$deleteWorkflowName = Filter::sanitizeString($_POST['delete-workflow-name']);
if (!empty($deleteWorkflowName) || $deleteWorkflowName === '0') {
    $module->deleteWorkflow($deleteWorkflowName, $username);
}

$reinstateWorkflowName = Filter::sanitizeString($_POST['reinstate-workflow-name']);
if (!empty($reinstateWorkflowName || $reinstateWorkflowName === '0')) {
    $module->reinstateWorkflow($reinstateWorkflowName, $username);
}

$workflows = $module->getworkflows();
?>

<?php #require_once APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>
<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="' . $cssFile . '" rel="stylesheet" type="text/css" media="all">';
$jsInclude = '<script type="text/javascript" src="' . ($module->getUrl('resources/workflows.js')) . '"></script>';
$buffer = str_replace('</head>', "    {$link}\n{$jsInclude}\n</head>", $buffer);

echo $buffer;
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png" alt="">REDCap-ETL Admin</h4>


<?php
#-------------------------------------------------
# Render page content header (tabs and messages)
#-------------------------------------------------
$module->renderAdminPageContentHeader($selfUrl, $error, $warning, $success);
?>



<form action="<?php echo $selfUrl;?>" method="post" style="margin-bottom: 12px;">
Workflow name: <input type="text" id="search-text" name="search-text" size="40">
<input type="submit" name="submit" value="Search"><br />
<?php Csrf::generateFormToken(); ?>
</form>
    <!--
<div class="ui-widget">
  <label for="user">User: </label>
  <input type="text" id="user-search" size="40">
</div>
-->

<table class="dataTable">
  <thead>
    <tr> <th>Workflow Name</th> <th>Status</th> </th><th>Last Updated<th>Configure</th>
    <th>Reinstate</th><th>Delete</th></tr>
  </thead>
  <tbody>
    <?php
    $i = 0;
    $row = 1;
    foreach ($workflows as $workflowName => $workflow) {
        $display = true;
        if (!empty($searchText || $searchText === '0')) {
            if (strpos(strtoupper($workflowName), strtoupper($searchText)) === false) {
                $display = false;
            }
        }
        
        if ($display) {
            if ($row % 2 == 0) {
                echo "<tr class=\"even\">\n";
            } else {
                echo "<tr class=\"odd\">\n";
            }
            echo '<td>' . Filter::escapeForHtml($workflowName) . "</td>\n";

            #-------------------------------
            # Status
            #-------------------------------
            $status = $workflow['metadata']['workflowStatus'];
            echo '<td style="text-align:center;">' . $status . "</td>\n";

            #-------------------------------
            # Last updated by/date
            #-------------------------------
            $dateUpdated = substr($workflow['metadata']['dateUpdated']['date'], 0, 10);
            if (empty($dateUpdated)) {
                $dateUpdated = substr($workflow['metadata']['dateAdded']['date'], 0, 10);
            }

            $updatedBy = $workflow['metadata']['updatedBy'];
            if (empty($updatedBy)) {
                $updatedBy = $workflow['metadata']['addedBy'];
            }
            if (!empty($updatedBy)) {
                $updatedBy = ' [' . $updatedBy . ']';
            }

            if ($updatedBy || $dateUpdated) {
                echo '<td>' . $dateUpdated . $updatedBy;
            } else {
                echo '<td>';
            }
            echo "</td>\n";

            #-------------------------------
            # Configure
            #-------------------------------
            #get the first project since the workflow config url requires a project id
            $pid = array_column($workflow, 'projectId')[0];
            $workflowConfigUrl = $module->getURL(RedCapEtlModule::WORKFLOW_CONFIG_PAGE
                . '?pid=' . Filter::escapeForUrlParameter($pid)
                . '&workflowName=' . Filter::escapeForUrlParameter($workflowName));
            echo '<td style="text-align:center;">'
                . '<a href="' . $workflowConfigUrl . '">'
                . '<img src="' . APP_PATH_IMAGES . 'gear.png" alt="CONFIG"></a>'
                . "</td>\n";

            #-------------------------------
            # Reinstate (take out of 'Removed' status)
            #-------------------------------
            if ($status === RedCapEtlModule::WORKFLOW_REMOVED) {
                echo '<td style="text-align:center;">'
                    . '<input type="image" src="' . APP_PATH_IMAGES . 'tick.png" alt="REINSTATE"'
                    . ' id="reinstateWorkflow' . $row . '"'
                    . ' class="renameServer" style="cursor: pointer;">'
                    . "</td>\n";
            } else {
                echo "<td> </td>\n";
            }
         
            #-------------------------------
            # Delete
            #-------------------------------
            echo '<td style="text-align:center;">'
                .  '<input type="image" src="' . APP_PATH_IMAGES . 'delete.png" alt="DELETE"'
                . ' id="deleteWorkflow' . $row . '"'
                . ' class="deleteServer" style="cursor: pointer;">'
                . "</td>\n";
              
            echo "</tr>\n";
        }
        
        $row++;
    }
    ?>
  </tbody>
</table>

<?php
#--------------------------------------
# Reinstate workflow dialog
#--------------------------------------
?>
<div id="reinstate-dialog"
    title="Reinstate Workflow Configuration"
    style="display: none;"
    >
    <form id="reinstate-form" action="<?php echo $selfUrl;?>" method="post">
    To reinstate the workflow <span id="workflow-to-reinstate" style="font-weight: bold;"></span>
    (i.e., remove it from 'Removed' status), click on the 
    <span style="font-weight: bold;">Reinstate workflow</span> button.
    <input type="hidden" name="reinstate-workflow-name" id="reinstate-workflow-name" value="">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>

<?php
#--------------------------------------
# Delete workflow dialog
#--------------------------------------
?>
<div id="delete-dialog"
    title="Workflow Configuration Delete"
    style="display: none;"
    >
    <form id="delete-form" action="<?php echo $selfUrl;?>" method="post">
    To delete the workflow <span id="workflow-to-delete" style="font-weight: bold;"></span>,
    click on the <span style="font-weight: bold;">Delete workflow</span> button.
    <input type="hidden" name="delete-workflow-name" id="delete-workflow-name" value="">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>

<?php

#-----------------------------------------------------------------------
# Set up click event handlers for the workflow reinstate/delete buttons
#-----------------------------------------------------------------------
echo "<script>\n";

$row = 1;
foreach ($workflows as $workflowName => $workflow) {
    echo '$("#reinstateWorkflow' . $row . '").click({workflow: "'
        . Filter::escapeForJavaScriptInDoubleQuotes($workflowName)
        . '"}, RedCapEtlModule.reinstateWorkflow);' . "\n";
    echo '$("#deleteWorkflow' . $row . '").click({workflow: "'
        . Filter::escapeForJavaScriptInDoubleQuotes($workflowName)
        . '"}, RedCapEtlModule.deleteWorkflow);' . "\n";
    $row++;
}

echo "</script>\n";
?>

<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

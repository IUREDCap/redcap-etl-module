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

$selfUrl = $module->getUrl(RedCapEtlModule::ADMIN_WORKFLOWS_PAGE);
$configUrl = $module->getUrl(RedCapEtlModule::WORKFLOW_CONFIG_PAGE);

$deleteWorkflowName = Filter::sanitizeString($_POST['delete-workflow-name']);
if (!empty($deleteWorkflowName)) {
    $module->deleteWorkflow($deleteWorkflowName);
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
$jsInclude = '<script type="text/javascript" src="' . ($module->getUrl('resources/servers.js')) . '"></script>';
$buffer = str_replace('</head>', "    {$link}\n{$jsInclude}\n</head>", $buffer);

echo $buffer;
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png" alt="">REDCap-ETL Admin</h4>


<?php
#-------------------------------------------------
# Render page content header (tabs and messages)
#-------------------------------------------------
$module->renderAdminPageContentHeader($selfUrl, $error, $warning, $success);
$module->renderAdminWorkflowsSubTabs($selfUrl);
?>

<table class="dataTable">
  <thead>
    <tr> <th>Workflow Name</th> <th>Status</th> </th><th>Last Updated By/Date</th><th>Configure</th>
    </th><th>Delete</th> </th></tr>
  </thead>
  <tbody>
    <?php
    $row = 1;
    foreach ($workflows as $workflowName => $workflow) {
        #get the first project since the workflow config url requires a project id
        $pid = array_column($workflow,'projectId')[0];
        $workflowConfigUrl = $module->getURL(RedCapEtlModule::WORKFLOW_CONFIG_PAGE
                                . '?pid=' . Filter::escapeForUrlParameter($pid)
                                . '&workflowName=' . Filter::escapeForUrlParameter($workflowName)
                            );
        if ($row % 2 == 0) {
            echo "<tr class=\"even\">\n";
        } else {
            echo "<tr class=\"odd\">\n";
        }

        echo '<td>' . $workflowName . "</td>\n";
        
        #-------------------------------
        # Status
        #-------------------------------
        $status = $workflow['metadata']['workflowStatus'];
        echo '<td style="text-align:center;">' . $status . "</td>\n";

        #-------------------------------
        # Last updated by/date
        #-------------------------------
        $updatedBy = $workflow['metadata']['updatedBy'];
        $dateUpdated = substr($workflow['metadata']['dateUpdated']['date'],0,10);
        if ($updatedBy || $dateUpdated) {
            echo '<td style="text-align:center;">' . $updatedBy . '/' . $dateUpdated;
        } else {
            echo '<td>';
        }
        echo "</td>\n";

        #-------------------------------
        # Configure
        #-------------------------------
        echo '<td style="text-align:center;">'
            . '<a href="' . $workflowConfigUrl . '">'
            . '<img src="' . APP_PATH_IMAGES . 'gear.png" alt="CONFIG"></a>'
            . "</td>\n";

        #-------------------------------
        # Delete
        #-------------------------------
        echo '<td style="text-align:center;">'
            .  '<input type="image" src="' . APP_PATH_IMAGES . 'delete.png" alt="DELETE"'
            . ' id="deleteWorkflow' . $row . '"'
            . ' class="deleteServer" style="cursor: pointer;">'
            . "</td>\n";
        
              
        echo "</tr>\n";
        $row++;
    }
    ?>
  </tbody>
</table>


<?php
#--------------------------------------
# Delete server dialog
#--------------------------------------
?>
<div id="delete-dialog"
    title="Workflow Delete"
    style="display: none;"
    >
    <form id="delete-form" action="<?php echo $selfUrl;?>" method="post">
    To delete the workflow  <span id="workflow-to-delete" style="font-weight: bold;"></span>,
    click on the <span style="font-weight: bold;">Delete workflow</span> button.
    <input type="hidden" name="delete-workflow-name" id="delete-workflow-name" value="">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>

<?php

#-----------------------------------------------------------------------
# Set up click event handlers for the workflow delete button
#-----------------------------------------------------------------------
echo "<script>\n";

$row = 1;
foreach ($workflows as $workflowName => $workflow) {
echo "console.log('$workflowName');";
    echo '$("#deleteWorkflow' . $row . '").click({workflowName: "'
        . Filter::escapeForJavaScriptInDoubleQuotes($workflowName)
        . '"}, RedCapEtlModule.deleteWorkflow);' . "\n";
    $row++;
}

echo "</script>\n";
?>

<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

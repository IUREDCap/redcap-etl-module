<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

# FOR WORKFLOWS

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

$selfUrl         = $module->getUrl(RedCapEtlModule::CRON_DETAIL_WORKFLOWS_PAGE);
$serverConfigUrl = $module->getUrl(RedCapEtlModule::SERVER_CONFIG_PAGE);
$configUrl    = $module->getURL(RedCapEtlModule::WORKFLOW_CONFIG_PAGE);

$adminConfig = $module->getAdminConfig();
    
$selectedDay = Filter::sanitizeInt($_POST['selectedDay']);
if (empty($selectedDay)) {
    $selectedDay = Filter::sanitizeInt($_GET['selectedDay']);
    if (empty($selectedDay)) {
        $selectedDay = 0;
    }
}

$selectedTime = Filter::sanitizeInt($_POST['selectedTime']);
if (empty($selectedTime)) {
    $selectedTime = Filter::sanitizeInt($_GET['selectedTime']);
    if (empty($selectedTime)) {
        $selectedTime = 0;
    }
}

$submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);

$cronJobs = $module->getWorkflowCronJobs($selectedDay, $selectedTime);
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
$buffer = str_replace('</head>', "    " . $link . "\n</head>", $buffer);
echo $buffer;
?>

<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png" alt="">REDCap-ETL Admin</h4>


<?php

$module->renderAdminPageContentHeader($selfUrl, $error, $warning, $success);
$module->renderAdminEtlCronDetailSubTabs($selfUrl);
?>

<?php
#---------------------------------
# Server selection form
#---------------------------------
$days = AdminConfig::DAY_LABELS;
$times = $adminConfig->getTimeLabels();

?>
<h5 style="margin-top: 2em;">ETL Workflows</h5>
<form action="<?php echo $selfUrl;?>" method="post"
      style="padding: 4px; margin-bottom: 12px; border: 1px solid #ccc; background-color: #ccc;">
    <span style="font-weight: bold;">Day:</span>
    <select name="selectedDay" onchange="this.form.submit()">
    <?php
    foreach ($days as $value => $label) {
        if (strcmp($value, $selectedDay) === 0) {
            echo '<option value="' . $value . '" selected>' . $label . "</option>\n";
        } else {
            echo '<option value="' . $value . '">' . $label . "</option>\n";
        }
    }
    ?>
    </select>
    
    <span style="font-weight: bold; margin-left: 1em;">Time:</span>
    <select name="selectedTime" onchange="this.form.submit()">
    <?php
    foreach ($times as $value => $label) {
        if (strcmp($value, $selectedTime) === 0) {
            echo '<option value="' . $value . '" selected>' . $label . "</option>\n";
        } else {
            echo '<option value="' . $value . '">' . $label . "</option>\n";
        }
    }
    ?>
    </select>
    <?php Csrf::generateFormToken(); ?>
</form>

<table class="dataTable">
    <thead>
        <tr> <th>Workflow Name</th> <th>Server</th> </tr>
    </thead>
    <tbody>
        <?php
        $row = 1;
        foreach ($cronJobs as $cronJob) {
            $server = $cronJob['server'];
            $serverUrl = $serverConfigUrl . '&serverName=' . Filter::escapeForUrlParameter($server);
            
            $workflowName = $cronJob['workflowName'];
            $tasks = $module->getWorkflowTasks($workflowName);
            $taskProjectIds = array_column($tasks, 'projectId');
            $firstPid = $taskProjectIds[0];
            $workflowUrl = $configUrl.'&pid='.Filter::escapeForUrlParameter($firstPid).'&workflowName='.Filter::escapeForUrlParameter($workflowName);

            if ($row % 2 === 0) {
                echo '<tr class="even">' . "\n";
            } else {
                echo '<tr class="odd">' . "\n";
            }
            echo "<td>" . '<a href="' . $workflowUrl . '">' . Filter::escapeForHtml($workflowName) . '</a>' . "</td>\n";
            
            echo "<td>" . '<a href="' . $serverUrl . '">' . Filter::escapeForHtml($server) . '</a>' . "</td>\n";
            
            echo "</tr>\n";
            $row++;
        }
        ?>
    </tbody>
</table>

<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

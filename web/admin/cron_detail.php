<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

# FOR STAND-ALONE TASKS

#---------------------------------------------
# Check that the user has access permission
#---------------------------------------------
$module->checkAdminPagePermission();


require_once __DIR__ . '/../../vendor/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapEtlModule;
use IU\RedCapEtlModule\ServerConfig;
use IU\RedCapEtlModule\Workflow;

$selfUrl         = $module->getUrl(RedCapEtlModule::CRON_DETAIL_PAGE);
$serverConfigUrl = $module->getUrl(RedCapEtlModule::SERVER_CONFIG_PAGE);
$userUrl         = $module->getURL(RedCapEtlModule::USER_CONFIG_PAGE);

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


/*
if ($submitValue === 'Run') {
    try {
        $module->runCronJobs($selectedDay, $selectedTime);
        $success = "Cron jobs were run for: day={$selectedDay} hour={$selectedTime}\n\n";
    } catch (\Exception $exception) {
        $error = $exception->getMessage();
    }
}
*/

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
?>

<?php

#---------------------------------
# Day and time selection form
#---------------------------------
$days = AdminConfig::DAY_LABELS;
$times = $adminConfig->getTimeLabels();
?>
<h5 style="margin-top: 10px;">ETL Cron Jobs</h5>
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
    <input type="hidden" name="redcap_csrf_token" value="<?php echo $module->getCsrfToken(); ?>"/>
</form>

<table class="dataTable">
    <thead>
        <tr> <th>Job Type</th> <th>Config/Workflow Name</th> <th>Server</th> <th>Project IDs</th> </tr>
    </thead>
    <tbody>
        <?php
        $row = 1;

        $taskCronJobs     = $module->getTaskCronJobs($selectedDay, $selectedTime);
        $workflowCronJobs = $module->getWorkflowCronJobs($selectedDay, $selectedTime);
        $cronJobs = array_merge($taskCronJobs, $workflowCronJobs);

        #-----------------------------------------------------
        # Task cron jobs
        #-----------------------------------------------------
        foreach ($cronJobs as $cronJob) {
            $server = $cronJob['server'];
            $serverUrl = $serverConfigUrl . '&serverName=' . Filter::escapeForUrlParameter($server);
            #$username  = $cronJob['username'];
            $workflowSuperscript = '';
            $workflowNote        = '';

            $configType = '';
            if (array_key_exists('workflowName', $cronJob)) {
                $configType = 'workflow';
                $config     = $cronJob['workflowName'];

                $tasks = $module->getWorkflowTasks($config);

                $workflowStatus = $module->getWorkflowStatus($config);
                if ($workflowStatus === Workflow::WORKFLOW_REMOVED) {
                    $workflowSuperscript = '*';
                    $workflowNote = 'This workflow has been removed by a user and will not run in the cron job'
                        . ' unless it is reinstated by an admin.';
                }

                $taskProjectIds = array_column($tasks, 'projectId');
                $firstPid = $taskProjectIds[0];
                $configUrl = $module->getURL(
                    RedCapEtlModule::USER_ETL_CONFIG_PAGE
                    . '?pid=' . Filter::escapeForUrlParameter($firstPid)
                    . '&configType=workflow'
                    . '&workflowName=' . Filter::escapeForUrlParameter($config)
                );

                $pidLinks = '';
                $pids = array_unique($taskProjectIds);
                sort($pids);
                foreach ($pids as $pid) {
                    $pidLinks .= ' <a href="'
                        . APP_PATH_WEBROOT . 'index.php?pid=' . (int)$pid . '">'
                        . (int) $pid . '</a> ';
                }
            } else {
                $configType = 'task';
                $projectId = $cronJob['projectId'];
                $config    = $cronJob['config'];
                $configUrl = $module->getURL(
                    RedCapEtlModule::USER_ETL_CONFIG_PAGE
                    . '?pid=' . Filter::escapeForUrlParameter($projectId)
                    . '&configType=task'
                    . '&configName=' . Filter::escapeForUrlParameter($config)
                );
                $pidLinks = '<a href="' . APP_PATH_WEBROOT . 'index.php?pid=' . (int)$projectId . '">'
                    . (int)$projectId . '</a>';
            }

            if ($row % 2 === 0) {
                echo '<tr class="even">' . "\n";
            } else {
                echo '<tr class="odd">' . "\n";
            }

            $displayConfigType = $configType;
            if ($displayConfigType === 'task') {
                $displayConfigType = 'configuration';
            }
            echo "<td>{$displayConfigType}</td>\n";   # Job Type

            if (empty($workflowSuperscript)) {
                echo "<td>" . '<a href="' . $configUrl . '">' . Filter::escapeForHtml($config) . '</a>' . "</td>\n";
            } else {
                echo "<td>" . '<a href="' . $configUrl . '">' . Filter::escapeForHtml($config)
                    . '<sup>' . Filter::escapeForHtml($workflowSuperscript) . '</sup></a>' . "</td>\n";
            }

            echo "<td>" . '<a href="' . $serverUrl . '">' . Filter::escapeForHtml($server) . '</a>' . "</td>\n";

            echo "<td>" . $pidLinks . '</a>' . "</td>\n";

            echo "</tr>\n";
            $row++;
        }
        ?>
    </tbody>
</table>


<?php
if (!empty($workflowNote)) {
    echo "<p style=\"margin-top: 24px;\"><sup>{$workflowSuperscript}</sup>{$workflowNote}</p>\n";
}
?>

<!--
<form action="<?php #echo $selfUrl;?>" method="post" style="margin-top: 12px;">
    <input type="hidden" name="selectedDay" value="<?php #echo $selectedDay; ?>">
    <input type="hidden" name="selectedTime" value="<?php #echo $selectedTime; ?>">
    <input type="submit" id="runButton" name="submitValue" value="Run"
       onclick='$("#runButton").css("cursor", "progress"); $("body").css("cursor", "progress");'/>
-->
    <?php # Csrf::generateFormToken(); ?>
<!-- </form>
-->

<div id="popup" style="display: none;"></div>


<script>
$(function() {
$('#popup').dialog({
    autoOpen: false,
    open: function(event, ui) {
        $('#popup').load(
            "<?php echo $module->getURL(
                "config_dialog.php?config={$config}&username={$username}"
                . "&projectId={$projectId}"
            ) ?>",
            function() {}
        );
    },
  modal: true,
  minHeight: 600,
  minWidth: 800,
  buttons: {
    'Save Changes': function(){
        $(this).dialog('close');
    },
    'Discard & Exit' : function(){
      $(this).dialog('close');
    }
  }
});
    $(".copyConfig").click(function(){
        var id = this.id;
        var configName = id.substring(4);
        $("#configToCopy").text('"'+configName+'"');
        $('#copyFromConfigName').val(configName);
        $("#popup").dialog("open");
    });
});
</script>


<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

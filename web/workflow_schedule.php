<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------


# you are here, about to split this up. Before you do, get the changes Jim made to ETL.

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__ . '/../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapEtlModule;
use IU\RedCapEtlModule\ServerConfig;

$error   = '';
$warning = '';
$success = '';

$pid = PROJECT_ID;
$username = USERID;

$workflowName = Filter::escapeForHtml($_GET['workflowName']);
if (empty($workflowName)) {
    $workflowName = $_POST['workflowName'];  
}
$selfUrl = $module->getUrl('web/workflow_schedule.php').'&workflowName='.Filter::escapeForUrlParameter($workflowName);

try {
    $excludeIncomplete = true;
    $projectWorkflows = $module->getProjectAvailableWorkflows($pid, $excludeIncomplete);

    $noReadyProjects = false;
    if (empty($projectWorkflows)) { 
        $noReadyProjects = true;
    }

    if (!empty($workflowName)) {
        $p = array_search($workflowName, $projectWorkflows); 
        $workflowReady = $p === false ? false : true;
    }

    array_unshift($projectWorkflows, '');
    
    #-----------------------------------------------------------
    # Check that the user has permission to access this page
    # and get the configuration if one was specified
    #-----------------------------------------------------------
    $configCheck = false;
    $runCheck = false;
    $scheduleCheck = true;
    $configuration = $module->checkUserPagePermission(USERID, $configCheck, $runCheck, $scheduleCheck);

    $adminConfig = $module->getAdminConfig();

    $servers   = $module->getUserAllowedServersBasedOnAccessLevel(USERID);

    #-------------------------
    # Set the submit value
    #-------------------------
    $submitValue = '';
    if (array_key_exists('submitValue', $_POST)) {
        $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);
    }
    
    if (strcasecmp($submitValue, 'Save') === 0) {
        $server = Filter::sanitizeString($_POST['server']); #ServerConfig::EMBEDDED_SERVER_NAME;
        if (empty($workflowName)) {
            $error = 'ERROR: No workflow specified.';
        } elseif (empty($server)) {
            $error = 'ERROR: No server specified.';
        } else  {
            # Saving the schedule values
            $schedule = array();
        
            $schedule[0] = Filter::sanitizeInt($_POST['Sunday']);
            $schedule[1] = Filter::sanitizeInt($_POST['Monday']);
            $schedule[2] = Filter::sanitizeInt($_POST['Tuesday']);
            $schedule[3] = Filter::sanitizeInt($_POST['Wednesday']);
            $schedule[4] = Filter::sanitizeInt($_POST['Thursday']);
            $schedule[5] = Filter::sanitizeInt($_POST['Friday']);
            $schedule[6] = Filter::sanitizeInt($_POST['Saturday']);
            $module->setWorkflowSchedule($workflowName, $server, $schedule, $username);
            $success = " Schedule saved.";
        }
    } else {
        # Just displaying page
        if (isset($workflowName)) {
            $cron = $module->getWorkflowSchedule($workflowName);
            $server = $cron[Configuration::CRON_SERVER];
            $schedule = $cron[Configuration::CRON_SCHEDULE];
        }
    }
} catch (Exception $exception) {
    $error = 'ERROR: ' . $exception->getMessage();
}
?>

<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
/*
ob_start();
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
*/
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="' . $cssFile . '" rel="stylesheet" type="text/css" media="all">';
echo "{$link}\n";
?>

<div class="projhdr"> <!--h4 style="color:#800000;margin:0 0 10px;"> -->
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png" alt="">REDCap-ETL
</div>


<?php
#------------------------------
# Display module tabs
#------------------------------
$module->renderProjectPageContentHeader($scheduleUrl, $error, $warning, $success);
?>


<?php
#---------------------------------------
# Configuration selection form
#---------------------------------------
?>
<form action="<?php echo $selfUrl;?>" method="post" 
      style="padding: 4px; margin-bottom: 0px; border: 1px solid #ccc; background-color: #ccc;">
    <span style="font-weight: bold;">ETL Workflow Configuration:</span>
    <select name="workflowName" onchange="this.form.submit();">
    <?php
    foreach ($projectWorkflows as $value) {
        if (strcmp($value, $workflowName) === 0) {
            echo '<option value="' . Filter::escapeForHtmlAttribute($value) . '" selected>'
                . Filter::escapeForHtml($value) . "</option>\n";
        } else {
            echo '<option value="' . Filter::escapeForHtmlAttribute($value) . '">'
                . Filter::escapeForHtml($value) . "</option>\n";
        }
    }
    ?>
    </select>
    <?php Csrf::generateFormToken(); ?>
</form>

<br />


<script type="text/javascript">
// Change radio buttons so that a checked
// radio button that is clicked will be
// unchecked
$(function () {
    var val = -1;
    var vals = {};
    vals['Sunday'] = -1;
    vals['Monday'] = -1;
    vals['Tuesday'] = -1;
    vals['Wednesday'] = -1;
    vals['Thursday'] = -1;
    vals['Friday'] = -1;
    vals['Saturday'] = -1;
    vals['Week'] = -1;

    $('input:radio').click(function () {
        name = $(this).attr('name');
        //alert('value:' + $(this).val());
        //alert('name:' + $(this).attr('name'));
        if ($(this).val() == vals[name]) {
            $(this).prop('checked',false);
            vals[name] = -1;
        } else {
            $(this).prop('checked',true);
            vals[name] = $(this).val();
        }
});
});
</script>


        
<form action="<?php echo $selfUrl;?>" method="post" style="margin-top: 14px;">


    <div style="margin-bottom: 12px;">
    <span style="font-weight: bold">Server:</span>

    <?php
    #--------------------------------------------------------------
    # Server selection
    #--------------------------------------------------------------
    echo '<select name="server" id="serverId">' . "\n";
    echo '<option value=""></option>' . "\n";

    #if ($adminConfig->getAllowEmbeddedServer()) {
    #    $selected = '';
    #    if (strcasecmp($server, ServerConfig::EMBEDDED_SERVER_NAME) === 0) {
    #        $selected = 'selected';
    #    }
    #
    #    echo '<option value="'.ServerConfig::EMBEDDED_SERVER_NAME.'" '.$selected.'>'
    #         .ServerConfig::EMBEDDED_SERVER_NAME
    #         .'</option>'."\n";
    #}

    foreach ($servers as $serverName) {
        $serverConfig = $module->getServerConfig($serverName);
        if (isset($serverConfig) && $serverConfig->getIsActive()) {
            $selected = '';
            if ($serverName === $server) {
                $selected = 'selected';
            }
            echo '<option value="' . Filter::escapeForHtmlAttribute($serverName) . '" ' . $selected . '>'
                . Filter::escapeForHtml($serverName) . "</option>\n";
        }
    }
    echo "</select>\n";
    ?>
    </div>

  <table class="cron-schedule">
    <thead>
      <tr>
        <th>&nbsp;</th>
        <?php
        foreach (AdminConfig::DAY_LABELS as $key => $label) {
            echo "<th class=\"day\">{$label}</th>\n";
        }
        ?>
      </tr>
    </thead>
    <tbody>
    <?php
    $row = 1;
    foreach ($adminConfig->getTimes() as $time) {
        if ($row % 2 === 0) {
            echo '<tr class="even-row">';
        } else {
            echo '<tr>';
        }
        
        echo '<td class="time-range">' . ($adminConfig->getHtmlTimeLabel($time)) . "</td>";
        
        foreach (AdminConfig::DAY_LABELS as $day => $label) {
            $radioName = $label;
            $value = $time;
            
            $checked = '';
            if (isset($schedule[$day]) && $schedule[$day] != '' && $schedule[$day] == $value) {
                $checked = ' checked ';
            }

            if ($adminConfig->isAllowedCronTime($day, $time)) {
                echo '<td class="day" >';
                echo '<input type="radio" name="' . $radioName . '" value="' . $value . '" ' . $checked . '>';
                echo '</td>' . "\n";
            } else {
                echo '<td class="day" ><input type="radio" name="' . $radioName . '"'
                    . ' value="' . $value . '" disabled></td>' . "\n";
            }
        }
        echo "</tr>\n";
        $row++;
    }
    ?>
    </tbody>
  </table>
  <p>
      <input type="submit" name="submitValue" value="Save">
   </p>
    <?php Csrf::generateFormToken(); ?>
</form>

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

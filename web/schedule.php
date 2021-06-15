<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__ . '/../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\ServerConfig;

$error   = '';
$warning = '';
$success = '';

$pid = PROJECT_ID;
$username = USERID;

$servers = array();

try {
    #-----------------------------------------------------------
    # Check that the user has permission to access this page
    #-----------------------------------------------------------
    $configCheck = true;
    $configuration = $module->checkUserPagePermission(USERID, $configCheck);


    $selfUrl  = $module->getUrl("web/schedule.php");

    $adminConfig = $module->getAdminConfig();

    $servers  = $module->getUserAllowedServersBasedOnAccessLevel(USERID);

    #------------------------------------------
    # Get the configuration type
    #------------------------------------------
    $configType = Filter::sanitizeLabel($_POST['configType']);
    if (empty($configType)) {
        $configType = Filter::sanitizeLabel($_GET['configType']);
        if (empty($configType)) {
            $configType = $_SESSION['configType'];
        }
    } else {
        $_SESSION['configType'] = $configType;
    }

    #------------------------------------------
    # Get the (task) configuration name
    #------------------------------------------
    $configName = Filter::sanitizeLabel($_POST['configName']);
    if (empty($configName)) {
        $configName = $_SESSION['configName'];
    } else {
        $_SESSION['configName'] = $configName;
    }

    #------------------------------------------
    # Get the workflow (configuration) name
    #------------------------------------------
    $workflowName = Filter::sanitizeLabel($_POST['workflowName']);
    if (empty($workflowName)) {
        $workflowName = $_SESSION['workflowName'];
    } else {
        $_SESSION['workflowName'] = $workflowName;
    }

    #------------------------------------------
    # Get the server
    #------------------------------------------
    $server = Filter::stripTags($_POST['server']);
    if (empty($server)) {
        $server = $_SESSION['server'];
    } else {
        $_SESSION['server'] = $server;
    }

    #-------------------------
    # Set the submit value
    #-------------------------
    $submit = '';
    if (array_key_exists('submit', $_POST)) {
        $submit = Filter::sanitizeButtonLabel($_POST['submit']);
    }

    #-----------------------------------------
    # Process a submit
    #-----------------------------------------
    $submitValue = '';
    if (array_key_exists('submitValue', $_POST)) {
        $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);
    }

    if (strcasecmp($submitValue, 'Save') === 0) {
        $configuration->validateForRunning();
        
        $server = Filter::stripTags($_POST['server']);
        
        # Saving the schedule values
        $schedule = array();
        
        $schedule[0] = Filter::sanitizeInt($_POST['Sunday']);
        $schedule[1] = Filter::sanitizeInt($_POST['Monday']);
        $schedule[2] = Filter::sanitizeInt($_POST['Tuesday']);
        $schedule[3] = Filter::sanitizeInt($_POST['Wednesday']);
        $schedule[4] = Filter::sanitizeInt($_POST['Thursday']);
        $schedule[5] = Filter::sanitizeInt($_POST['Friday']);
        $schedule[6] = Filter::sanitizeInt($_POST['Saturday']);
        
        if ($configType === 'task') {
            if (empty($configName)) {
                $error = 'ERROR: No ETL configuration specified.';
            } elseif (!isset($configuration)) {
                $error = 'ERROR: No ETL configuration found for ' . $configName . '.';
            } elseif (empty($server)) {
                $error = 'ERROR: No server specified.';
            } else {
                $module->setConfigSchedule($configName, $server, $schedule);
                $success = " Schedule saved.";
            }
        } elseif ($configType === 'workflow') {
            if (!isset($workflowName)) {
                $error = 'ERROR: No workflow specified.';
            } elseif (empty($server)) {
                $error = 'ERROR: No server specified.';
            } else {
                $module->setWorkflowSchedule($workflowName, $server, $schedule, $username);
                $success = " Schedule saved.";
            }
        }
    } else {
        # Just displaying page
        if (isset($configuration)) {
            $server   = $configuration->getProperty(Configuration::CRON_SERVER);
            $schedule = $configuration->getProperty(Configuration::CRON_SCHEDULE);
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

<div class="projhdr"> 
    <img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png" alt="">REDCap-ETL
    <?php
    if ($testMode) {
        echo '<span style="color: blue;">[TEST MODE]</span>';
    }
    ?>
</div>


<?php
$module->renderProjectPageContentHeader($selfUrl, $error, $warning, $success);
?>

<?php
#-------------------------------------
# Configuration selection form
#-------------------------------------
?>
<form action="<?php echo $selfUrl;?>" method="post" style="margin-top: 4px;">

    <div id="input-container">

        <!-- TASK/WORKFLOW CONFIGURATION SELECTION -->
        <div style="float: left;">
            <table style="margin-bottom: 12px;">
                <!-- TASKS -->
                <tr>
                    <td>
                        <?php
                        $checked = '';
                        if ($configType === 'task') {
                            $checked = 'checked';
                        }
                        ?>
                        <input type="radio" name="configType" value="task" id="task" <?php echo $checked ?>/>
                        <label for="task">ETL Task</label>
                    </td>
                    <td>
                        <select name="configName" onchange="this.form.submit()">
                        <?php
                        $configNames = $module->getAccessibleConfigurationNames();
                        array_unshift($configNames, '');
                        foreach ($configNames as $value) {
                            if (strcmp($value, $configName) === 0) {
                                echo '<option value="' . Filter::escapeForHtmlAttribute($value) . '" selected>'
                                    . Filter::escapeForHtml($value) . "</option>\n";
                            } else {
                                echo '<option value="' . Filter::escapeForHtmlAttribute($value) . '">'
                                    . Filter::escapeForHtml($value) . "</option>\n";
                            }
                        }
                        ?>
                        </select>
                    </td>
                </tr>
        
                <tr>
                    <td>
                        <?php
                        $checked = '';
                        if ($configType === 'workflow') {
                            $checked = 'checked';
                        }
                        ?>
                        <input type="radio" name="configType" value="workflow" id="workflow" <?php echo $checked ?>/>
                        <label for="workflow">ETL Workflow</label>
                        &nbsp;
                    </td>
                    <td>
                        <?php
                        $excludeIncomplete = true;
                        $projectWorkflows = $module->getProjectAvailableWorkflows($pid, $excludeIncomplete);
                        ?>
                        <select name="workflowName" onchange="this.form.submit()">
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
                    </td>
                </tr>
            </table>
        </div>

        <!-- SERVER SELECTION -->
        <div style="float: left; margin-bottom: 12px; margin-left: 2em;">
            <span style="margin-right: 4px;">ETL Server:</span>
            <?php
            echo '<select name="server" id="serverId">' . "\n";
            echo '<option value=""></option>' . "\n";
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

        <!-- SAVE BUTTON -->
        <div style="float: left; margin-left: 2em;">
        <input type="submit" name="submitValue" value="Save" style="padding: 0em 2em;">
        </div>
    </div>

    <div style="clear: both;"/>




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


        


  <!-- <fieldset style="border: 2px solid #ccc; border-radius: 7px; padding: 7px;"> -->
  <!-- <legend style="font-weight: bold;">Schedule Automated Repeating Run</legend> -->

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
  <!-- </fieldset> -->
    <?php Csrf::generateFormToken(); ?>
</form>

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

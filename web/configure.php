<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__ . '/../dependencies/autoload.php';

use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapDb;

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

    #-------------------------------------------------------------------
    # Check for test mode (which should only be used for development)
    #-------------------------------------------------------------------
    $testMode = false;
    if (@file_exists(__DIR__ . '/../test-config.ini')) {
        $testMode = true;
    }

    $selfUrl  = $module->getUrl("web/configure.php");

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
    # Get the workflow name
    #------------------------------------------
    $workflowName = Filter::sanitizeLabel($_POST['workflowName']);
    if (empty($workflowName)) {
        $workflowName = $_SESSION['workflowName'];
    } else {
        $_SESSION['workflowName'] = $workflowName;
    }

    #-------------------------
    # Set the submit value
    #-------------------------
    $submit = '';
    if (array_key_exists('submit', $_POST)) {
        $submit = Filter::sanitizeButtonLabel($_POST['submit']);
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
#------------------------------------------
# Configuration selection form
#------------------------------------------
?>
<form action="<?php echo $selfUrl;?>" method="post" 
    style="padding: 12px; margin-bottom: 0px; margin-right: 1em; border-radius: 10px; border: 1px solid #ccc;">


    <div id="input-container" style="padding: 0px; margin: 0px;">

        <!-- CONFIGURATION INFORMATION -->
        <div style="float: left;">
            <table style="margin-bottom: 0px;">
                <!-- TASKS -->
                <tr>
                    <td>
                        <?php
                        $checked = '';
                        if ($configType === 'task') {
                            $checked = 'checked';
                        }
                        ?>
                        <input type="radio" name="configType" value="task" id="task" <?php echo $checked ?>
                               onchange="this.form.submit()"/>
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
                        <input type="radio" name="configType" value="workflow" id="workflow" <?php echo $checked ?>
                               onchange="this.form.submit()"/>
                        <label for="workflow">ETL Workflow</label>
                        &nbsp;
                    </td>
                    <td>
                        <?php
                        $excludeIncomplete = false;
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

        <!-- SAVE BUTTON -->
        <!--
        <div style="float: left; margin-left: 2em;">
        <input type="submit" name="submitValue" value="Save"
            style="padding: 0em 2em; font-weight: bold; color: rgb(45, 107, 161);">
        </div>
        -->

    </div>
    
    <div style="clear: both;"></div>

    <?php Csrf::generateFormToken(); ?>
</form>

<?php

define('ETL_CONFIG_PAGE', 1);
if ($configType === 'task') {
    include(__DIR__ . '/task_configure_include.php');
} elseif ($configType === 'workflow') {
    include(__DIR__ . '/workflow_configure_include.php');
}

?>

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>


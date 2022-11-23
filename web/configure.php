<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__ . '/../dependencies/autoload.php';

use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapDb;

$error   = '';
$warning = '';
$success = '';

$pid = PROJECT_ID;
$username = USERID;

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

    #------------------------------------------
    # Get request variables
    #------------------------------------------
    $configType   = $module->getRequestVar('configType', '\IU\RedCapEtlModule\Filter::sanitizeLabel');
    $configName   = $module->getRequestVar('configName', '\IU\RedCapEtlModule\Filter::sanitizeLabel');
    $workflowName = $module->getRequestVar('workflowName', '\IU\RedCapEtlModule\Filter::sanitizeLabel');

    #-------------------------
    # Set the submit value
    #-------------------------
    $submit = '';
    if (array_key_exists('submit', $_POST)) {
        $submit = Filter::sanitizeButtonLabel($_POST['submit']);
    }

    #------------------------------------------------------------------------------------
    # Process included page actions that need to be done before output is generated
    #------------------------------------------------------------------------------------
    $submitValue = '';
    if (array_key_exists('submitValue', $_POST)) {
        $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);
    }

    if ($configType === 'task') {
        if (strcasecmp($submitValue, 'Download CSV file') === 0) {
            header('Content-Type: text/plain');
            header('Content-disposition: attachment; filename="rules.csv"');
            $rulesText = $configuration->getProperty(Configuration::TRANSFORM_RULES_TEXT);
            $rulesText = Filter::stripTags($rulesText);
            $fh = fopen('php://output', 'w');
            fwrite($fh, $rulesText);
            exit();
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
                        <label for="task">ETL Configuration</label>
                        &nbsp;
                    </td>
                    <td>
                        <select name="configName" id="configName" onchange="this.form.submit()">
                        <?php
                        $configNames = $module->getAccessibleConfigurationNames();
                        #array_unshift($configNames, '');
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
                    </td>
                    <td>
                        <?php
                        $excludeIncomplete = false;
                        $excludeRemoved    = true;

                        # For admins - get list of workflows that include "removed" workflows
                        # (removed by user but not actually deleted from system)
                        if ($module->isSuperUser()) {
                            $excludeRemoved = false;
                        }
                        $projectWorkflows = $module->getProjectAvailableWorkflows(
                            $pid,
                            $excludeIncomplete,
                            $excludeRemoved
                        );

                        #array_unshift($projectWorkflows, '');
                        ?>
                        <select name="workflowName" id="workflowName" onchange="this.form.submit()">
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

    </div>
    
    <div style="clear: both;"></div>

    <?php Csrf::generateFormToken(); ?>
</form>

<?php

if ($configType === 'task') {
    echo ""
        . "<script>\n"
        . '$("#configName").prop("disabled", false);' . "\n"
        . '$("#workflowName").prop("disabled", true);' . "\n"
        .  "</script>\n";

    include(__DIR__ . '/task_configure_include.php');
} elseif ($configType === 'workflow') {
    echo ""
        . "<script>\n"
        . '$("#configName").prop("disabled", true);' . "\n"
        . '$("#workflowName").prop("disabled", false);' . "\n"
       .  "</script>\n";

    #-------------------------------------------------------------------------------------------------
    # Check to make sure the workflow belongs to this project (it could have been
    # set in the user's session from viewing a previous project)
    #-------------------------------------------------------------------------------------------------
    # $workflowNames = $module->getProjectAvailableWorkflows();
    $workflowNames = $module->getProjectAvailableWorkflows($pid, $excludeIncomplete, $excludeRemoved);
    if (!in_array($workflowName, $workflowNames)) {
        $workflowName = '';
        $_SESSION[$workflowName] = '';
    }

    if (!empty($workflowName)) {
        include(__DIR__ . '/workflow_configure_include.php');
    }
}

?>

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>


<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__ . '/../vendor/autoload.php';

use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\DataTarget;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\ServerConfig;

$error   = '';
$warning = '';
$success = '';

$pid = PROJECT_ID;
$username = USERID;

$servers = array();
$server = '';


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

    $selfUrl  = $module->getUrl("web/run.php");

    $servers  = $module->getUserAllowedServersBasedOnAccessLevel(USERID);

    #------------------------------------------
    # Get request variables
    #------------------------------------------
    $configType   = $module->getRequestVar('configType', '\IU\RedCapEtlModule\Filter::sanitizeLabel');
    $configName   = $module->getRequestVar('configName', '\IU\RedCapEtlModule\Filter::sanitizeLabel');
    $workflowName = $module->getRequestVar('workflowName', '\IU\RedCapEtlModule\Filter::sanitizeLabel');
    $server       = $module->getRequestVar('server', '\IU\RedCapEtlModule\Filter::stripTags');
    $dataTarget   = $module->getRequestVar('dataTarget', '\IU\RedCapEtlModule\Filter::sanitizeLabel');

    # If no server is specified, set it to the first of the list of servers (if there is at least 1)
    if (empty($server) && count($servers) > 0) {
        $server = $servers[0];
    }

    # If this is not a task, or the server is not the embedded server,
    # make sure that the data target is set to "database", because the data target
    # may be stored in the user's session as "CSV" from a previous run
    if ($configType !== 'task' || $server !== ServerConfig::EMBEDDED_SERVER_NAME) {
        $dataTarget = DataTarget::DB;
    } elseif ($configType === 'task' && $server === ServerConfig::EMBEDDED_SERVER_NAME) {
        $serverConfig = $module->getServerConfig($server);
        # FINISH!!!!!!!!!!!!!!!!!!!
        $dataLoadOptions = $serverConfig->getDataLoadOptions();
    }


    #-------------------------
    # Set the submit value
    #-------------------------
    $submit = '';
    if (array_key_exists('submitValue', $_POST)) {
        $submit = Filter::sanitizeButtonLabel($_POST['submitValue']);
    }

    #-----------------------------------------
    # Process a submit
    #-----------------------------------------
    $runOutput = '';
    if (strcasecmp($submit, 'Run') === 0) {
        if ($configType === 'task') {
            if (empty($configName)) {
                $error = 'ERROR: No ETL configuration specified.';
            } elseif (!isset($configuration)) {
                $error = 'ERROR: No ETL configuration found for ' . $configName . '.';
            } else {
                # Check the configuration for errors that would prevent it from being run
                $checkDatabaseConnection = true;
                if ($dataTarget === DataTarget::CSV_ZIP) {
                    $checkDatabaseConnection = false;
                }
                $configuration->validateForRunning($server, $checkDatabaseConnection);

                $isCronJob = false;
                #$runOutput = $module->run($configName, $server, $isCronJob);
                $runOutput = $module->run($configName, $server, $isCronJob, $pid, null, null, null, $dataTarget);

                if ($dataTarget === DataTarget::CSV_ZIP) {
                    $result = $runOutput;
                    if (is_string($result) && (substr($result, 0, 6) === 'ERROR:')) {
                        $error = $result;
                    } else {
                        header('Content-Type: application/zip');
                        header('Content-Disposition: attachment; filename="redcap-etl.zip"');
                        header('Content-Length: ' . filesize($result));
                        echo file_get_contents($result);
                    }
                }
            }
        } elseif ($configType === 'workflow') {
            if (empty($workflowName)) {
                $error = 'ERROR: No ETL workflow specified.';
            } else {
                # If the workflow is mot valid for running, and exception will be thrown,
                # and the workflow will not run.
                $module->validateWorkflowForRunning($workflowName, $server);

                $isCronJob = false;
                $originatingProjectId = $pid;

                #Get projects that this user has access to
                $db = new RedCapDb();
                $userProjects = $db->getUserProjects($username);
                $runOutput = $module->runWorkflow(
                    $workflowName,
                    $server,
                    $username,
                    $userProjects,
                    $isCronJob,
                    $originatingProjectId
                );
            }
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

<script>
    // Help dialog events
    $(document).ready(function() {

        $( function() {
            $('#run-help-link').click(function () {
                $('#run-help').dialog({dialogClass: 'redcap-etl-help', width: 540, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right+50 top+90', of: $(this)})
                    ;
                return false;
            });
        });
    });

</script>

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

#print "<pre>\n";
#print_r($_POST);
#print "</pre>\n";

?>


<?php
#------------------------------------------
# Configuration & Server selection form
#------------------------------------------
?>

<form action="<?php echo $selfUrl;?>" name="runForm" method="post" 
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
                        <input type="radio" name="configType" value="task"
                               id="task" <?php echo $checked ?> onchange="this.form.submit()"/>
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
                               onchange="this.form.submit()"
                        />
                        <label for="workflow">ETL Workflow</label>
                    </td>
                    <td>
                        <?php
                        #----------------------------------------------------------------
                        # Worflow Selection
                        #----------------------------------------------------------------
                        $excludeIncomplete = false;
                        $projectWorkflows = $module->getProjectAvailableWorkflows($pid, $excludeIncomplete);
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

        <!-- SERVER SELECTION -->
        <div style="float: left; margin-bottom: 22px; margin-left: 2em">
            <span style="margin-right: 4px;">ETL Server:</span>
            <?php
            echo '<select name="server" id="serverId">' . "\n";
            # echo '<option value=""></option>' . "\n";
            # array_unshift($servers, '');
            foreach ($servers as $serverName) {
                if ($serverName !== '') {
                    $serverConfig = $module->getServerConfig($serverName);
                    if (isset($serverConfig) && $serverConfig->getIsActive()) {
                        $selected = '';
                        if ($serverName === $server) {
                            $selected = 'selected';
                        }
                        echo '<option value="' . Filter::escapeForHtmlAttribute($serverName) . '" ' . $selected . '>'
                        . Filter::escapeForHtml($serverName) . "</option>\n";
                    }
                } else {
                    echo '<option value="" ' . '>'
                        . "</option>\n";
                }
            }
            echo "</select>\n";
            ?>
        </div>

        <!-- DATA TARGET -->
        <?php
        if ($configType === 'task' && $server === ServerConfig::EMBEDDED_SERVER_NAME) {
            if ($dataLoadOptions === ServerConfig::DATA_LOAD_DB_ONLY) {
                echo '
                <div style="float: left; margin-bottom: 22px; margin-left: 2em">
                    <select name="dataTarget" id="dataTarget" style="margin-left: 1em;">
                        <option value="' . DataTarget::DB . '">Load data into database </option>
                    </select>
                </div>';
            } elseif ($dataLoadOptions === ServerConfig::DATA_LOAD_FILE_ONLY) {
                echo '
                <div style="float: left; margin-bottom: 22px; margin-left: 2em">
                    <select name="dataTarget" id="dataTarget" style="margin-left: 1em;">
                        <option value="' . DataTarget::CSV_ZIP . '">Export data as CSV zip file</option>
                    </select>
                </div>';
            } else {
                echo '
                <div style="float: left; margin-bottom: 22px; margin-left: 2em">
                    <select name="dataTarget" id="dataTarget" style="margin-left: 1em;">
                        <option value="' . DataTarget::DB . '">Load data into database </option>
                        <option value="' . DataTarget::CSV_ZIP . '">Export data as CSV zip file</option>
                    </select>
                </div>';
            }
        }
        ?>
    
        <!-- RUN BUTTON -->
        <div style="float: left; margin-left: 2em; margin-bottom: 0px;">
            <input type="submit" name="submitValue" value="Run"
                    style="color: #008000; font-weight: bold; padding: 0px 24px;"
                    onclick='$("#runOutput").text(""); $("body").css("cursor", "progress");'/>
        </div>
    </div>
    
    <div style="float: right;">
        <a href="#" id="run-help-link" class="etl-help" title="help">?</a>
        <div id="run-help" title="Run ETL" style="display: none;">
            <?php echo Help::getHelpWithPageLink('run', $module); ?>
        </div>
    </div>

    <div style="clear: both;"></div>


    <?php Csrf::generateFormToken(); ?>
    <input type="hidden" name="redcap_csrf_token" value="<?php echo $module->getCsrfToken(); ?>"/>
</form>

<script>
$(document).ready(function(){
    $("#serverId").change(function(){
         runForm.submit();
     });
});
</script>

<?php
if ($configType === 'task') {
    echo ""
        . "<script>\n"
        . '$("#configName").prop("disabled", false);' . "\n"
        . '$("#workflowName").prop("disabled", true);' . "\n"
        .  "</script>\n";
} elseif ($configType === 'workflow') {
    echo ""
        . "<script>\n"
        . '$("#configName").prop("disabled", true);' . "\n"
        . '$("#workflowName").prop("disabled", false);' . "\n"
       .  "</script>\n";
}
?>

<div style="margin-right: 1em; margin-top: 12px;"
><pre id="runOutput"><?php echo Filter::escapeForHtml($runOutput);?></pre></div>


<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>


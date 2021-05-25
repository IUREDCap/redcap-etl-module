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
use IU\RedCapEtlModule\ModuleLog;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;
use IU\RedCapEtlModule\ServerConfig;

$selfUrl         = $module->getUrl(RedCapEtlModule::LOG_PAGE);
$adminUrl        = $module->getURL(RedCapEtlModule::ADMIN_HOME_PAGE);
$serverConfigUrl = $module->getUrl(RedCapEtlModule::SERVER_CONFIG_PAGE);
$userUrl         = $module->getURL(RedCapEtlModule::USER_CONFIG_PAGE);

$cronDetailsLogUrl   = $module->getUrl('web/admin/cron_details_log.php');
$etlRunDetailsLogUrl = $module->getUrl('web/admin/etl_run_details_log.php');

$adminConfigJson = $module->getSystemSetting(AdminConfig::KEY);
$adminConfig = new AdminConfig();

$moduleLog = new ModuleLog($module);


#------------------------------------------------------------------
# Process parameters
#------------------------------------------------------------------
$submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);

$logType = Filter::sanitizeLabel($_POST['logType']);
if (empty($logType)) {
    $logType = RedCapEtlModule::ETL_RUN;
}

$startDate = Filter::sanitizeDate($_POST['startDate']);
if (!empty($startDate) && !checkdate($startDate)) {
    $error = 'invalid start date';
} else {
    $startDate = date('m/d/Y');
}

$endDate  = Filter::sanitizeDate($_POST['endDate']);
if (!empty($endDate) && !checkdate($endDate)) {
    $error = 'invalid end date';
} else {
    $endDate = date('m/d/Y');
}

if (isset($_POST['downloadButton_x'])) {
    $moduleLog->generateCsvDownload($logType, $startDate, $endDate);
    exit(0);
}

#---------------------------------------------
# Include REDCap's Control Center page header
#---------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="' . $cssFile . '" rel="stylesheet" type="text/css" media="all">' . "\n";
$jsCode = '<script>' . "\n"
    . '    $( function() {' . "\n"
    . '        $("#startDate").datepicker();' . "\n"
    . '        $("#endDate").datepicker();' . "\n"
    . '    } );' . "\n"
    . '</script>' . "\n";
$buffer = str_replace('</head>', "    " . $link . $jsCode . "</head>", $buffer);
echo $buffer;
?>



<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png" alt="">REDCap-ETL Admin</h4>

<?php

$errorMessage   = Filter::stripTags($_GET['error']);
$successMessage = Filter::stripTags($_GET['success']);

$module->renderAdminPageContentHeader($selfUrl, $errorMessage, $warningMessage, $successMessage);
#$module->renderAdminUsersSubTabs($selfUrl);

?>



<h5>REDCap-ETL Log</h5>

<!--
<div style="margin-bottom: 1em;">
<button type="submit" value="Download CSV file" name="submitValue">
    <img src="<?php #echo APP_PATH_IMAGES.'csv.gif';?>" alt="" style="vertical-align: middle;">
    <span  style="vertical-align: middle;"> Download CSV file</span>
</button>
</div>
-->

<?php
#print "<pre>\n";
#print_r($_POST);
#print "</pre>\n";
?>

<form action="<?php echo $selfUrl;?>" method="post">
    
    <?php
    $runSelected = '';
    $cronSelected = '';
    if ($logType == RedCapEtlModule::ETL_CRON) {
        $cronSelected = ' selected ';
    } else {
        $runSelected = ' selected ';
    }
    ?>
    <div style="margin-bottom: 12px;">
        <label for="logType">Log Entries:</label>
        <select name="logType" id="logType">
            <option value="<?php echo RedCapEtlModule::ETL_RUN?>" <?php echo $runSelected; ?> >
                ETL Processes
            </option>
            <option value="<?php echo RedCapEtlModule::ETL_CRON?>" <?php echo $cronSelected; ?>>
                Cron Jobs
            </option>
        </select>
    </div>
    
    <div style="margin-bottom: 12px;">
        <span>Start Date:</span>
        <input type="text" id="startDate" name="startDate" class="x-form-text x-form-field" style="width: 90px;"
            value="<?php echo $startDate; ?>">
        
        <span style="margin-left: 7px;">End Date:</span>
        <input type="text" id="endDate" name="endDate" class="x-form-text x-form-field" style="width: 90px;"
            value="<?php echo $endDate; ?>">
        </input>
    
        <input type="submit" value="Display" name="submitValue" style="margin-left: 7px;">
        
        <input type="image" name="downloadButton" src="<?php echo APP_PATH_IMAGES . 'download_csvexcel.gif'; ?>"
               alt="CSV" style="vertical-align: middle; margin-left: 2em;">
    </div>
    <?php Csrf::generateFormToken(); ?>
</form>


    
    <p style="margin-top: 12px; font-weight: bold;">
        <?php
        if ($logType === RedCapEtlModule::ETL_CRON) {
            echo "ETL Cron Jobs";
        } elseif ($logType === RedCapEtlModule::ETL_RUN) {
            echo "ETL Processes";
        }
        
        if ($startDate === $endDate) {
            echo " for {$startDate}";
        } else {
            echo " for {$startDate} to {$endDate}";
        }
        echo "\n";
        ?>
    </p>
    
    <table class="etl-log">
        <thead>
        <?php

        #----------------------------------------------
        # Output table header based on log type
        #----------------------------------------------
        if ($logType === RedCapEtlModule::ETL_CRON) {
            echo "<tr> <th>Log ID</th> <th>Time</th> 
            <th>Day</th> <th>Hour</th> <th># Jobs</th> </tr>\n";
        } elseif ($logType === RedCapEtlModule::ETL_RUN) {
            echo "<tr>\n";
            echo "<th>Log ID</th> <th>Time</th>\n";
            echo "</th><th>Project ID</th>\n";
            echo "<th>Server</th> <th>Config</th>\n";
            echo "<th>User ID</th> <th>Username</th>\n";
            echo "<th>Cron?</th> <th>Cron<br/>Day</th> <th>Cron<br/>Hour</th>\n";
            echo "<th>Details</th>\n";
            echo "</tr>\n";
        }

        ?>

        </thead>
        <tbody>
            <?php

            $logData = $moduleLog->getData($logType, $startDate, $endDate);
            foreach ($logData as $entry) {
                if ($logType === RedCapEtlModule::ETL_RUN) {
                    $projectId = null;
                    #Get the project id if this is a stand-alone ETL run.
                    #(Workflows have more than one project id.)
                    if ($entry['log_type'] !== RedCapEtlModule::WORKFLOW_RUN) {
                        $projectId = $entry['project_id'];
                        $projectUrl = APP_PATH_WEBROOT . 'index.php?pid=' . (int)$projectId;
                    } 
                    $cron = $entry['cron'];
                    
                    $config = $entry['config'];
                    if ($entry['log_type'] === RedCapEtlModule::WORKFLOW_RUN) {
                        #The first project in the workflow sequence at runtime is recorded for log purposes
                        #so that a link can be provided back to the Workflow configuration page,
                        #which requires a pid value
                        $pid = $entry['project_id'];
                        $configUrl = null;
                        if ($pid) {
                            $configUrl = $module->getURL(
                                RedCapEtlModule::WORKFLOW_CONFIG_PAGE
                                . '?pid=' . Filter::escapeForUrlParameter($pid)
                                . '&workflowName=' . Filter::escapeForUrlParameter($config)
                            );
                        }
				    } else {
                        $configUrl = $module->getURL(
                            RedCapEtlModule::USER_ETL_CONFIG_PAGE
                            . '?pid=' . Filter::escapeForUrlParameter($projectId)
                            . '&configName=' . Filter::escapeForUrlParameter($config)
                        );
                    }
                    $server = $entry['etl_server'];
                    $serverUrl = $serverConfigUrl . '&serverName=' . Filter::escapeForUrlParameter($server);
                    
                    echo "<tr>\n";
                    echo '<td style="text-align: right;">' . $entry['log_id'] . "</td>\n";
                    echo "<td>" . $entry['timestamp'] . "</td>\n";

                    echo '<td style="text-align: right;">' . '<a href="'
                        . $projectUrl . '">' . $projectId . "</a></td>\n";
                    echo "<td>" . '<a href="' . $serverUrl . '">' . Filter::escapeForHtml($server) . '</a>' . "</td>\n";
                    if ($configUrl) {
                        echo "<td>" . '<a href="' . $configUrl . '">' . Filter::escapeForHtml($config) . '</a>' . "</td>\n";
                    } else {
                        echo "<td>" .  Filter::escapeForHtml($config) . "</td>\n";
				    }
                    #--------------------------------------------
                    # User info (not available for cron jobs)
                    #--------------------------------------------
                    echo '<td style="text-align: right;">' . $entry['ui_id'] . "</td>\n";
                    echo "<td>" . Filter::escapeForHtml($entry['etl_username']) . "</td>\n";
                    
                    #-------------------------------------
                    # Cron info
                    #-------------------------------------
                    if ($cron) {
                        echo "<td>yes</td>\n";
                    } else {
                        echo "<td>no</td>\n";
                    }
                    echo '<td style="text-align: right;">' . $entry['cron_day'] . "</td>\n";
                    echo '<td style="text-align: right;">' . $entry['cron_hour'] . "</td>\n";
                    
                    echo '<td>';
                    if ($server === ServerConfig::EMBEDDED_SERVER_NAME) {
                        echo '<a id="etl_run_detail_' . ($entry['log_id']) . '" class="etlRunDetails"'
                            . ' href="#">'
                            . 'details' . '</a>';
                    } else {
                        echo 'See remote server logs';
                    }
                    echo "</td>\n";
                    
                    echo "</tr>\n";
                } elseif ($logType === RedCapEtlModule::ETL_CRON) {
                    $cron = null;
                    if (array_key_exists('cron', $entry)) {
                        $cron = $entry['cron'];
                    }
                    echo "<tr>\n";
                    echo '<td style="text-align: right;">' . $entry['log_id'] . "</td>\n";
                    echo "<td>" . $entry['timestamp'] . "</td>\n";
                    echo '<td style="text-align: right;">' . $entry['cron_day'] . "</td>\n";
                    echo '<td style="text-align: right;">' . $entry['cron_hour'] . "</td>\n";
                    
                    $numJobs = $entry['num_jobs'];
                    echo '<td style="text-align: right;">';
                    if ($numJobs > 0) {
                        echo '<a id="cron_detail_' . ($entry['log_id']) . '" class="cronLogDetail"'
                            . ' href="#" style="font-weight: bold; text-decoration: underline;">'
                            . $numJobs . '</a>';
                    } else {
                        echo $numJobs;
                    }
                    echo "</td>\n";
                    
                    echo "</tr>\n";
                }
            }
            ?>
        </tbody>
    </table>




<script>
    $(document).ready(function() {
        //--------------------------------------
        // Cron detail dialog
        //--------------------------------------
        $(".cronLogDetail").click(function () {
            id = $(this).prop("id");
            id = id.replace('cron_detail_', '');
            
            var $url = '<?php echo $cronDetailsLogUrl; ?>';
            var $dialog;
            $dialog = $('<div></div>')
                .load($url, {
                    cron_log_id: id,
                    <?php echo Csrf::TOKEN_NAME; ?>: "<?php echo Csrf::getToken(); ?>"
                }).dialog();
                
            $dialog.dialog({
                title: 'Cron Jobs',
                dialogClass: 'redcap-etl-help',
                width: 500,
                maxHeight: 400
            })
            //.position({my: 'left top', at: 'right+20 top', of: $(this)})
            .dialog('open')
            ;
            
            return false;
        });

        //--------------------------------------
        // Etl run details dialog
        //--------------------------------------
        $(".etlRunDetails").click(function () {
            id = $(this).prop("id");
            id = id.replace('etl_run_details_', '');
            
            var $url = '<?php echo $etlRunDetailsLogUrl; ?>';
            var $dialog;
            $dialog = $('<div></div>')
                .load($url, {
                    etl_run_log_id: id,
                    <?php echo Csrf::TOKEN_NAME; ?>: "<?php echo Csrf::getToken(); ?>"
                }).dialog();
                
            $dialog.dialog({title: 'ETL Run Details', dialogClass: 'redcap-etl-log', width: 600, maxHeight: 400})
                //.position({my: 'left top', at: 'right+20 top', of: $(this)})
                .dialog('open')
            ;
            
            return false;
        });
    });
</script>


<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

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


require_once __DIR__.'/../../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\ModuleLog;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

$selfUrl  = $module->getUrl(RedCapEtlModule::LOG_PAGE);
$adminUrl = $module->getURL(RedCapEtlModule::ADMIN_HOME_PAGE);
$userUrl  = $module->getURL(RedCapEtlModule::USER_CONFIG_PAGE);

$adminConfigJson = $module->getSystemSetting(AdminConfig::KEY);
$adminConfig = new AdminConfig();


$submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);
$username    = Filter::stripTags($_POST['username-result']);

$users = $module->getUsers();

$logType = RedCapEtlModule::ETL_RUN;

#$startDate = Filter::sanitizeDate($_POST['startDate']);
$startDate = $_POST['startDate'];
if (!empty($startDate) && !checkdate($startDate)) {
    $error = 'invalid start date';
} else {
    $startDate = date('m/d/Y');
}

$endDate  = $_POST['endDate'];
if (!empty($endDate) && !checkdate($endDate)) {
    $error = 'invalid end date';
} else {
    $endDate = date('m/d/Y');
}

#---------------------------------------------
# Include REDCap's Control Center page header
#---------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">'."\n";
$jsCode = '<script>'."\n"
    .'    $( function() {'."\n"
    .'        $("#startDate").datepicker();'."\n"
    .'        $("#endDate").datepicker();'."\n"
    .'    } );'."\n"
    .'</script>'."\n";
$buffer = str_replace('</head>', "    ".$link.$jsCode."</head>", $buffer);
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
    <img src="<?php echo APP_PATH_IMAGES.'csv.gif';?>" alt="" style="vertical-align: middle;">
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
    
    <div style="margin-bottom: 12px;">
        Log Entries:
        <select>
            <option value="<?php echo RedCapEtlModule::ETL_CRON?>">Cron Jobs</option>
            <option>ETL Processes</option>
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
    </div>
    
    <table class="etl-log">
        <thead>
        <?php
    
        #----------------------------------------------
        # Output table header based on log type
        #----------------------------------------------
        if ($logType === RedCapEtlModule::ETL_CRON) {
            echo "<tr> <th>Log ID</th> <th>TimeStamp</th> <th>User ID</th> <th>Project ID</th> <th>Message</th> </tr>\n";
        } elseif ($logType === RedCapEtlModule::ETL_RUN) {
            echo "<tr>\n";
            echo "<th>Log ID</th> <th>TimeStamp</th> <th>User ID</th> <th>Project ID</th>\n";
            echo "<th>Server</th> <th>Config</th> <th>Cron?</th> <th>Username</th>\n";
            echo "</tr>\n";
        } else {
            echo "<tr> <th>Log ID</th> <th>TimeStamp</th> <th>User ID</th> <th>Project ID</th> <th>Message</th> </tr>\n";
        }

        ?>

        </thead>
        <tbody>
            <?php

            $moduleLog = new ModuleLog($module);
            $logData = $moduleLog->getData($logType, $startDate, $endDate);

            foreach ($logData as $entry) {
                if ($logType === RedCapEtlModule::ETL_RUN) {
                    $cron = $entry['cron'];
                    echo "<tr>\n";
                    echo '<td style="text-align: right;">'.$entry['log_id']."</td>\n";
                    echo "<td>".$entry['timestamp']."</td>\n";
                    echo '<td style="text-align: right;">'.$entry['ui_id']."</td>\n";
                    echo '<td style="text-align: right;">'.$entry['project_id']."</td>\n";
                    echo "<td>".$entry['etl_server']."</td>\n";
                    echo "<td>".$entry['config']."</td>\n";
                    if ($cron) {
                        echo "<td>yes</td>\n";
                    } else {
                        echo "<td>no</td>\n";
                    }
                    echo "<td>".$entry['etl_username']."</td>\n";
                    echo "</tr>\n";
                } else {
                    $cron = null;
                    if (array_key_exists('cron', $entry)) {
                        $cron = $entry['cron'];
                    }
                    echo "<tr>\n";
                    echo '<td style="text-align: right;">'.$entry['log_id']."</td>\n";
                    echo "<td>".$entry['timestamp']."</td>\n";
                    echo '<td style="text-align: right;">'.$entry['ui_id']."</td>\n";
                    echo '<td style="text-align: right;">'.$entry['project_id']."</td>\n";
                    echo "<td>".$entry['message']."</td>\n";
                    echo "</tr>\n";
                }
            }
            ?>
        </tbody>
    </table>
    <?php Csrf::generateFormToken(); ?>
</form>



<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

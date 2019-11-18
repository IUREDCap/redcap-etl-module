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

#---------------------------------------------
# Include REDCap's Control Center page header
#---------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
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

<div style="margin-bottom: 1em;">
<button type="submit" value="Download CSV file" name="submitValue">
    <img src="<?php echo APP_PATH_IMAGES.'csv.gif';?>" alt="" style="vertical-align: middle;">
    <span  style="vertical-align: middle;"> Download CSV file</span>
</button>
</div>

                                                            
<table class="etl-log">
    <thead>
        <tr> <th>Log ID</th> <th>TimeStamp</th> <th>User ID</th> <th>Project ID</th> <th>Message</th> </tr>
    </thead>
    <tbody>
        <?php

        $moduleLog = new ModuleLog($module);
        $logData = $moduleLog->getData();

        foreach ($logData as $entry) {
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
        ?>
    </tbody>
</table>

<?php
print "<hr />\n";
print "<pre>\n";
print_r($entry);
print "</pre>\n";
?>


<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

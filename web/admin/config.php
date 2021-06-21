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

use \IU\REDCapETL\Version;

use \IU\RedCapEtlModule\AdminConfig;
use \IU\RedCapEtlModule\Csrf;
use \IU\RedCapEtlModule\Filter;
use \IU\RedCapEtlModule\Help;
use \IU\RedCapEtlModule\RedCapEtlModule;

try {
    $selfUrl     = $module->getUrl(RedCapEtlModule::ADMIN_HOME_PAGE);
    $cronInfoUrl = $module->getUrl(RedCapEtlModule::CRON_DETAIL_PAGE);

    $adminConfig = $module->getAdminConfig();

    $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);

    if (strcasecmp($submitValue, 'Save') === 0) {
        $adminConfig->set(Filter::stripTagsArrayRecursive($_POST));
        
        $module->setAdminConfig($adminConfig);
        $success = "Admin configuration saved.";
    }
} catch (Exception $exception) {
    $error = 'ERROR: ' . $exception->getMessage();
}
    
?>

<?php #require_once APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>

<?php
#---------------------------------------------
# Include REDCap's control center page header
#---------------------------------------------
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


<form action="<?php echo $selfUrl;?>" method="post">

    <p>
    Version: <?php echo Filter::escapeForHtml($module->getVersion()); ?>
    </p>
    
    <?php
    #--------------------------------------------------------
    # SSL Certificate Verification
    #--------------------------------------------------------
    $checked = '';
    if ($adminConfig->getSslVerify()) {
        $checked = 'checked';
    }
    ?>
    <input type="checkbox" name="<?php echo AdminConfig::SSL_VERIFY;?>" <?php echo $checked;?> >
    REDCap SSL certificate verification
    <br />

    <?php
    #--------------------------------------------------
    # Allow On Demand
    #--------------------------------------------------
    $checked = '';
    if ($adminConfig->getAllowOnDemand()) {
        $checked = 'checked';
    }
    ?>
    <input type="checkbox" name="<?php echo AdminConfig::ALLOW_ON_DEMAND;?>" <?php echo $checked;?>>
    Allow ETL jobs to be run on demand? <br />
    
    <?php
    #------------------------------------------------
    # Allow Cron (Scheduled) Jobs
    #------------------------------------------------
    $checked = '';
    if ($adminConfig->getAllowCron()) {
        $checked = 'checked';
    }
    ?>
    <input type="checkbox" name="<?php echo AdminConfig::ALLOW_CRON;?>" <?php echo $checked;?>>
    Allow ETL cron jobs? <br />

    <?php
    #---------------------------------------
    # Last cron run time
    #---------------------------------------
    $cronTime = $module->getLastRunTime();
    if (!isset($cronTime) || !is_array($cronTime)) {
        $cronTime = '';
    } else {
        $date    = $cronTime[0];
        $hour    = $cronTime[1];
        $minutes = $cronTime[2];
        if (strlen($hour) === 1) {
            $hour = '0' . $hour;
        }
        $cronTime = "{$date} {$hour}:{$minutes}";
    }
    ?>
    <br/>
    Last ETL cron run time: <?php echo $cronTime; ?><br />
    
    <?php
    #----------------------------------------------------------------
    # Allowed cron times table
    #----------------------------------------------------------------
    ?>
    <p style="text-align: center; margin-top: 14px;">Allowed ETL cron job times
    and number of scheduled jobs per time</p>
    
    <table class="cron-schedule admin-cron-schedule">
      <thead>
        <tr>
          <th>&nbsp;</th>
            <?php
            foreach (AdminConfig::DAY_LABELS as $dayLabel) {
                echo '<th class="day">' . $dayLabel . "</th>\n";
            }
            ?>
        </tr>
      </thead>
    <tbody>
        
    <?php
    #---------------------------------------------------
    # Allowed and schedule cron jobs
    #---------------------------------------------------
    $cronJobs = $module->getAllCronJobs();
    $row = 1;
    foreach (range(0, 23) as $time) {
        if ($row % 2 === 0) {
            echo '<tr class="even-row">' . "\n";
        } else {
            echo '<tr>' . "\n";
        }
        $row++;
        $label = $adminConfig->getHtmlTimeLabel($time);
        ?>

        <td class="time-range"><?php echo $label;?></td>
        
        <?php
        foreach (range(0, 6) as $day) {
            $name = AdminConfig::ALLOWED_CRON_TIMES . '[' . $day . '][' . $time . ']';
            $count = count($cronJobs[$day][$time]);

            $workflowCronJobs = $module->getWorkflowCronJobs($day, $time);
            $count += count($workflowCronJobs);
            
            $jobsUrl = $cronInfoUrl . '&selectedDay=' . $day . '&selectedTime=' . $time;

            $checked = '';
            if ($adminConfig->isAllowedCronTime($day, $time)) {
                $checked = ' checked ';
            }
            echo '<td class="day" style="position: relative;">' . "\n";
            echo '<input type="checkbox" name="' . $name . '" ' . $checked . '>';
            if ($count > 0) {
                echo '<a href="' . $jobsUrl . '" style="position: absolute; top: 1px; right: 4px;">' . $count . '</a>';
            }
            echo '</td>' . "\n";
        }
        ?>
      </tr>
        <?php
    }
    ?>
    </tbody>
  </table>

  <p>
    <input type="submit" name="submitValue" value="Save">
  </p>
    <?php Csrf::generateFormToken(); ?>
</form>

<?php
#print "<pre>\n"; print_r($cronJobs); print "</pre>\n";
?>

<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

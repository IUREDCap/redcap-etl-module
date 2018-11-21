<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

if (!SUPER_USER) {
    exit("Only super users can access this page!");
}

require_once __DIR__.'/../../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use \IU\RedCapEtlModule\RedCapEtlModule;

$selfUrl = $module->getUrl(RedCapEtlModule::ADMIN_HOME_PAGE);

$cronInfoUrl = $module->getUrl(RedCapEtlModule::CRON_DETAIL_PAGE);

$adminConfig = $module->getAdminConfig();

$submitValue = $_POST['submitValue'];
if (strcasecmp($submitValue, 'Save') === 0) {
    $times = $_POST['times'];
    $adminConfig->setAllowedCronTimes($times);
    
    $allowEmbeddedServer = $_POST['allowEmbeddedServer'];
    $adminConfig->setAllowEmbeddedServer($allowEmbeddedServer);
    
    $allowOnDemand = $_POST['allowOnDemand'];
    $adminConfig->setAllowOnDemand($allowOnDemand);
    
    $allowCron = $_POST['allowCron'];
    $adminConfig->setAllowCron($allowCron);
    
    $module->setAdminConfig($adminConfig);
    $success = "Admin configuration saved.";
}

?>

<?php #include APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>

<?php
#---------------------------------------------
# Include REDCap's control center page header
#---------------------------------------------
ob_start();
include APP_PATH_DOCROOT . 'ControlCenter/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
?>

<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>


<?php

$module->renderAdminTabs($selfUrl);

#----------------------------
# Display messages, if any
#----------------------------
$module->renderErrorMessageDiv($error);
$module->renderSuccessMessageDiv($success);

?>

<?php
#print "<pre>POST:\n"; print_r($_POST); print "</pre>\n";
?>

<form action="<?php echo $selfUrl;?>" method="post">

    <?php
    $checked = '';
    if ($adminConfig->getAllowEmbeddedServer()) {
        $checked = 'checked';
    }
    ?>
    <input type="checkbox" name="allowEmbeddedServer" <?php echo $checked;?>> Allow embedded REDCap-ETL server
    <br />
    
    <?php
    $checked = '';
    if ($adminConfig->getAllowOnDemand()) {
        $checked = 'checked';
    }
    ?>
    <input type="checkbox" name="allowOnDemand" <?php echo $checked;?>>
    Allow ETL jobs to be run on demand? <br />
    
    <?php
    $checked = '';
    if ($adminConfig->getAllowCron()) {
        $checked = 'checked';
    }
    ?>
    <input type="checkbox" name="allowCron" <?php echo $checked;?>>
    Allow ETL cron jobs? <br />

  <p style="text-align: center; margin-top: 14px;">Allowed ETL cron job times and number of jobs per time</p>
  <table class="cron-schedule admin-cron-schedule">
    <thead>
      <tr>
        <th>&nbsp;</th>
        <?php
        foreach (AdminConfig::DAY_LABELS as $dayLabel) {
            echo '<th class="day">'.$dayLabel."</th>\n";
        }
        ?>
      </tr>
    </thead>
    <tbody>
    <?php
    $cronJobs = $module->getAllCronJobs();
    $row = 1;
    foreach (range(0, 23) as $time) {
        if ($row % 2 === 0) {
            print '<tr class="even-row">'."\n";
        } else {
            print '<tr>'."\n";
        }
        $row++;
        $label = $adminConfig->getHtmlTimeLabel($time);
    ?>
        <td class="time-range"><?php echo $label;?></td>
        
        <?php
        foreach (range(0, 6) as $day) {
            $name = 'times['.$day.']['.$time.']';
            $count = count($cronJobs[$day][$time]);
            
            $jobsUrl = $cronInfoUrl.'&selectedDay='.$day.'&selectedTime='.$time;

            $checked = '';
            if ($adminConfig->isAllowedCronTime($day, $time)) {
                $checked = ' checked ';
            }
            echo '<td class="day" style="position: relative;">'."\n";
            echo '<input type="checkbox" name="'.$name.'" '.$checked.'>';
            if ($count > 0) {
                echo '<a href="'.$jobsUrl.'" style="position: absolute; top: 1px; right: 4px;">'.$count.'</a>';
            }
            echo '</td>'."\n";
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
</form>

<?php
#print "<pre>\n"; print_r($cronJobs); print "</pre>\n";
?>

<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

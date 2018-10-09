<?php

if (!SUPER_USER) {
    exit("Only super users can access this page!");
}

require_once __DIR__.'/dependencies/autoload.php';
#require_once __DIR__.'/AdminConfig.php';

use IU\RedCapEtlModule\AdminConfig;

$module = new \IU\RedCapEtlModule\RedCapEtlModule();
$selfUrl = $module->getUrl(basename(__FILE__));

$adminConfig = $module->getAdminConfig();

$submitValue = $_POST['submitValue'];
if (strcasecmp($submitValue, 'Save') === 0) {
    $times = $_POST['times'];
    $adminConfig->setAllowedCronTimes($times);
    
    $allowCron = $_POST['allowCron'];
    $adminConfig->setAllowCron($allowCron);
    
    $module->setAdminConfig($adminConfig);
    $success = "Admin configuration saved.";
}

?>

<?php #include APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>

<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
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

  <input type="checkbox"> Allow embedded REDCap-ETL server
  <br />
    
    <?php
    $checked = '';
    if ($adminConfig->getAllowCron()) {
        $checked = 'checked';
    }
    ?>
  <input type="checkbox" name="allowCron" <?php echo $checked;?>>
  Allow ETL cron jobs? <br />

  <p>Allowed ETL cron job times</p>
  <table class="cron-schedule">
    <thead>
      <tr>
        <th>&nbsp;</th>
        <?php
        foreach (AdminConfig::DAY_LABELS as $dayLabel) {
            echo '<th style="width: 6em">'.$dayLabel."</th>\n";
        }
        ?>
      </tr>
    </thead>
    <tbody>
    <?php
    $row = 1;
    foreach (range(0, 23) as $time) {
        if ($row % 2 === 0) {
            print '<tr class="even-row">'."\n";
        } else {
            print '<tr>'."\n";
        }
        $row++;
        $label = $adminConfig->getTimeLabel($time);
    ?>
        <td><?php echo $label;?></td>
        <?php
        foreach (range(0, 6) as $day) {
            $name = 'times['.$day.']['.$time.']';
            echo '<td class="day">'."\n";
            if ($adminConfig->isAllowedCronTime($day, $time)) {
                echo '<input type="checkbox" name="'.$name.'" checked>';
            } else {
                echo '<input type="checkbox" name="'.$name.'">';
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

<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

<?php

require_once __DIR__.'/AdminConfig.php';

use IU\RedCapEtlModule\AdminConfig;

$module = new \IU\RedCapEtlModule\RedCapEtlModule();
$selfUrl = $module->getUrl(basename(__FILE__));

$adminConfigJson = $module->getSystemSetting(AdminConfig::KEY);
$adminConfig = new AdminConfig();

?>

<?php include APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>

<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>

<?php $module->renderAdminTabs($selfUrl); ?>

<form>
  <?php
  $checked = '';
  if ($adminConfig->getAllowCron()) {;
      $checked = 'checked';
  }
  ?>

  Allow ETL cron jobs? <input type="checkbox" name="allowCron" <?php echo $checked;?>> <br />

  <p>Allowed ETL cron job times</p>
  <table class="dataTable" style="font-size: 75%;">
    <thead>
      <tr>
        <th>&nbsp;</th>
        <?php
        foreach (AdminConfig::DAY_LABELS as $dayLabel) {
            echo "<th>{$dayLabel}</th>\n";
        } 
        ?>
      </tr>
    </thead>
    <tbody>
      <?php
      $row = 1;
      foreach (range(0,23) as $label) {
          if ($row % 2 === 0) {
             print '<tr class="even">'."\n";
          } else {
              print '<tr class="odd">'."\n";
          }
          $row++;
      ?>
      <td><?php echo $label;?></td>
      <?php
      foreach (range(0,6) as $day) {
        echo '<td style="text-align: center;"><input type="checkbox"></td>'."\n";
      }
      ?>
      </tr>
      <?php
      }
      ?>
    </tbody>
  </table>
</form>

<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

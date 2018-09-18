<?php

require_once __DIR__.'/Configuration.php';
require_once __DIR__.'/redcap-etl/dependencies/autoload.php';

use IU\RedCapEtlModule\Configuration;

$error   = '';
$success = '';

$redCapEtlModule = new \IU\RedCapEtlModule\RedCapEtlModule();


$configurationNames = $redCapEtlModule->getUserConfigurationNames();

$selfUrl   = $redCapEtlModule->getUrl(basename(__FILE__));
$listUrl = $redCapEtlModule->getUrl("index.php");

$configName = $_POST['configName'];
if (empty($configName)) {
    $configName = $_GET['configName'];
}

if (!empty($configName)) {
    $configuration = $redCapEtlModule->getConfiguration($configName);
}

#-------------------------
# Set the submit value
#-------------------------
$submit = '';
if (array_key_exists('submit', $_POST)) {
    $submit = $_POST['submit'];
}

if (strcasecmp($submit, 'Run') === 0) {
    if (empty($configName)) {
        $error = 'ERROR: No ETL configuration specified.';
    } elseif (!isset($configuration)) {
        $error = 'ERROR: No ETL configuration found for '.$configName.'.';
    } else {
        try {
            $logger = new \IU\REDCapETL\NullLogger('REDCap-ETL');
            $properties = $configuration->getProperties();
            $redCapEtl  = new \IU\REDCapETL\RedCapEtl($logger, $properties);
            $redCapEtl->run();
            $success = 'Run completed.';
        } catch (Exception $exception) {
            $error = 'ERROR: '.$exception->getMessage();
        }
    }
}

?>

<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
?>

<div class="projhdr"> <!--h4 style="color:#800000;margin:0 0 10px;"> -->
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>


<?php $redCapEtlModule->renderUserTabs($selfUrl); ?>

<?php
#----------------------------
# Display error, if any
#----------------------------
if (!empty($error)) { ?>
<div class="red" style="margin:20px 0;font-weight:bold;">
    <img src="/redcap/redcap_v8.5.11/Resources/images/exclamation.png">
    <?php echo $error; ?>
    </div>
<?php } ?>

<?php
#-----------------------------------
# Display success message, if any
#-----------------------------------
if (!empty($success)) { ?>
<div align='center' class='darkgreen' style="margin: 20px 0;"><img src='/redcap/redcap_v8.5.11/Resources/images/accept.png'><?php echo $success;?></div>
<?php } ?>

<?php
# Configuration selection form
?>
<form action="<?php echo $selfUrl;?>" method="post" style="padding: 4px; margin-bottom: 0px; border: 1px solid #ccc; background-color: #ccc;">
    <span style="font-weight: bold;">Configuration:</span>
    <select name="configName" onchange="this.form.submit()">
    <?php
    $values = $redCapEtlModule->getUserConfigurationNames();
    array_unshift($values, '');
    foreach ($values as $value) {
        if (strcmp($value, $configName) === 0) {
            echo '<option value="'.$value.'" selected>'.$value."</option>\n";
        } else {
            echo '<option value="'.$value.'">'.$value."</option>\n";
        }
    }
    ?>
    </select>
</form>

<br />

<!-- Configuration form -->
<form action="<?php echo $selfUrl;?>" method="post">
  <input type="hidden" name="configName" value="<?php echo $configName; ?>" />
  <input type="hidden" name="<?php echo Configuration::CONFIG_API_TOKEN; ?>"
         value="<?php echo $properties[Configuration::CONFIG_API_TOKEN]; ?>" />
  <input type="hidden" name="<?php echo Configuration::TRANSFORM_RULES_SOURCE; ?>"
         value="<?php echo $properties[Configuration::TRANSFORM_RULES_SOURCE]; ?>" />
  <input type="submit" name="submit" value="Run" />
</form>

<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>



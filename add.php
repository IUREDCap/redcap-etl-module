<?php

require_once __DIR__.'/dependencies/autoload.php';

$redCapEtl = new \IU\RedCapEtlModule\RedCapEtlModule();

$selfUrl = $redCapEtl->getUrl("add.php");

$error = '';

$submit = $_POST['submit'];

#----------------------------------------------
# Add configuration submit
#----------------------------------------------
if (strcasecmp($submit, 'Add') === 0) {
    if (!array_key_exists('configurationName', $_POST) || empty($_POST['configurationName'])) {
        $error = 'ERROR: No configuration name was specified.';
    } else {
        $configurationName = $_POST['configurationName'];
        $configuration = $redCapEtl->getConfiguration($configurationName);
        if (isset($configuration)) {
            $error = 'ERROR: configuration "'.$configurationName.'" already exists.';
        } else {
            $indexUrl = $redCapEtl->getUrl("index.php");
            $redCapEtl->addConfiguration($configurationName);
            header('Location: '.$indexUrl);
        }
    }
}


?>


<?php include APP_PATH_DOCROOT . 'ProjectGeneral/header.php'; ?>

<div class="projhdr">
    <img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>


<?php 
$redCapEtl->renderUserTabs($selfUrl);
?>


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

<form action="<?=$selfUrl;?>" method="post">
    REDCap-ETL configuration name: <input name="configurationName" type="text">
    <br />
    <input type="submit" name="submit" value="Add" />
</form>


<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>



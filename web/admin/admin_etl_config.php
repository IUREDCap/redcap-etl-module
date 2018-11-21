<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

if (!SUPER_USER) {
    exit("Only super users can access this page!");
}

require_once __DIR__.'/../../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\RedCapEtlModule;

$selfUrl = $module->getUrl(RedCapEtlModule::ADMIN_ETL_CONFIG_PAGE);

$submitValue = $_POST['submitValue'];

if (strcasecmp($submitValue, 'Save') === 0) {
    $configName = $_POST['configName'];
    $username   = $_POST['username'];
    $projectId  = $_POST['projectId'];
    $configuration = $module->getConfiguration($configName, $username, $projectId);

    try {
        $configuration->set($_POST);
        $module->setConfiguration($configuration, $username, $projectId);
    } catch (\Exception $exception) {
        $error = 'ERROR: '.$exception->getMessage();
    }
} else {
    $configName = $_GET['config'];
    $username   = $_GET['username'];
    $projectId  = $_GET['pid'];

    $configuration = $module->getConfiguration($configName, $username, $projectId);
}

$properties = $configuration->getProperties();
    
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

$module->renderAdminTabs('');

#----------------------------
# Display messages, if any
#----------------------------
$module->renderErrorMessageDiv($error);
$module->renderSuccessMessageDiv($success);

?>

<?php
#print "<pre>POST:\n"; print_r($_POST); print "</pre>\n";
?>

<?php
echo '<h5><span style="font-weight: bold;">ETL Config "'
    .$configName.'" for user '.$username.', project ID '.$projectId.'</span></h5>'."\n";
?>


<?php
require __DIR__.'/config_form.php';
?>

<?php
#print "<pre>\n"; print_r($configuration); print "</pre>";
?>

<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

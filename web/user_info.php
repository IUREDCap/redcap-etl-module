<?php


/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapEtlModule;
use IU\RedCapEtlModule\ServerConfig;

#--------------------------------------------------------------
# If the user doesn't have permission to access REDCap-ETL for
# this project, redirect them to the access request page which
# should display a link to send e-mail to request permission.
#--------------------------------------------------------------
if (!Authorization::hasEtlProjectPagePermission($module, USERID)) {
    $requestAccessUrl = $module->getUrl('web/request_access.php');
    header('Location: '.$requestAccessUrl);
}

$error   = '';
$success = '';

$adminConfig = $module->getAdminConfig();

$selfUrl   = $module->getUrl('web/user_info.php');

$redcapEtlImage = $module->getUrl('resources/redcap-etl.png');

#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
?>

<div class="projhdr"> <!--h4 style="color:#800000;margin:0 0 10px;"> -->
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>

<?php
$module->renderProjectPageContentHeader($selfUrl, $error, $warning, $success);
?>


<h5 style="font-weight: bold;">Overview</h5>

<p>
The REDCap-ETL (Extract Transform Load) external module:
<ol>
    <li>Extracts data from REDCap</li>
    <li>Transforms the extracted data based on user-specified transformation rules</li>
    <li>Loads transformed data into a database</li>
</ol>
</p>

<p>
<img src="<?php echo $redcapEtlImage; ?>">
</p>

<hr />

<h5 style="font-weight: bold;">REDCap-ETL Configurations</h5>

To run REDCap-ETL, you need to create an ETL configuration.



  

<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

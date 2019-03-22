<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

if (!SUPER_USER) {
    exit("Only super users can access this page!");
}

require_once __DIR__.'/../../dependencies/autoload.php';

use \IU\REDCapETL\Version;

use \IU\RedCapEtlModule\AdminConfig;
use \IU\RedCapEtlModule\Filter;
use \IU\RedCapEtlModule\RedCapEtlModule;

$selfUrl   = $module->getUrl(RedCapEtlModule::ADMIN_INFO_PAGE);
$configUrl = $module->getUrl(RedCapEtlModule::ADMIN_HOME_PAGE);
$usersUrl  = $module->getUrl(RedCapEtlModule::USERS_PAGE);

$cronDetailUrl = $module->getUrl(RedCapEtlModule::CRON_DETAIL_PAGE);

$adminConfig = $module->getAdminConfig();

$redcapEtlImage = $module->getUrl('resources/redcap-etl.png');


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

$module->renderAdminPageContentHeader($selfUrl, $error, $success);

?>

<?php
#print "<pre>POST:\n"; print_r($_POST); print "</pre>\n";
?>

<p>
The REDCap-ETL (Extract Transform Load) external module:
<ol>
    <li>Extracts data from REDCap</li>
    <li>Transforms data based on user-specified transformation rules</li>
    <li>Loads transformed data into a database</li>
</ol>
</p>

<p>
<img src="<?php echo $redcapEtlImage; ?>">
</p>

REDCap-ETL has the following admin pages:
<ul>
    <li><a href="<?php echo $configUrl;?>" style="font-weight: bold;">Config</a>
        - General REDCap-ETL configuration with information on number and time of crom (scheduled) ETL jobs.
    </li>
    <li><a href="<?php echo $cronDetailUrl;?>" style="font-weight: bold;">Cron Detail</a>
    - Detailed information on cron (scheduled) ETL jobs.
    </li>
    <li><a href="<?php echo $usersUrl;?>" style="font-weight: bold;">Users</a>
    - List of users who have been given permission to use REDCap-ETL.
    </li>
</ul>    

<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

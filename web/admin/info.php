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

use \IU\REDCapETL\Version;

use \IU\RedCapEtlModule\AdminConfig;
use \IU\RedCapEtlModule\Filter;
use \IU\RedCapEtlModule\RedCapEtlModule;
use \IU\RedCapEtlModule\ServerConfig;

$selfUrl   = $module->getUrl(RedCapEtlModule::ADMIN_INFO_PAGE);

$configUrl     = $module->getUrl(RedCapEtlModule::ADMIN_HOME_PAGE);
$cronDetailUrl = $module->getUrl(RedCapEtlModule::CRON_DETAIL_PAGE);

$usersUrl       = $module->getUrl(RedCapEtlModule::USERS_PAGE);
$userConfigUrl  = $module->getUrl(RedCapEtlModule::USER_CONFIG_PAGE);

$etlServersUrl  = $module->getUrl(RedCapEtlModule::SERVERS_PAGE);
$etlServerConfigUrl = $module->getUrl(RedCapEtlModule::SERVER_CONFIG_PAGE);

$embeddedServerConfigUrl =
    $module->getUrl(RedCapEtlModule::SERVER_CONFIG_PAGE.'?serverName='.ServerConfig::EMBEDDED_SERVER_NAME);

$adminConfig = $module->getAdminConfig();

$redcapEtlImage = $module->getUrl('resources/redcap-etl.png');


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
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
?>

<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png" alt="">REDCap-ETL Admin</h4>


<?php

$module->renderAdminPageContentHeader($selfUrl, $error, $warning, $success);

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
<img src="<?php echo $redcapEtlImage; ?>" alt="">
</p>

<hr />

<h5 style="font-weight: bold;">REDCap-ETL Servers</h5>

The REDCap-ETL external module needs at least one REDCap-ETL server to function. There are 3 basic options:
<ol>
    <li>
        <span style="font-weight: bold;">Embedded Server</span>
        - the external module has an embedded REDCap-ETL server that can be used without
        any additional setup effort. It can be enabled/disabled and configured
        here: <a href="<?php echo $embeddedServerConfigUrl;?>" style="font-weight: bold;">Embedded ETL Server Config</a>
    </li>
    <li>
        <span style="font-weight: bold;">Standard REDCap-ETL Server</span>
        - a standard REDCap-ETL server is a stand-alone program that runs outside of REDCap.
        More information can be found here:
        <a href="https://github.com/IUREDCap/redcap-etl">https://github.com/IUREDCap/redcap-etl</a>.
        Setting up a standard REDCap-ETL server may require a fair amount of effort, but an
        advantage of doing this is that is will take much of the processing load
        from ETL processes off of your REDCap server.
    </li>
    <li>
        <span style="font-weight: bold;">Custom ETL Server</span>
        - you can set up a custom ETL server and then configure it within the
        external module. The custom server needs to be accessible by SSH (secure shell)
        and have a single command that can be executed to run a job on it.
    </li>
</ol>

<hr />

<h5 style="font-weight: bold;">Admin Pages</h5>

The REDCap-ETL external module has the following admin pages:
<ul>
    <li><a href="<?php echo $configUrl;?>" style="font-weight: bold;">Config</a>
        - General REDCap-ETL configuration with information on number and time of cron (scheduled) ETL jobs.
        <ul>
            <li>
                This is where you set whether users can run ETL jobs on demand (interactively) or schedule them.
            </li>
            <li>
                By default, users are allowed to schedule ETL jobs, but not run them interactively.
            </li>
        </ul>
    </li>
    <li><a href="<?php echo $cronDetailUrl;?>" style="font-weight: bold;">Cron Detail</a>
    - Detailed information on cron (scheduled) ETL jobs.
    </li>
    <li><a href="<?php echo $usersUrl;?>" style="font-weight: bold;">Users</a>
    - List of users who have been given permission to use REDCap-ETL.
    </li>
    <li><a href="<?php echo $userConfigUrl;?>" style="font-weight: bold;">User Search</a>
    - User search and user ETL permission configuration.
    </li>
    <li><a href="<?php echo $etlServersUrl;?>" style="font-weight: bold;">ETL Servers</a>
    - See list of existing ETL servers and add new ones.
    </li>
    <li><a href="<?php echo $etlServerConfigUrl;?>" style="font-weight: bold;">ETL Server Config</a>
    - ETL server configuration.
    </li>     
</ul>    

<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

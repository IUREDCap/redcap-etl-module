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

$selfUrl   = $module->getUrl('web/user_manual.php');
$transformationRulesUrl = $module->getUrl('web/transformation_rules.php');

$redcapEtlImage = $module->getUrl('resources/redcap-etl.png');

#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
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


<h4 style="font-weight: bold;">Overview</h4>

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

<h4 style="font-weight: bold;">REDCap-ETL Configurations</h4>

<p>
To run REDCap-ETL, you need to create an ETL configuration.
The configuration needs to specify at least the following things:
</p>
<ul>
    <li><strong>API Token</strong> - used to access the data to be extracted</li>
    <li><strong>Transformation Rules</strong> - that explain how the extracted data is transformed</li>
    <li><strong>Database Information</strong> - that contains the database and user account to be used
        for loading the extracted and transformed data</li>
</ul>

<p>
ETL configurations have a data export permission that matches the REDCap data export user right of
the user who created the configuration at the time the configuration was created. ETL configurations
are shared among the users of the project, but you will only be able to access configurations that
have a data export permission that is the same or less than yours.
</p>

<h5 style="font-weight: bold;">REDCap API Tokens</h5>

<p>
Each ETL configuration must specify a REDCap API token. The token is used to access REDCap so that
the data can be extracted from it.
The API token is specified by selecting the username of the owner of the API token.
So, users are allowed to use the API token of another user for ETL processing, but they cannot see the token.
Also, only tokens
that have the same data export permission as the configuration can be used. Since users can only access
configurations that have the same of less data export permission than they have, this means that users can
only use API tokens that have the same or less data export permission than they have.
</p>

<h5 style="font-weight: bold;">Transformation Rules</h5>

<p>
In your configuration, you need to specify transformation rules that
indicate how the data extracted from REDCap should be transformed before it
is loaded into your database.
</p>

<p>
An auto-generate button is provide that will generate transformation rules that
can be used as is, or modified.
</p>

<p>
More information on the transformation rules can be found here:
<a href="<?php echo $transformationRulesUrl; ?>" target="_blank">Transformation Rules</a>
</p>

<h5 style="font-weight: bold;">Database Information</h5>

<p>
You need to have a database for loading your extracted data. The
database needs to be accessible by the REDCap-ETL server that
you are using, and you need to have a user account for the database
that has at least the following permissions:
</p>
<ul>
    <li>SELECT</li>
    <li>INSERT</li>
    <li>CREATE</li>
    <li>DROP</li>
    <li>CREATE VIEW</li>
</ul>  

<p>
REDCap-ETL configurations allows post-processing SQL statements to be specified that
are run after the ETL process completes. The database user will also need to have
permission to execute any post-processing statements not coevered by the
permissions above.
</p>

<hr />

<h5 style="font-weight: bold;">REDCap-ETL Logging</h5>

<p>
There are 2 options for logging the results of your ETL processes, and they can be used simulatenously:
<ol>
    <li><strong>Data Logging</strong> - REDCap-ETL, by default, logs to 2 tables in your
    database where the your transformed data is loaded. The names of these tables
    can be changed in your configuration, or you can turn of this logging.</li>
    <li><strong>E-mail logging</strong> - you can specify in the ETL configuration that you
    want to receive an e-mail when an error occurs and/or that you recieve an e-mail
    summary of ETL processing when you process completes successfully.</li>
</ol>
</p>

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapEtlModule;
use IU\RedCapEtlModule\ServerConfig;

$error   = '';
$warning = '';
$success = '';

try {
    #--------------------------------------------------------------
    # If the user doesn't have permission to access REDCap-ETL for
    # this project, redirect them to the access request page which
    # should display a link to send e-mail to request permission.
    #--------------------------------------------------------------
    if (!Authorization::hasEtlProjectPagePermission($module)) {
        $requestAccessUrl = $module->getUrl('web/request_access.php');
        header('Location: '.$requestAccessUrl);
    }

    $adminConfig = $module->getAdminConfig();

    $selfUrl   = $module->getUrl('web/user_manual.php');
    $transformationRulesUrl = $module->getUrl('web/transformation_rules.php');

    $redcapEtlImage = $module->getUrl('resources/redcap-etl.png');
} catch (Exception $exception) {
    $error = 'ERROR: '.$exception->getMessage();
}


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
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png" alt="">REDCap-ETL
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
    <li>Loads the transformed data into a database</li>
</ol>
</p>

<p>
<img src="<?php echo $redcapEtlImage; ?>" alt="">
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
        for loading the transformed data</li>
</ul>

<p>
ETL configurations are shared among the users of the project who have permission to use REDCap-ETL.
</p>

<h5 style="font-weight: bold;">REDCap API Tokens</h5>

<p>
Each ETL configuration must specify a REDCap API token. The token is used to access REDCap so that
the data can be extracted from it.
The API token is specified by selecting the username of the owner of the API token.
Users are allowed to use the API token of any other
user who also has permission to user REDCap-ETL, but they cannot see the token.
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
<a href="<?php echo $transformationRulesUrl; ?>" target="_blank"><strong>Transformation Rules</strong></a>
</p>

<h5 style="font-weight: bold;">Database Information</h5>

<p>
You need to have a database for loading your transformed data. The
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
REDCap-ETL configurations allow post-processing SQL statements to be specified that
are run after the ETL process completes. The database user will also need to have
permission to execute any post-processing statements not covered by the
permissions above.
</p>

<hr />

<h5 style="font-weight: bold;">REDCap-ETL Logging</h5>

<p>
There are 2 options for logging the results of your ETL processes, and they can be used simultaneously:
</p>
<div style="max-width: 800px;">
<ol>
    <li><strong>Database Logging</strong> - REDCap-ETL, by default, logs to 2 tables in the
    database where your transformed data is loaded. The names of these tables
    can be changed in your configuration, or you can turn off this logging.</li>
    <li><strong>E-mail logging</strong> - you can specify that you
    want to receive an e-mail when an error occurs and/or that you receive an e-mail
    summary of ETL processing when your process completes successfully.</li>
</ol>
</div>

<h4 style="font-weight: bold;">Running REDCap-ETL</h4>

<p>
You will be able to run a REDCap-ETL configuration on the ETL servers to which
you have been granted access. A REDCap-ETL administrator sets the access-level
permissions for the ETL servers. Once you have permissions to a server, there
are 2 basic ways to run REDCap-ETL:
</p>
<div style="max-width: 800px;">
<ol>
    <li><strong>On Demand</strong> 
    <?php
    if (!$adminConfig->getAllowOnDemand()) {
        echo ' <span style="color: red">(disabled)</span> ';
    }
    ?>
    - You can run an ETL process on demand
    by going to the <strong>Run</strong>
    tab. The servers for which you have permissions will be displayed in a drop-down box.
    Select an ETL server, as well as an ETL configuration, and click the <strong>Run</strong>
    button.
    </li>
    <li><strong>Scheduled</strong>
    <?php
    if (!$adminConfig->getAllowCron()) {
        echo ' <span style="color: red">(disabled)</span> ';
    }
    ?>
    - You can use the <strong>Schedule</strong> tab to schedule an ETL
    job to run at specified times each week. For a given configuration, you can specify one hour per
    day of the week for the job to run.The servers for which you have
    permissions will be displayed in a drop-down box. Select a server,
    specify the day(s) and time(s), and click the <strong>Save</strong> button.
    </li>
</ol>
</div>

<p class="blue">
<strong>Note:</strong> REDCap-ETL deletes the tables specified in the transformation rules at the start of
each run, and then regenerates these tables. This is done
because there is no good way to know what data has changed in REDCap since the last time REDCap-ETL was run.
So you would not want to use these tables as a place to manually add data. 
However, you could create <em>additional</em> tables in the database that you update manually.
</p>

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

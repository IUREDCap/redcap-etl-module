<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__ . '/../dependencies/autoload.php';

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
        header('Location: ' . $requestAccessUrl);
    }

    $adminConfig = $module->getAdminConfig();

    $selfUrl   = $module->getUrl('web/user_manual.php');
    $transformationRulesUrl = $module->getUrl('web/transformation_rules.php');

    $redcapEtlImage = $module->getUrl('resources/redcap-etl.png');
} catch (Exception $exception) {
    $error = 'ERROR: ' . $exception->getMessage();
}


#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="' . $cssFile . '" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    " . $link . "\n</head>", $buffer);
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

<h4 style="font-weight: bold;">REDCap-ETL Workflows</h4>
<p>Workflows allow you to combine multiple ETL configurations into a unified process. Workflows
can be run immediately or scheduled to run on a daily or weekly basis.</p>
<p><b>Tasks.</b> Each task in a workflow corresponds to a REDCap project and utilizes an ETL 
configuration that was specified for that project. Workflow tasks run sequentially. A project 
can be used in more than one workflow task. For example, if there are two ETL configurations 
for a project, each with different database login information, you could add the project 
to the workflow as two tasks, each with a different ETL configuration to load the extracted
data to the database specified in the respective ETL configurations. Tasks are given a 
default name that you can change. Tasks can't have the same name as an ETL property,
for example 'batch_size.'
</p>
<p><b>Creating workflows and adding tasks.</b> Workflows are created (added) on the REDCap-ETL
'ETL Workflows' tab, which lists all workflows that exist for the project you are viewing.
Anyone with ETL access to a project can create a workflow. For a new workflow, the project 
currently being viewed is automatically added. On the REDCap-ETL 'Configure' tab, you can add 
any other projects to the workflow that you have ETL access to. Your workflow will appear on 
the 'ETL Workflows' tab for all other users who also have ETL access to that project. They will 
be able to add projects to the workflow you created. You will be able to see all of the tasks 
that were added by other people. However, if you don't have permissions to the task's project, 
you will see limited information.</p>
<p><b>Deleting tasks and workflows.</b> From the REDCap-ETL 'Configure' tab, you will be able 
to delete from a workflow any task that you have ETL permissions to. You will also be able to 
remove any workflow that you have access to using the 'ETL Workflows' tab. If you have ETL 
permissions to every project in the workflow, the workflow will be permanently deleted
when you remove it. If you do not have ETL permissions to all projects in the workflow, the workflow
will be assigned a status of 'Removed'. Only admins can see workflows that have been removed.
They will be able to reinstate the workflow if it was removed accidentally, as well as permanently
delete it.</p>
<p><b>Global properties.</b>You can specify certain workflow global properties that will override 
the values in the tasks. For example, if you want to ensure all e-mail notifications are enabled and
sent to the same e-mail address(es), you could complete the 'E-mail Notifications' section
of the global properties. Each task would then have 'E-mail errors' and 'E-mail summary',
enabled, and all such e-mails from the different tasks would be sent to the address(es) in
the 'Email to list' field. Anyone with access to the workflow can add or modify global properties.</p>
<p><b>Running workflows</b>. Workflows with a status of' Ready' can be run. A workflow receives
as status of 'Ready' once every task in the workflow has an ETL configuration assigned to it. 
Until then, a workflow has an status of 'Incomplete.'</p>

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
    tab.
    Select the ETL configuration or ETL workflow that you want to run. Select an ETL server,
    and click the <strong>Run</strong> button. In addition, for the case where you are running
    an ETL configuration on the "embedded server", you will have the option of downloading the
    extracted and transformed data as a CSV (comma-separated values) zip file, instead of loading the
    data into the database you specified in your ETL configuration (as is done for all other
    cases).
    </li>
    <li><strong>Scheduled</strong>
    <?php
    if (!$adminConfig->getAllowCron()) {
        echo ' <span style="color: red">(disabled)</span> ';
    }
    ?>
    - You can use the <strong>Schedule</strong> tab to schedule an ETL
    process to run at specified times each week. For a given configuration or workflow,
    you can specify one hour per day of the week for the process to run.
    After you have specified an ETL configuration or workflow, an
    ETL server, and the times the ETL process should run,
    click the <strong>Save</strong> button to save your schedule.
    </li>
</ol>
</div>

<p class="blue">
<strong>Note:</strong> REDCap-ETL deletes the tables specified in the transformation rules at the start of
each run, and then regenerates these tables. This is done
because there is no good way to know what data has changed in REDCap since the last time REDCap-ETL was run.
So, you would not want to use these tables as a place to manually add data. 
However, you could create <em>additional</em> tables in the database that you update manually.
</p>

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__ . '/../vendor/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapEtlModule;
use IU\RedCapEtlModule\ServerConfig;
use IU\RedCapEtlModule\ExtRedCapProject;

$error   = '';
$warning = '';
$success = '';

try {
    #-----------------------------------------------------------
    # Check that the user has permission to access this page
    # and get the configuration if one was specified
    #-----------------------------------------------------------
    $configCheck = false;
    $runCheck = true;
    $configuration = $module->checkUserPagePermission(USERID, $configCheck, $runCheck);
    $configName = '';
    if (!empty($configuration)) {
        $configName = $configuration->getName();
    }

    $adminConfig = $module->getAdminConfig();

    $servers = $module->getServers();

    $selfUrl   = $module->getUrl('web/run.php');
    $listUrl   = $module->getUrl('web/index.php');

    #------------------------------------------
    # Get the server
    #------------------------------------------
    $server = Filter::stripTags($_POST['server']);
    if (empty($server)) {
        $server = $_SESSION['server'];
    } else {
        $_SESSION['server'] = $server;
    }

    #-------------------------
    # Set the submit value
    #-------------------------
    $submit = '';
    if (array_key_exists('submit', $_POST)) {
        $submit = Filter::sanitizeButtonLabel($_POST['submit']);
    }

    $runOutput = '';
    if (strcasecmp($submit, 'Run') === 0) {
        if (empty($configName)) {
            $error = 'ERROR: No ETL configuration specified.';
        } elseif (!isset($configuration)) {
            $error = 'ERROR: No ETL configuration found for ' . $configName . '.';
        } else {
            $isCronJob = false;
            $runOutput = $module->run($configName, $server, $isCronJob);
        }
    }
} catch (Exception $exception) {
    $error = 'ERROR: ' . $exception->getMessage();
}

?>

<?php
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

<?php



$apiUrl = $configuration->getProperty(Configuration::REDCAP_API_URL);
$dataToken = $configuration->getProperty(Configuration::DATA_SOURCE_API_TOKEN);
$sslVerify = false;
$caCertfile = null;
$dataProject = new \IU\REDCapETL\EtlRedCapProject($apiUrl, $dataToken, $sslVerify, $caCertFile);
$metadata = $dataProject->getMetadata();
$projectInfo = $dataProject->exportProjectInfo();
$instruments = $dataProject->exportInstruments();
$projectXml  = $dataProject->exportProjectXml($metadataOnly = true);
$recIdField  = $dataProject->getRecordIdFieldName();
$mappings = $dataProject->exportInstrumentEventMappings();
$batches = $dataProject->getRecordIdBatches(10);
$records = $dataProject->exportRecordsAp();


$extProject = new \IU\RedCapEtlModule\EtlExtRedCapProject(null, null, $sslVerify, $caCertFile);
$metadata2 = $extProject->getMetadata();
$projectInfo2 = $extProject->exportProjectInfo();
$instruments2 = $extProject->exportInstruments();
$projectXml2  = $extProject->exportProjectXml($metadataOnly = true);
$recIdField2  = $extProject->getRecordIdFieldName();
$mappings2    = $extProject->exportInstrumentEventMappings();
$batches2     = $extProject->getRecordIdBatches(10);
$records2     = $extProject->exportRecordsAp();

$lookupChoices  = $dataProject->getLookupChoices();
$lookupChoices2 = $extProject->getLookupChoices();

$fieldNames  = $dataProject->getFieldNames();
$fieldNames2 = $extProject->getFieldNames();

$fieldTypeMap  = $dataProject->getFieldTypeMap();
$fieldTypeMap2 = $extProject->getFieldTypeMap();

$primaryKey1 = $dataProject->getPrimaryKey();
$primaryKey2 = $extProject->getPrimaryKey();


$fields1 = $dataProject->exportFieldNames();
$fields2 = $extProject->exportFieldNames();

$userRights = REDCap::getUserRights(USERID);
$dataExportRight = $userRights[USERID]['data_export_tool'];
print('<pre>');
print_r($dataExportRight);
print('</pre>');

if (array_map(trim, $metadata) == array_map(trim, $metadata2)) {
    print "Metadata matches <br/>\n";
} else {
    print "Metadata does NOT match<br/>\n";
}

if ($fieldTypeMap === $fieldTypeMap2) {
    print "Field type maps match <br/>\n";
} else {
    print "Field type maps do NOT match<br/>\n";
}

if ($instruments === $instruments2) {
    print "Intruments match<br/>\n";
} else {
    print "Instruments do NOT match<br/>\n";
}

if ($lookupChoices === $lookupChoices2) {
    print "Lookup choices match<br/>\n";
} else {
    print "Lookup choices do NOT match<br/>\n";
}

if ($mappings === $mappings2) {
    print "Mappings match <br/>\n";
} else {
    print "Mappings do NOT match<br/>\n";
}

if ($primaryKey1 === $primaryKey2) {
    print "Primary keys match <br/>\n";
} else {
    print "Primary keys do NOT match<br/>\n";
}

if ($records === $records2) {
    print "Records match <br/>\n";
} else {
    print "Records do NOT match<br/>\n";
}

if ($batches === $batches2) {
    print "Record ID batches match <br/>\n";
} else {
    print "Record ID batches do NOT match<br/>\n";
}

print "<br/>\n";

print "<table border=\"1\">\n";


print "<tr>\n";
print "<td><pre>\n";
print "EXPORT FIELD NAMES:\n";
print_r($fields1);
print "</pre></td>\n";
print "<td><pre>\n";
print "EXPORT FIELD NAMES:\n";
print_r($fields2);
print "</pre></td>\n";
print "</tr>\n";


print "<tr>\n";
print "<td><pre>\n";
print "FIELD TYPE MAP:\n";
print_r($fieldTypeMap);
print "</pre></td>\n";
print "<td><pre>\n";
print "FIELD TYPE MAP:\n";
print_r($fieldTypeMap2);
print "</pre></td>\n";
print "</tr>\n";


print "<tr>\n";
print "<td><pre>\n";
print "FIELD NAMES:\n";
print_r($fieldNames);
print "</pre></td>\n";
print "<td><pre>\n";
print "FIELD NAMES:\n";
print_r($fieldNames2);
print "</pre></td>\n";
print "</tr>\n";




print "<tr>\n";
print "<td><pre>\n";
print_r($lookupChoices);
print "</pre></td>\n";
print "<td><pre>\n";
print_r($lookupChoices2);
print "</pre></td>\n";
print "</tr>\n";


/*
print "<tr>\n";
print "<td><pre>\n";
print "METADATA:\n";
print_r($metadata);
print "</pre></td>\n";
print "<td><pre>\n";
print "METADATA:\n";
print_r($metadata2);
print "</pre></td>\n";
print "</tr>\n";
*/

print "<tr>\n";
print "<td><pre>\n";
print_r($projectInfo);
print "</pre></td>\n";
print "<td><pre>\n";
print_r($projectInfo2);
print "</pre></td>\n";
print "</tr>\n";

print "<tr>\n";
print "<td><pre>\n";
print_r($instruments);
print "</pre></td>\n";
print "<td><pre>\n";
print_r($instruments2);
print "</pre></td>\n";
print "</tr>\n";

/*
print "<tr>\n";
print "<td><pre>\n";
print_r(htmlspecialchars($projectXml));
print "</pre></td>\n";
print "<td><pre>\n";
print_r(htmlspecialchars($projectXml2));
print "</pre></td>\n";
print "</tr>\n";
 */

print "<tr>\n";
print "<td><pre>\n";
print_r($recIdField);
print "</pre></td>\n";
print "<td><pre>\n";
print_r($recIdField2);
print "</pre></td>\n";
print "</tr>\n";

print "<tr>\n";
print "<td><pre>\n";
print "MAPPINGS <br/>\n";
print_r($mappings);
print "</pre></td>\n";
print "<td><pre>\n";
print "MAPPINGS <br/>\n";
print_r($mappings2);
print "</pre></td>\n";
print "</tr>\n";

/*
print "<tr>\n";
print "<td><pre>\n";
print_r($batches);
print "</pre></td>\n";
print "<td><pre>\n";
print_r($batches2);
print "</pre></td>\n";
print "</tr>\n";
*/

/*
print "<tr>\n";
print "<td><pre>\n";
print_r($records);
print "</pre></td>\n";
print "<td><pre>\n";
print_r($records2);
print "</pre></td>\n";
print "</tr>\n";
*/

print "</table>\n";

/*
print "<div><pre>\n";
print_r(htmlspecialchars($projectXml2));
print "</pre></div>\n";
*/

?>


<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

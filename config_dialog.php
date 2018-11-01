<?php

if (!SUPER_USER) {
    exit("Only super users can access this page!");
}

use IU\RedCapEtlModule\Configuration;

echo "Config Dialog";

$module = new \IU\RedCapEtlModule\RedCapEtlModule();

$configName = $_GET['config'];
$username   = $_GET['username'];
$projectId  = $_GET['projectId'];

$configuration = $module->getConfiguration($configName, $username, $projectId);

$properties = $configuration->getProperties();

require __DIR__.'/config_form.php';

#echo "<pre>\n";
#print_r($configuration);
#echo "</pre>\n";

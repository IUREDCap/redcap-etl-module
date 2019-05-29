<?php

#---------------------------------------------
# Get the help info for the specified topic
#---------------------------------------------
$module->checkAdminPagePermission();

require_once __DIR__.'/../../dependencies/autoload.php';

use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\Filter;

$topic = '';
if (array_key_exists('topic', $_GET)) {
    $topic = trim(Filter::sanitizeButtonLabel($_GET['topic']);
}

$data = Help::getHelp($topic);

echo $data;

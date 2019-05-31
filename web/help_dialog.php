<?php

#---------------------------------------------
# Get the help info for the specified topic
#---------------------------------------------
$module->checkUserPagePermission(USERID);

require_once __DIR__.'/../dependencies/autoload.php';

use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\Filter;

$topic = '';
if (array_key_exists('topic', $_GET)) {
    $topic = trim(Filter::sanitizeButtonLabel($_GET['topic']));
}

$help = Help::getHelp($topic, $module);

#echo '<div title="'.$topic.'>'."\n";
echo "{$help}\n";
#echo "</div>\n";

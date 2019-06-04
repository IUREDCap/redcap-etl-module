<?php

#---------------------------------------------
# Get the help info for the specified topic
#---------------------------------------------
$module->checkUserPagePermission(USERID);

require_once __DIR__.'/../dependencies/autoload.php';

use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\Filter;

$setting = (int) $_POST['setting'];
$defaultHelp = Filter::sanitizeHelp($_POST['defaultHelp']);
$customHelp  = Filter::sanitizeHelp($_POST['customHelp']);

$help = Help::getHelpFromText($setting, $defaultHelp, $customHelp);

#echo '<div title="'.$topic.'>'."\n";
echo $help;
#echo "</div>\n";

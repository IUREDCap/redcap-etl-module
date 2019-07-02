<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

#---------------------------------------------
# Get the help info for the specified topic
#---------------------------------------------
$module->checkAdminPagePermission();

require_once __DIR__.'/../../dependencies/autoload.php';

use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\Filter;

$setting = (int) $_POST['setting'];
$defaultHelp = Filter::sanitizeHelp($_POST['defaultHelp']);
$customHelp  = Filter::sanitizeHelp($_POST['customHelp']);

$help = Help::getHelpFromText($setting, $defaultHelp, $customHelp);

#echo '<div title="'.$topic.'>'."\n";
#echo '<div style="max-height: 300px; overflow-y: auto; padding-right: 7px;">';
echo $help;
#echo "</div>\n";

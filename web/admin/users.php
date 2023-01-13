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


require_once __DIR__ . '/../../vendor/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

$selfUrl  = $module->getUrl(RedCapEtlModule::USERS_PAGE);
$adminUrl = $module->getURL(RedCapEtlModule::ADMIN_HOME_PAGE);
$userUrl  = $module->getURL(RedCapEtlModule::USER_CONFIG_PAGE);

$adminConfigJson = $module->getSystemSetting(AdminConfig::KEY);
$adminConfig = new AdminConfig();


$submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);
$username    = Filter::stripTags($_POST['username-result']);

$users = $module->getUsers();

#---------------------------------------------
# Include REDCap's Control Center page header
#---------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="' . $cssFile . '" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    " . $link . "\n</head>", $buffer);
echo $buffer;
?>



<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png" alt="">REDCap-ETL Admin</h4>

<?php

$errorMessage   = Filter::stripTags($_GET['error']);
$successMessage = Filter::stripTags($_GET['success']);

$module->renderAdminPageContentHeader($selfUrl, $errorMessage, $warningMessage, $successMessage);
$module->renderAdminUsersSubTabs($selfUrl);

?>


<div style="margin-top: 3em;">
    <h5 style="float: left;">REDCap-ETL Users</h5>
    <!-- HELP -->
    <div style="float: right;">
        <a href="#" id="etl-users-help-link" class="etl-help" style="margin-left: 17px;" title="help">?</a>
    </div>
    <div style="clear: both;"></div>
</div>

<div id="etl-users-help" title="ETL Users" style="display: none;">
    <?php echo Help::getHelpWithPageLink('etl-users', $module); ?>
</div>

<table class="dataTable">
    <thead>
        <tr> <th>username</th> <th>ETL Project Permissions</th> <th>ETL Configurations</th> </tr>
    </thead>
    <tbody>
    <?php
    $row = 1;
    foreach ($users as $user) {
        $etlProjects = $module->getUserEtlProjects($user);
        $configCount = 0;
        foreach ($etlProjects as $etlProject) {
            $configNames = $module->getConfigurationNames($etlProject);
            $configCount += count($configNames);
        }
        $userConfigUrl = $userUrl . '&username=' . Filter::escapeForUrlParameter($user);
        if ($row % 2 == 0) {
            echo "<tr class=\"even\">\n";
        } else {
            echo "<tr class=\"odd\">\n";
        }
        echo '<td><a href="' . $userConfigUrl . '">' . Filter::escapeForHtml($user) . '</td>' . "\n";
        echo '<td style="text-align: right;">' . count($etlProjects) . "</td>\n";
        echo '<td style="text-align: right;">' . $configCount . "</td>\n";
        echo "</tr>\n";
        $row++;
    }
    ?>
    </tbody>
</table>


<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

<script>
    $(document).ready(function() {
        $( function() {
            $('#etl-users-help-link').click(function () {
                $('#etl-users-help').dialog({dialogClass: 'redcap-etl-help', width: 440, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'left-420 top+80', of: $(this)})
                    ;
                return false;
            });
        });
    });
</script>


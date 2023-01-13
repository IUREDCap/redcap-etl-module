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

use IU\REDCapETL\Version;

use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\RedCapEtlModule;

try {
    $selfUrl     = $module->getUrl(RedCapEtlModule::HELP_LIST_PAGE);

    $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);

    if (strcasecmp($submitValue, 'Save') === 0) {
        $success = "Help saved.";
    }
} catch (Exception $exception) {
    $error = 'ERROR: ' . $exception->getMessage();
}

?>

<?php #require_once APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>

<?php
#---------------------------------------------
# Include REDCap's control center page header
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

$module->renderAdminPageContentHeader($selfUrl, $error, $warning, $success);
$module->renderAdminHelpEditSubTabs($selfUrl);

?>

<table class="dataTable">
    <thead>
        <tr> <th>Topic</th> <th>Setting</th> </th><th>Edit</th>
    </thead>
    <tbody>
        <?php
        $topics = Help::getTopics();

        $row = 1;
        foreach ($topics as $topic) {
            $editUrl = $module->getUrl('web/admin/help_edit.php?topic=' . $topic);

            if ($row % 2 == 0) {
                echo "<tr class=\"even\">\n";
            } else {
                echo "<tr class=\"odd\">\n";
            }
            echo '<td>' . Help::getTitle($topic) . '</td>';
            echo '<td>' . Help::getSettingText($module->getHelpSetting($topic)) . '</td>';
            echo '<td style="text-align:center;">'
                . '<a href="' . $editUrl . '" id="' . Help::getTitle($topic) . '">'
                . '<img src="' . APP_PATH_IMAGES . 'page_white_edit.png" alt="EDIT">'
                . '</a>'
                . "</td>\n";
            echo "</tr>\n";
            $row++;
        }
        ?>
    </tbody>
</table>
                    
<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

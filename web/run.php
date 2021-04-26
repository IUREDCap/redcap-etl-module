<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__ . '/../dependencies/autoload.php';

use IU\RedCapEtlModule\Csrf;

try {
    #-----------------------------------------------------------
    # Check that the user has permission to access this page
    #-----------------------------------------------------------
    $module->checkUserPagePermission(USERID, $configCheck);

    #-------------------------------------------------------------------
    # Check for test mode (which should only be used for development)
    #-------------------------------------------------------------------
    $testMode = false;
    if (@file_exists(__DIR__.'/../test-config.ini')) {
        $testMode = true;
    }

    $taskRunUrl  = $module->getUrl("web/task_run.php");
    $workflowRunUrl  = $module->getUrl("web/workflow_run.php");
    $selfUrl  = $module->getUrl("web/run.php");
} catch (\Exception $exception) {
    $error = 'ERROR: '.$exception->getMessage();
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

<div class="projhdr"> 
    <img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png" alt="">REDCap-ETL
    <?php
    if ($testMode) {
        echo '<span style="color: blue;">[TEST MODE]</span>';
    }
    ?>
</div>


<?php
$module->renderProjectPageContentHeader($selfUrl, $error, $warning, $success);
?>


<?php
#-------------------------------------
# Configuration selection form
#-------------------------------------
?>
<form action="<?php echo $selfUrl;?>" method="post" 

      style="padding: 4px; margin-bottom: 0px; border: 1px solid #ccc;">

    <div style="padding:10px;">
        <span style="font-weight: bold;">What do to you want to run:</span>
    </div>

    <div>
        <input type="radio" name="configureType" value="etl" id="etl"
            onclick="document.location.href='<?php echo $taskRunUrl; ?>'">
        <label for="etl">An ETL configuration</label>
    </div>

    <div>
        <input type="radio" name="configureType" value="workflow" id="workflow"
            onclick="document.location.href='<?php echo $workflowRunUrl; ?>'">
        <label for="etl">A workflow configuration</label>
    </div>
    <?php Csrf::generateFormToken(); ?>
</form>

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>


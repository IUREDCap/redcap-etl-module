<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\ServerConfig;

$error   = '';
$warning = '';
$success = '';

$pid = PROJECT_ID;
$username = USERID;

$workflowName = Filter::escapeForHtml($_GET['workflowName']);
if (empty($workflowName)) {
    $workflowName = $_POST['workflowName'];  
}


try {
    $excludeIncomplete = true;
    $projectWorkflows = $module->getProjectAvailableWorkflows($pid, $excludeIncomplete);

    $noReadyProjects = false;
    if (empty($projectWorkflows)) { 
        $noReadyProjects = true;
    }

    if (!empty($workflowName)) {
        $p = array_search($workflowName, $projectWorkflows); 
        $workflowReady = $p === false ? false : true;
    }

    array_unshift($projectWorkflows, '');

    #-----------------------------------------------------------
    # Check that the user has permission to access this page
    # and get the configuration if one was specified
    #-----------------------------------------------------------
    $configCheck = false;
    $runCheck = true;
    $module->checkUserPagePermission(USERID, $configCheck, $runCheck);

    $adminConfig = $module->getAdminConfig();
    $selfUrl   = $module->getUrl('web/workflow_run.php');
    $runUrl   = $module->getUrl('web/run.php');

    #-------------------------
    # Set the submit value
    #-------------------------
    $submit = '';
    if (array_key_exists('submit', $_POST)) {
        $submit = Filter::sanitizeButtonLabel($_POST['submit']);
    }

    $runOutput = '';
    if (strcasecmp($submit, 'Run') === 0) {
        $workflowName = $_POST['workflowName'];
        if (empty($workflowName)) {
            $error = 'ERROR: No workflow specified.';
        } else  {
            $server = ServerConfig::EMBEDDED_SERVER_NAME;
            $isCronJob = false;
            $originatingProjectId = $pid;
            #Get projects that this user has access to
            $db = new RedCapDb();
            $userProjects = $db->getUserProjects($username);
 
            $runOutput = $module->runWorkflow($workflowName, $server, $username, $userProjects, $isCronJob, $originatingProjectId);
        }
    }
} catch (Exception $exception) {
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
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
?>

<div class="projhdr"> <!--h4 style="color:#800000;margin:0 0 10px;"> -->
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png" alt="">REDCap-ETL
</div>

<?php
$module->renderProjectPageContentHeader($runUrl, $error, $warning, $success);
?>

<?php
#print '<pre>'; print_r($_POST); print '</pre>'."\n";
?>

<?php
if ($workflowName && !$workflowReady) {
    $msg = 'The selected workflow '.$workflowName.' is not yet ready to run. ';
	$msg .=  'If you wish to run this workflow, return to the workflow configuration page to complete the configuration.';
	echo '<span style="font-weight: bold;">'.$msg.'</span>';
} elseif ($noReadyProjects) {
	echo '<span style="font-weight: bold;">There are no workflows with a status of READY for this project.</span>';
} else {
	       
#---------------------------------------
# Configuration selection form
#---------------------------------------
?>
<form action="<?php echo $selfUrl;?>" method="post" 
      style="padding: 4px; margin-bottom: 0px; border: 1px solid #ccc; background-color: #ccc;">
    <span style="font-weight: bold;">ETL Workflow:</span>
    <select name="workflowName" onchange="this.form.submit()">

    <?php
    foreach ($projectWorkflows as $value) {
        if (strcmp($value, $workflowName) === 0) {
            echo '<option value="'.Filter::escapeForHtmlAttribute($value).'" selected>'
                .Filter::escapeForHtml($value)."</option>\n";
        } else {
            echo '<option value="'.Filter::escapeForHtmlAttribute($value).'">'
                .Filter::escapeForHtml($value)."</option>\n";
        }
    }
    ?>
    </select>
    <?php Csrf::generateFormToken(); ?>
</form>

<br />

<!-- Run form -->
<?php
#$allowEmbeddedServer = $adminConfig->getAllowEmbeddedServer();
?>
<form action="<?php echo $selfUrl;?>" method="post">
    <fieldset style="border: 2px solid #ccc; border-radius: 7px; padding: 7px;">
        <legend style="font-weight: bold;">Run Now</legend>
        
        <input type="hidden" name="workflowName"
            value="<?php echo Filter::escapeForHtmlAttribute($workflowName); ?>" />
        
        <input type="submit" name="submit" value="Run"
            style="color: #008000; font-weight: bold;"
            onclick='$("#runOutput").text(""); $("body").css("cursor", "progress");' <?php echo $disabled; ?>/>
  <p><pre id="runOutput"><?php echo Filter::escapeForHtml($runOutput);?></pre></p>
  </fieldset>
    <?php Csrf::generateFormToken(); ?>
</form>


<?php } # end if (noReadyProjects) ?> 

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>



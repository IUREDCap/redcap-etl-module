<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapEtlModule;
use IU\RedCapEtlModule\ServerConfig;

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

    #$servers = $module->getServers();
    $servers   = $module->getUserAllowedServersBasedOnAccessLevel(USERID);

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
            $error = 'ERROR: No ETL configuration found for '.$configName.'.';
        } else {
            $configuration->validateForRunning();
            $isCronJob = false;
            $runOutput = $module->run($configName, $server, $isCronJob);
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
$module->renderProjectPageContentHeader($selfUrl, $error, $warning, $success);
?>

<?php
#print '<pre>'; print_r($configuration); print '</pre>'."\n";
#print '<pre>'; print_r($servers); print '</pre>'."\n";
?>

<?php
#---------------------------------------
# Configuration selection form
#---------------------------------------
?>
<form action="<?php echo $selfUrl;?>" method="post" 
      style="padding: 4px; margin-bottom: 0px; border: 1px solid #ccc; background-color: #ccc;">
    <span style="font-weight: bold;">Configuration:</span>
    <select name="configName" onchange="this.form.submit()">

    <?php
    $values = $module->getAccessibleConfigurationNames();
    array_unshift($values, '');
    foreach ($values as $value) {
        if (strcmp($value, $configName) === 0) {
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
        
        <input type="hidden" name="configName"
            value="<?php echo Filter::escapeForHtmlAttribute($configName); ?>" />
        
        <input type="submit" name="submit" value="Run"
            style="color: #008000; font-weight: bold;"
            onclick='$("#runOutput").text(""); $("body").css("cursor", "progress");'/>
        on
        <?php
        
        echo '<select name="server" id="serverId">'."\n";
            
        foreach ($servers as $serverName) {
            $serverConfig = $module->getServerConfig($serverName);
            
            if (isset($serverConfig) && $serverConfig->getIsActive()) {
                $selected = '';
                if ($serverName === $server) {
                    $selected = 'selected';
                }
                echo '<option value="'.Filter::escapeForHtmlAttribute($serverName).'" '.$selected.'>'
                    .Filter::escapeForHtml($serverName)."</option>\n";
            }
        }
        echo "</select>\n";
        ?>
  <p><pre id="runOutput"><?php echo Filter::escapeForHtml($runOutput);?></pre></p>
  </fieldset>
    <?php Csrf::generateFormToken(); ?>
</form>


<?php
#print '<pre>'; print_r($servers); print '</pre>'."\n";
#print '<pre>'; print_r($serverConfig); print '</pre>'."\n";
?>


<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>



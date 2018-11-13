<?php

require_once __DIR__.'/dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Logger;
use IU\RedCapEtlModule\ServerConfig;

$error   = '';
$success = '';

$module = new \IU\RedCapEtlModule\RedCapEtlModule();

$adminConfig = $module->getAdminConfig();

$servers = $module->getServers();

$configurationNames = $module->getUserConfigurationNames();

$selfUrl   = $module->getUrl(basename(__FILE__));
$listUrl = $module->getUrl("index.php");

#-------------------------------------------
# Get the configuration name
#-------------------------------------------
$configName = $_POST['configName'];
if (empty($configName)) {
    $configName = $_GET['configName'];
    if (empty($configName)) {
        $configName = $_SESSION['configName'];
    }
}

if (!empty($configName)) {
    $_SESSION['configName'] = $configName;
}

if (!empty($configName)) {
    $configuration = $module->getConfiguration($configName);
    if (empty($configuration)) {
        $configName = null;
    }
}

#------------------------------------------
# Get the server
#------------------------------------------
$server = $_POST['server'];
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
    $submit = $_POST['submit'];
}

$runOutput = '';
if (strcasecmp($submit, 'Run') === 0) {
    if (empty($configName)) {
        $error = 'ERROR: No ETL configuration specified.';
    } elseif (!isset($configuration)) {
        $error = 'ERROR: No ETL configuration found for '.$configName.'.';
    } else {
        try {
            if (strcasecmp($server, ServerConfig::EMBEDDED_SERVER_NAME) === 0) {
                # Embedded serv er
                if (!$adminConfig->getAllowEmbeddedServer()) {
                    throw new \Exception('Running on the embedded REDCap-ETL server is not allowed.');
                }
                $logger = new \IU\REDCapETL\Logger('REDCap-ETL');
                $logger->turnOff();
                $logger->setPrintInfo(true);
                $properties = $configuration->getPropertiesArray();
                $redCapEtl  = new \IU\REDCapETL\RedCapEtl($logger, $properties);
                
                # Capture run command print output
                ob_start();
                $redCapEtl->run();
                $runOutput = ob_get_contents();
                ob_end_clean();

                #$runOutput = 'Job submitted to '.ServerConfig::EMBEDDED_SERVER_NAME.'.';
                \REDCap::logEvent('Run REDCap-ETL configuration '.$configName.'.');
            } else {
                $serverConfig = $module->getServerConfig($server);
                $runOutput = $serverConfig->run($configuration);
            }
        } catch (Exception $exception) {
            $error = 'ERROR: '.$exception->getMessage();
        }
    }
}

?>

<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
?>

<div class="projhdr"> <!--h4 style="color:#800000;margin:0 0 10px;"> -->
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>


<?php $module->renderUserTabs($selfUrl); ?>

<?php
#----------------------------
# Display error, if any
#----------------------------
if (!empty($error)) { ?>
<div class="red" style="margin:20px 0;font-weight:bold;">
    <img src="/redcap/redcap_v8.5.11/Resources/images/exclamation.png">
    <?php echo $error; ?>
    </div>
<?php } ?>

<?php
#-----------------------------------
# Display success message, if any
#-----------------------------------
if (!empty($success)) { ?>
<div align='center' class='darkgreen' style="margin: 20px 0;">
    <img src='/redcap/redcap_v8.5.11/Resources/images/accept.png'><?php echo $success;?>
</div>
<?php } ?>

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
    $values = $module->getUserConfigurationNames();
    array_unshift($values, '');
    foreach ($values as $value) {
        if (strcmp($value, $configName) === 0) {
            echo '<option value="'.$value.'" selected>'.$value."</option>\n";
        } else {
            echo '<option value="'.$value.'">'.$value."</option>\n";
        }
    }
    ?>
    </select>
</form>

<br />

<!-- Run form -->
<?php
$allowEmbeddedServer = $adminConfig->getAllowEmbeddedServer();
?>
<form action="<?php echo $selfUrl;?>" method="post">
    <fieldset style="border: 2px solid #ccc; border-radius: 7px; padding: 7px;">
        <legend style="font-weight: bold;">Run Now</legend>
        <input type="hidden" name="configName" value="<?php echo $configName; ?>" />
        <input type="submit" name="submit" value="Run" /> on
        <?php

        echo '<select name="server">'."\n";
            
        if ($adminConfig->getAllowEmbeddedServer()) {
            $selected = '';
            if (strcasecmp($server, ServerConfig::EMBEDDED_SERVER_NAME) === 0) {
                $selected = 'selected';
            }
      
            echo '<option value="'.ServerConfig::EMBEDDED_SERVER_NAME.'" '.$selected.'>'
                .ServerConfig::EMBEDDED_SERVER_NAME
                .'</option>'."\n";
        }
        
        foreach ($servers as $serverName) {
            $selected = '';
            if ($serverName === $server) {
                $selected = 'selected';
            }
            echo '<option value="'.$serverName.'" '.$selected.'>'.$serverName."</option>\n";
        }
        echo "</select>\n";
        ?>
  <p><?php echo "<pre>{$runOutput}</pre>\n";?></p>
  </fieldset>
</form>


<?php
#print '<pre>'; print_r($servers); print '</pre>'."\n";
?>


<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>



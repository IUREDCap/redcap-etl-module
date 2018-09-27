<?php

if (!SUPER_USER) exit("Only super users can access this page!");

require_once __DIR__.'/ServerConfig.php';


use IU\RedCapEtlModule\ServerConfig;


$module = new \IU\RedCapEtlModule\RedCapEtlModule();
$selfUrl      = $module->getUrl(basename(__FILE__));
$configureUrl = $module->getUrl('server_configure.php');
$serversUrl   = $module->getUrl('servers.php');

$submit = $_POST['submit'];
$serverName = $_POST['serverName'];
if (empty($serverName)) {
    $serverName = $_GET['serverName'];
}


if (!empty($serverName)) {
    $serverConfig = $module->getServerConfig($serverName);
}
    
#------------------------------------
# Router
#------------------------------------
if (strcasecmp($submit, 'Save') === 0) {
    if (empty($serverName)) {
        $error = 'ERROR: no server name specified.';
    } else {
         $serverConfig = new ServerConfig($serverName);
        try {
            $serverConfig->set($_POST);
            $module->setServerConfig($serverConfig);
            header('Location: '.$serversUrl);
        } catch (Exception $exception) {
            $error = 'ERROR: '.$exception->getMessage();
        }
    }
} elseif (strcasecmp($submit, 'Cancel') === 0) {
    header('Location: '.$serversUrl);
}

?>

<?php #include APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>
<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
include APP_PATH_DOCROOT . 'ControlCenter/header.php'; 
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
?>


<?php
#print "SUBMIT = {$submit} <br/> \n";
#print "serverName: = {$serverName} <br/> \n";
#print "ServerConfig: <pre><br />\n"; print_r($serverConfig); print "</pre> <br/> \n";
#print "POST: <pre><br />\n"; print_r($_POST); print "</pre> <br/> \n";
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>

<?php $module->renderAdminTabs($selfUrl); ?>

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
# Server selection form
?>
<form action="<?php echo $selfUrl;?>" method="post" style="padding: 4px; margin-bottom: 12px; border: 1px solid #ccc; background-color: #ccc;">
    <span style="font-weight: bold;">Server:</span>
    <select name="serverName" onchange="this.form.submit()">
    <?php
    $values = $module->getServers();
    array_unshift($values, '');
    foreach ($values as $value) {
        if (strcmp($value, $serverName) === 0) {
            echo '<option value="'.$value.'" selected>'.$value."</option>\n";
        } else {
            echo '<option value="'.$value.'">'.$value."</option>\n";
        }
    }
    ?>
    </select>
</form>

<?php # echo "user-search: ".$_POST['user-search']."<br/>\n"; ?>
<?php # echo "username-result: ".$_POST['username-result']."<br/>\n"; ?>
<?php # print "<pre>"; print_r($userInfo); print "</pre>"; ?>

<?php
#----------------------------------------------------
# Server configuration form
#----------------------------------------------------
if (!empty($serverName)) {
?>
<form action=<?php echo $selfUrl;?> method="post">
  <input type="hidden" name="serverName" value="<?php echo $serverConfig->getName();?>">
  <table>
    <tr>
      <td>Server address:</td>
      <td><input type="text" name="serverAddress" value="<?php echo $serverConfig->getServerAddress();?>"
                 size="60" style="margin: 4px;"></td>
    </tr>
    <tr>
      <td style="padding-top: 4px; padding-bottom: 4px; vertical-align: top;">Authentication method:</td>
      <td style="padding: 4px;">
        <input type="radio" name="authMethod" value="ssh-key" style="vertical-align: middle; margin: 0;">
        <span style="vertical-align: top; margin-right: 8px;">SSH Key</span>
        <input type="radio" name="authMethod" value="password" style="vertical-align: middle; margin: 0;">
        <span style="vertical-align: top; margin-right: 8px;">Password</span>
      </td>
    </tr>
    <tr>
      <td>Username:</td>
      <td><input type="text" name="serverUsername" size="28" style="margin: 4px;"></td>
    </tr>
    <tr>
      <td>SSH private key file:</td>
      <td><input type="text" name="sshKeyFile" size="28" style="margin: 4px;"></td>
    </tr>
    <tr>
      <td>Password:</td>
      <td><input type="text" name="password" size="28" style="margin: 4px;"></td>
    </tr>
    <tr>
      <td>Configuration directory:</td>
      <td><input type="text" name="configDir" size="60" style="margin: 4px;"></td>
    </tr>
    <tr>
      <td>ETL command:</td>
      <td><input type="text" name="etlCommand" size="60" style="margin: 4px;"></td>
    </tr>
  </table>
  <div style="margin-top: 20px;">
    <div style="width: 50%; float: left;">
      <input type="submit" name="submit" value="Save" style="margin: auto; display: block;">
    </div>
    <div style="width: 50%; float: right;">
      <input type="submit" name="submit" value="Cancel" style="margin: auto; display: block;">
    </div>
    <div style="clear: both;">
    </div>
  </div>
</form>
<?php
}
?>

<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

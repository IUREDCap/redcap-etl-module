<?php

if (!SUPER_USER) exit("Only super users can access this page!");

require_once __DIR__.'/Servers.php';
require_once __DIR__.'/RedCapDb.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\RedCapDb;

$module = new \IU\RedCapEtlModule\RedCapEtlModule();
$selfUrl = $module->getUrl(basename(__FILE__));
$configureUrl = $module->getUrl('server_configure.php');

$submit = $_POST['submit'];

$serverName = $_POST['server-name'];
if (!empty($serverName)) {
    if (strcasecmp($submit, 'Add Server') === 0) {
        $module->addServer($serverName);
    }
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
$servers = $module->getServers();
#print "Servers: <pre><br />\n"; print_r($servers); print "</pre> <br/> \n";
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>

<?php $module->renderAdminTabs($selfUrl); ?>

<?php # echo "user-search: ".$_POST['user-search']."<br/>\n"; ?>
<?php # echo "username-result: ".$_POST['username-result']."<br/>\n"; ?>
<?php # print "<pre>"; print_r($userInfo); print "</pre>"; ?>

<h5 style="margin-top: 2em;">REDCap-ETL Server Configuration</h5>


<form action=<?php echo $selfUrl;?> method="post">
  <table>
    <tr>
      <td>Server address:</td>
      <td><input type="text" name="serverAddress" size="60" style="margin: 4px;"></td>
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
  <input type="submit" name="submit" value="Save">
</form>

<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

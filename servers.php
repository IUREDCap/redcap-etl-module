<?php

if (!SUPER_USER) exit("Only super users can access this page!");

require_once __DIR__.'/Servers.php';
require_once __DIR__.'/RedCapDb.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\RedCapDb;

$module = new \IU\RedCapEtlModule\RedCapEtlModule();
$selfUrl = $module->getUrl(basename(__FILE__));
$configureUrl = $module->getUrl('server_config.php');

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

<form action="<?php echo $selfUrl;?>" method="post">
Server: <input type="text" id="server-name" name="server-name" size="48">
<input type="submit" name="submit" value="Add Server"><br />
</form>
    <!--
<div class="ui-widget">
  <label for="user">User: </label>
  <input type="text" id="user-search" size="40">
</div>
-->



<form action="<?php echo $selfUrl;?>" method="post" style="margin-top: 14px;">
  <div style="vertical-align: bottom;">
    <input type="checkbox">
    Allow embedded REDCap-ETL server
  </div>
</form>

<h5 style="margin-top: 2em;">REDCap-ETL Servers</h5>

<table class="dataTable">
  <thead>
    <tr> <th>Server Name</th> <th>Configure</th> </tr>
  </thead>
  <tbody>
    <?php
    $row = 1;
    foreach ($servers as $server) {
        
      if ($row % 2 == 0) {
          echo "<tr class=\"even\">\n";
      } else {
          echo "<tr class=\"odd\">\n";
      }
      print "<td>{$server}</td>\n";

      $serverConfigureUrl = $configureUrl.'&serverName='.$server;
      print '<td style="text-align:center;">'
          .'<a href="'.$serverConfigureUrl.'"><img src='.APP_PATH_IMAGES.'gear.png></a>'
          ."</td>\n";

      print "</tr>\n";
      $row++;
    }
    ?>
  </tbody>
</table>

<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

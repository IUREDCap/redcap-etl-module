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

$delete = $_POST['delete'];
if (!empty($delete)) {
    $module->removeServer($delete);
}

$copyFromServerName = $_POST['copy-from-server-name'];
$copyToServerName   = $_POST['copy-to-server-name'];
if (!empty($copyFromServerName) && !empty($copyToServerName)) {
    try {
        $module->copyServerConfig($copyFromServerName, $copyToServerName);
    } catch (Exception $exception) {
        $error = 'ERROR: ' . $exception->getMessage();
    }
}

$servers = $module->getServers();

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
$jsInclude = '<script type="text/javascript" src="'.($module->getUrl('resources/servers.js')).'"></script>';
$buffer = str_replace('</head>', "    {$link}\n{$jsInclude}\n</head>", $buffer);

echo $buffer;
?>


<?php
# print "SUBMIT = {$submit} <br/> \n";
#print "serverName: = {$serverName} <br/> \n";
#print "POST: <pre><br />\n"; print_r($_POST); print "</pre> <br/> \n";
#print "ERROR: {$error}\n";
#print "delete: ".$_POST['delete']."<br />\n";
#print "Servers: <pre><br />\n"; print_r($servers); print "</pre> <br/> \n";
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>


<?php
#-------------------------------------
# Render page sub-navigation tabs
#-------------------------------------
$module->renderAdminTabs($selfUrl);
?>

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

<table class="dataTable">
  <thead>
    <tr> <th>Server Name</th> <th>Configure</th> <th>Copy</th> <th>Delete</th> </th></tr>
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

      print '<td style="text-align:center;">'
          .'<img src="'.APP_PATH_IMAGES.'page_copy.png" id="copy-'.$server.'" class="copyServer" style="cursor: pointer;">'
          ."</td>\n";
          
      print '<td style="text-align:center;">';
      print '<form action="'.$selfUrl.'" method="post">'
            .'<input type="hidden" name="delete" value="'.$server.'">'
            .'<img src='.APP_PATH_IMAGES.'delete.png onclick="$(this).closest(\'form\').submit();" style="cursor: pointer;">'
            .'</form>';
      print "</td>\n";
      
      print "</tr>\n";
      $row++;
    }
    ?>
  </tbody>
</table>

<div id="copy-dialog"
    title="Server Configuration Copy"
    style="display: none;"
    >
    <form id="copy-form" action="<?php echo $selfUrl;?>" method="post">
    To copy the server <span id="server-to-copy" style="font-weight: bold;"></span>,
    enter the name of the new server below, and click on the <span style="font-weight: bold;">Copy server</span> button.
    <p>
    <span style="font-weight: bold;">New server name:</span> <input type="text" name="copy-to-server-name" id="new-server-name">
    </p>
    <input type="hidden" name="copy-from-server-name" id="copy-from-server-name" value="">
    </form>
</div>



<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

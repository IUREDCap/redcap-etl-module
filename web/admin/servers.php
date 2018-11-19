<?php

if (!SUPER_USER) {
    exit("Only super users can access this page!");
}

require_once __DIR__.'/../../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\RedCapEtlModule;

$module = new RedCapEtlModule();
$selfUrl = $module->getUrl(RedCapEtlModule::SERVERS_PAGE);
$configureUrl = $module->getUrl(RedCapEtlModule::SERVER_CONFIG_PAGE);

$submit = $_POST['submit'];

$serverName = $_POST['server-name'];

if (!empty($serverName)) {
    if (strcasecmp($submit, 'Add Server') === 0) {
        $module->addServer($serverName);
    }
}


$copyFromServerName = $_POST['copy-from-server-name'];
$copyToServerName   = $_POST['copy-to-server-name'];
if (!empty($copyFromServerName) && !empty($copyToServerName)) {
    try {
        $module->copyServer($copyFromServerName, $copyToServerName);
    } catch (Exception $exception) {
        $error = 'ERROR: ' . $exception->getMessage();
    }
}

$renameServerName    = $_POST['rename-server-name'];
$renameNewServerName = $_POST['rename-new-server-name'];
if (!empty($renameServerName) && !empty($renameNewServerName)) {
    try {
        $module->renameServer($renameServerName, $renameNewServerName);
    } catch (Exception $exception) {
        $error = 'ERROR: ' . $exception->getMessage();
    }
}

$deleteServerName = $_POST['delete-server-name'];
if (!empty($deleteServerName)) {
    $module->removeServer($deleteServerName);
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


<form action="<?php echo $selfUrl;?>" method="post" style="margin-bottom: 12px;">
Server: <input type="text" id="server-name" name="server-name" size="40">
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
    <tr> <th>Server Name</th> <th>Configure</th> <th>Copy</th> <th>Rename</th> </th><th>Delete</th> </th></tr>
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
            .'<img src="'.APP_PATH_IMAGES.'page_copy.png" id="copy-'.$server.'"'
            .' class="copyServer" style="cursor: pointer;">'
            ."</td>\n";

        print '<td style="text-align:center;">'
            .'<img src="'.APP_PATH_IMAGES.'page_white_edit.png" id="rename-'.$server.'"'
            .' class="renameServer" style="cursor: pointer;">'
            ."</td>\n";
          
        print '<td style="text-align:center;">'
              .'<img src="'.APP_PATH_IMAGES.'delete.png" id="delete-'.$server.'"'
              .' class="deleteServer" style="cursor: pointer;">'
              ."</td>\n";
      
        print "</tr>\n";
        $row++;
    }
    ?>
  </tbody>
</table>

<?php
#--------------------------------------
# Copy server dialog
#--------------------------------------
?>
<div id="copy-dialog"
    title="Server Configuration Copy"
    style="display: none;"
    >
    <form id="copy-form" action="<?php echo $selfUrl;?>" method="post">
    To copy the server <span id="server-to-copy" style="font-weight: bold;"></span>,
    enter the name of the new server below, and click on the <span style="font-weight: bold;">Copy server</span> button.
    <p>
    <span style="font-weight: bold;">New server name:</span>
    <input type="text" name="copy-to-server-name" id="copy-to-server-name">
    </p>
    <input type="hidden" name="copy-from-server-name" id="copy-from-server-name" value="">
    </form>
</div>

<?php
#--------------------------------------
# Rename server dialog
#--------------------------------------
?>
<div id="rename-dialog"
    title="Server Configuration Rename"
    style="display: none;"
    >
    <form id="rename-form" action="<?php echo $selfUrl;?>" method="post">
    To rename the server <span id="server-to-rename" style="font-weight: bold;"></span>,
    enter the new name for the new server below, and click on the
    <span style="font-weight: bold;">Rename server</span> button.
    <p>
    <span style="font-weight: bold;">New server name:</span>
    <input type="text" name="rename-new-server-name" id="rename-new-server-name">
    </p>
    <input type="hidden" name="rename-server-name" id="rename-server-name" value="">
    </form>
</div>

<?php
#--------------------------------------
# Delete server dialog
#--------------------------------------
?>
<div id="delete-dialog"
    title="Server Configuration Delete"
    style="display: none;"
    >
    <form id="delete-form" action="<?php echo $selfUrl;?>" method="post">
    To delete the server <span id="server-to-delete" style="font-weight: bold;"></span>,
    click on the <span style="font-weight: bold;">Delete server</span> button.
    <input type="hidden" name="delete-server-name" id="delete-server-name" value="">
    </form>
</div>


<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

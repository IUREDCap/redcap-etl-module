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

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\RedCapEtlModule;
use IU\RedCapEtlModule\ServerConfig;

$selfUrl = $module->getUrl(RedCapEtlModule::SERVERS_PAGE);
$configureUrl = $module->getUrl(RedCapEtlModule::SERVER_CONFIG_PAGE);

$submit = Filter::sanitizeLabel($_POST['submit']);

#-------------------------------
# Set server location values
#-------------------------------
$serverLocation = Filter::sanitizeLabel($_POST['serverLocation']);
if (!isset($serverLocation)) {
    $serverLocation = ServerConfig::LOCATION_EMBEDDED;
} elseif ($serverLocation !== ServerConfig::LOCATION_EMBEDDED && $serverLocation !== ServerConfig::LOCATION_REMOTE) {
    $serverLocation = ServerConfig::LOCATION_EMBEDDED;
}

$embeddedChecked = '';
$remoteChecked = '';
if ($serverLocation === ServerConfig::LOCATION_REMOTE) {
    $remoteChecked = 'checked="checked"';
} else {
    $embeddedChecked = 'checked="checked"';
}


$serverName = Filter::sanitizeString($_POST['server-name']);

if (!empty($serverName)) {
    if (strcasecmp($submit, 'Add Server') === 0) {
        try {
            ServerConfig::validateName($serverName);
            $module->addServer($serverName, $serverLocation);
        } catch (Exception $exception) {
            $error = 'ERROR: ' . $exception->getMessage();
        }
    }
}


$copyFromServerName = Filter::sanitizeString($_POST['copy-from-server-name']);
$copyToServerName   = Filter::sanitizeString($_POST['copy-to-server-name']);
if (!empty($copyFromServerName) && !empty($copyToServerName)) {
    try {
        $module->copyServer($copyFromServerName, $copyToServerName);
    } catch (Exception $exception) {
        $error = 'ERROR: ' . $exception->getMessage();
    }
}

$renameServerName    = Filter::sanitizeString($_POST['rename-server-name']);
$renameNewServerName = Filter::sanitizeString($_POST['rename-new-server-name']);
if (!empty($renameServerName) && !empty($renameNewServerName)) {
    try {
        $module->renameServer($renameServerName, $renameNewServerName);
    } catch (Exception $exception) {
        $error = 'ERROR: ' . $exception->getMessage();
    }
}

$deleteServerName = Filter::sanitizeString($_POST['delete-server-name']);
if (!empty($deleteServerName)) {
    $module->removeServer($deleteServerName);
}


$servers = $module->getServers();

?>

<?php #require_once APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>
<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="' . $cssFile . '" rel="stylesheet" type="text/css" media="all">';
$jsInclude = '<script type="text/javascript" src="' . ($module->getUrl('resources/servers.js')) . '"></script>';
$buffer = str_replace('</head>', "    {$link}\n{$jsInclude}\n</head>", $buffer);

echo $buffer;
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png" alt="">REDCap-ETL Admin</h4>


<?php
#-------------------------------------------------
# Render page content header (tabs and messages)
#-------------------------------------------------
$module->renderAdminPageContentHeader($selfUrl, $error, $warning, $success);
$module->renderAdminEtlServerSubTabs($selfUrl);
?>



<form action="<?php echo $selfUrl;?>" method="post" style="margin-bottom: 12px; margin-top: 17px; padding: 7px;">

    <fieldset class="server-config">

    <input type="submit" name="submit" value="Add Server" style="font-weight: bold;"/>
    &nbsp;
    <label for="server-name">Name:</label> <input type="text" id="server-name" name="server-name" size="40">
    <!-- HELP -->
    <a href="#" id="etl-servers-help-link" class="etl-help" style="margin-left: 17px;" title="help">?</a>
    <div id="etl-servers-help" title="ETL Servers" style="display: none;">
        <?php echo Help::getHelpWithPageLink('etl-servers', $module); ?>
    </div>
    <div>
        <span style="margin-left: 8em">Location:</span>
        <input type="radio" name="serverLocation" value="<?php echo ServerConfig::LOCATION_EMBEDDED; ?>"
               <?php echo $embeddedChecked; ?>>
            &nbsp;embedded
        </input>
        <input type="radio" name="serverLocation" value="<?php echo ServerConfig::LOCATION_REMOTE; ?>"
               <?php echo $remoteChecked; ?>>
            &nbsp; remote
        </input>
    </div>
    <?php Csrf::generateFormToken(); ?>
    <input type="hidden" name="redcap_csrf_token" value="<?php echo $module->getCsrfToken(); ?>"/>
    </fieldset>
</form>
    <!--
<div class="ui-widget">
  <label for="user">User: </label>
  <input type="text" id="user-search" size="40">
</div>
-->


<table class="dataTable">
  <thead>
    <tr> <th>ETL Server Name</th> <th>Active</th> <th>Location</th> <th>Access</th><th>Configure</th>
    <th>Copy</th> <th>Rename</th> </th><th>Delete</th> </th></tr>
  </thead>
  <tbody>
    <?php
    $row = 1;
    foreach ($servers as $server) {
        $serverConfig = $module->getServerConfig($server);

        if ($row % 2 == 0) {
            echo "<tr class=\"even\">\n";
        } else {
            echo "<tr class=\"odd\">\n";
        }
        echo "<td>" . Filter::escapeForHtml($server) . "</td>\n";

        $serverConfigureUrl = $configureUrl . '&serverName=' . Filter::escapeForUrlParameter($server);

        #-------------------------------
        # Active
        #-------------------------------
        echo '<td style="text-align:center;">';
        if ($serverConfig->getIsActive()) {
            echo '<img src="' . APP_PATH_IMAGES . 'tick.png" alt="Yes">';
        } else {
            echo '<img src="' . APP_PATH_IMAGES . 'cross.png" alt="No">';
        }
        echo "</td>\n";

        #-------------------------------
        # Location
        #-------------------------------
        echo '<td style="text-align:center;">';
        echo $serverConfig->getLocation();
        echo "</td>\n";

        #-------------------------------
        # Access Level
        #-------------------------------
        $accessLevel = $serverConfig->getAccessLevel();
        if (empty($accessLevel)) {
            $accessLevel = 'public';
        }
        echo '<td style="text-align:center;">';
        echo $accessLevel;
        echo "</td>\n";

        #-------------------------------
        # Configure
        #-------------------------------
        echo '<td style="text-align:center;">'
            . '<a href="' . $serverConfigureUrl . '">'
            . '<img src="' . APP_PATH_IMAGES . 'gear.png" alt="CONFIG"></a>'
            . "</td>\n";


        #-------------------------------
        # Copy
        #-------------------------------
        echo '<td style="text-align:center;">'
            . '<input type="image" src="' . APP_PATH_IMAGES . 'page_copy.png" alt="COPY" '
            . 'id="copyServer' . $row . '"'
            . ' class="copyServer" style="cursor: pointer;">'
            . "</td>\n";

        if (strcasecmp($server, ServerConfig::EMBEDDED_SERVER_NAME) === 0) {
            echo "<td>&nbsp;</td><td>&nbsp;</td>\n";
        } else {
            #-------------------------------
            # Rename
            #-------------------------------
            echo '<td style="text-align:center;">'
                . '<input type="image" src="' . APP_PATH_IMAGES . 'page_white_edit.png" alt="RENAME"'
                . ' id="renameServer' . $row . '"'
                . ' class="renameServer" style="cursor: pointer;">'
                . "</td>\n";

            #-------------------------------
            # Delete
            #-------------------------------
            echo '<td style="text-align:center;">'
                .  '<input type="image" src="' . APP_PATH_IMAGES . 'delete.png" alt="DELETE"'
                . ' id="deleteServer' . $row . '"'
                . ' class="deleteServer" style="cursor: pointer;">'
                . "</td>\n";
        }

        echo "</tr>\n";
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
        enter the name of the new server below,
        and click on the <span style="font-weight: bold;">Copy server</span> button.
        <p>
        <span style="font-weight: bold;">New server name:</span>
        <input type="text" name="copy-to-server-name" id="copy-to-server-name">
        </p>
        <input type="hidden" name="copy-from-server-name" id="copy-from-server-name" value="">
        <?php Csrf::generateFormToken(); ?>
        <input type="hidden" name="redcap_csrf_token" value="<?php echo $module->getCsrfToken(); ?>"/>
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
        <?php Csrf::generateFormToken(); ?>
        <input type="hidden" name="redcap_csrf_token" value="<?php echo $module->getCsrfToken(); ?>"/>
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
        <?php Csrf::generateFormToken(); ?>
        <input type="hidden" name="redcap_csrf_token" value="<?php echo $module->getCsrfToken(); ?>"/>
    </form>
</div>

<?php

#-----------------------------------------------------------------------
# Set up click event handlers for the server copy/rename/delete buttons
#-----------------------------------------------------------------------
echo "<script>\n";

$row = 1;
foreach ($servers as $server) {
    echo '$("#copyServer' . $row . '").click({server: "'
        . Filter::escapeForJavaScriptInDoubleQuotes($server)
        . '"}, RedCapEtlModule.copyServer);' . "\n";
    echo '$("#renameServer' . $row . '").click({server: "'
        . Filter::escapeForJavaScriptInDoubleQuotes($server)
        . '"}, RedCapEtlModule.renameServer);' . "\n";
    echo '$("#deleteServer' . $row . '").click({server: "'
        . Filter::escapeForJavaScriptInDoubleQuotes($server)
        . '"}, RedCapEtlModule.deleteServer);' . "\n";
    $row++;
}

echo "</script>\n";
?>

<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

<script>
    $(document).ready(function() {
        $( function() {
            $('#etl-servers-help-link').click(function () {
                $('#etl-servers-help').dialog({dialogClass: 'redcap-etl-help', width: 440, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right-184 top+144', of: $(this)})
                    ;
                return false;
            });
        });
    });
</script>

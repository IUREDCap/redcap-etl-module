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

use phpseclib\Crypt\RSA;
use phpseclib\Net\SCP;
use phpseclib\Net\SFTP;
use phpseclib\Net\SSH2;

use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\ServerConfig;
use IU\RedCapEtlModule\RedCapEtlModule;

use IU\RedCapEtlModule\DataTarget;

$selfUrl      = $module->getUrl(RedCapEtlModule::SERVER_CONFIG_PAGE);
$serversUrl   = $module->getUrl(RedCapEtlModule::SERVERS_PAGE);
$configureUserUrl = $module->getUrl(RedCapEtlModule::USER_CONFIG_PAGE);

$adminConfig = $module->getAdminConfig();

$privateServerUsersUrl    = $module->getUrl('web/admin/private_server_users.php');
$privateServerSetUsersUrl = $module->getUrl('web/admin/private_server_set_users.php');

$userSearchUrl = $module->getUrl('web/admin/user_search.php');

$submit = Filter::sanitizeButtonLabel($_POST['submitValue']);

#-------------------------------------------
# Get the server name
#-------------------------------------------
$serverName = Filter::sanitizeString($_POST['serverName']);
if (empty($serverName)) {
    $serverName = Filter::sanitizeString($_GET['serverName']);
    if (empty($serverName)) {
        $serverName = Filter::sanitizeString($_SESSION['serverName']);
    }
}

try {
    ServerConfig::validateName($serverName);
} catch (Exception $exception) {
    $serverName = '';
}

if (!empty($serverName)) {
    $_SESSION['serverName'] = $serverName;
}

#-------------------------------------------------------------------
# If the server name is set, get the configuration for that server
#-------------------------------------------------------------------
if (!empty($serverName)) {
    try {
        $serverConfig = $module->getServerConfig($serverName);
    } catch (Exception $exception) {
        # The server could not be found, but the most likely cause is
        # that it was deleted so don't indicate an error
        $serverConfig = null;
        $serverName = '';
        $_SESSION['serverName'] = '';
    }
}


$testOutput = '';

$accessSet = Filter::sanitizeString($_POST['accessLevel']);
#$previousAccessLevel = $serverConfig->getAccessLevel();

#------------------------------------
# Router
#------------------------------------
if (strcasecmp($submit, 'Save') === 0) {
    if (empty($serverName)) {
        $error = 'ERROR: no server name specified.';
    } else {
        try {
            $serverConfig = new ServerConfig($serverName);
            $serverConfig->set(Filter::stripTagsArrayRecursive($_POST));
            $serverConfig->validate();
            $module->setServerConfig($serverConfig);
            $removeUserCheckbox = $_POST['removeUserCheckbox'];
            $removeUsernames = array();
            if (is_array($removeUserCheckbox) && !empty($removeUserCheckbox)) {
                foreach (array_keys($removeUserCheckbox) as $username) {
                    array_push($removeUsernames, $username);
                }
            }
            $module->processPrivateServerUsers($serverName, $removeUsernames);
            header('Location: ' . $serversUrl);
        } catch (Exception $exception) {
            $error = 'ERROR: ' . $exception->getMessage();
        }
    }
} elseif (strcasecmp($submit, 'Cancel') === 0) {
    header('Location: ' . $serversUrl);
} elseif (strcasecmp($submit, 'Test Server Connection') === 0) {
    if (!isset($serverConfig)) {
        $testOutput = 'ERROR: no server configuration found.';
    } else {
        $testOutput = $serverConfig->test();
    }
} elseif ($accessSet) {
    try {
        $serverConfig = new ServerConfig($serverName);
        $serverConfig->set(Filter::stripTagsArrayRecursive($_POST));
        $serverConfig->validate();
        $module->setServerConfig($serverConfig);

        # if the access-level was changed from private to something else,
        # then check to see if the user indicated to remove all of the
        # allowed-users for this server, for example, so that those users
        # can't have access again if the access-level for the server
        # should go back to private in the future
        if ($accessSet !== ServerConfig::ACCESS_LEVEL_PRIVATE) {
            # this request value is set in a javascript function based on what
            # the user clicks on a js confirm prompt.
            $deleteUsers = $_REQUEST["deletePrivateAccessUsers"] === 'true' ? true : false;
            if ($deleteUsers) {
                $removeUsernames = $module->getPrivateServerUsers($serverName);
                $module->processPrivateServerUsers($serverName, $removeUsernames);
            }
        }
    } catch (Exception $exception) {
        $error = 'ERROR: ' . $exception->getMessage();
    }
}
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
$buffer = str_replace('</head>', "    " . $link . "\n</head>", $buffer);
echo $buffer;
?>

<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png" alt="">REDCap-ETL Admin</h4>

<?php
$module->renderAdminPageContentHeader($serversUrl, $error, $warning, $success);
$module->renderAdminEtlServerSubTabs($selfUrl);
?>


<?php
#---------------------------------
# Server selection form
#---------------------------------
?>
<form name="selectionForm" id="selectionForm" action="<?php echo $selfUrl;?>" method="post"
      style="padding: 4px; margin-bottom: 12px; border: 1px solid #ccc; background-color: #ccc;">
    <span style="font-weight: bold;">Server:</span>
    <select name="serverName" onchange="this.form.submit()">
    <?php
    $values = $module->getServers();
    array_unshift($values, '');
    foreach ($values as $value) {
        if (strcmp($value, $serverName) === 0) {
            echo '<option value="' . Filter::escapeForHtmlAttribute($value)
                . '" selected>' . Filter::escapeForHtml($value) . "</option>\n";
        } else {
            echo '<option value="' . Filter::escapeForHtmlAttribute($value) . '">'
                . Filter::escapeForHtml($value) . "</option>\n";
        }
    }
    ?>
    </select>
    <?php Csrf::generateFormToken(); ?>
    <input type="hidden" name="redcap_csrf_token" value="<?php echo $module->getCsrfToken(); ?>"/>
</form>


<script>
$(document).ready(function() {

    $("#showPassword").change(function() {
        var newType = 'password';
        if ($(this).is(':checked')) {
            newType = 'text';
        }
        $("#password").each(function(){
            $("<input type='" + newType + "' style='margin: 4px;' >")
                .attr({ id: this.id, name: this.name, value: this.value, size: this.size})
                .insertBefore(this);
        }).remove();       
    })

    $("#showSshKeyPassword").change(function() {
        var newType = 'password';
        if ($(this).is(':checked')) {
            newType = 'text';
        }
        $("#sshKeyPassword").each(function(){
            $("<input type='" + newType + "' style='margin: 4px;' >")
                .attr({ id: this.id, name: this.name, value: this.value, size: this.size })
                .insertBefore(this);
        }).remove();       
    })

     $("input[name=authMethod]").change(function() {
        var value = $(this).val();
        if (value == 0) {
            $("#passwordRow").hide();
            $("#sshKeyFileRow").show();
            $("#sshKeyPasswordRow").show();
        } else if (value == 1) {
            $("#passwordRow").show();
            $("#sshKeyFileRow").hide();
            $("#sshKeyPasswordRow").hide();
        }
    });

    $('#data-load-options-help-link').click(function () {
        $('#data-load-options-help').dialog({dialogClass: 'redcap-etl-admin', width: 540, maxHeight: 440})
            .dialog('widget').position({my: 'left top', at: 'right+10 top+40', of: $(this)})
            ;
        return false;
    });

    //------------------------------------------------------------
    // Manage private users button click
    //------------------------------------------------------------
    $("#manage-private-access-users").click(function () {
        let url = '<?php echo $privateServerUsersUrl; ?>';
        let serverName = $(this).prop("name");

        dialog = $('#manage-private-users-dialog').dialog({
            buttons: [
                {
                    text: "Save",
                    id: "save-private-access",
                    click: function() {
                        let tbl = [];
                        // console.log("trs: ");
                        // console.log(trs);

                        trs = $('table#private-users-table tbody tr').get();
                        for (i = 0; i < trs.length; i++) {
                            td = $(trs[i]).find('td:first');
                            user = td.text();
                            tbl.push(user);
                        }

                        let userData = JSON.stringify(tbl);

                        let setUrl = '<?php echo $privateServerSetUsersUrl; ?>';
                        status = $.post(setUrl, {
                            server_name: serverName,
                            user_names: userData,
                            <?php echo Csrf::TOKEN_NAME; ?>: "<?php echo Csrf::getToken(); ?>",
                            <?php echo 'redcap_csrf_token: "' . $module->getCsrfToken()  . '"'; ?>
                        }, function(result){
                            if (result !== '') {
                                let messageDialog = '<div>' + result + '</div>';
                                $(messageDialog).dialog({
                                    title: 'Private Access Users for Server ' + serverName,
                                    dialogClass: 'redcap-etl-log',
                                    buttons: [
                                        {
                                            text: "OK",
                                            click: function() {
                                                $( this ).dialog( "destroy" );
                                            }
                                        }
                                    ],
                                    width: 540,
                                    maxHeight: 540
                                }).dialog('open')
                            }
                        }, 'text');


                        $( this ).dialog( "destroy" );
                    }
                },
                {
                    text: "Cancel",
                    click: function() {
                        $( this ).dialog( "destroy" );
                    }
                }
            ]
        });

        // Clear any existing rows from the table (otherwise they accumulate)
        $('table#private-users-table tbody').empty();
        $('#user-search').val('');

        status = $.post(url, {
                server_name: serverName,
                <?php echo Csrf::TOKEN_NAME; ?>: "<?php echo Csrf::getToken(); ?>",
                <?php echo 'redcap_csrf_token: "' . $module->getCsrfToken()  . '"'; ?>
            }, function(result){
                let values = jQuery.parseJSON(result);
                // console.log(result);
                // console.log(values);

                for (i = 0; i < values.length; i++) {
                    let row = '';
                    row += '<tr>';
                    row += '<td>' + (values[i]).username + '</td>';
                    row += '<td>' + (values[i]).firstname + ' ' + (values[i]).lastname + '</td>';
                    row += '<td>' + (values[i]).email + '</td>';
                    row += '<td style="text-align: center;">' + '<button>X</button>' + '</td>';
                    row += '</tr>';
                    $('table#private-users-table tbody').append(row);
                }
            }, 'text');


        dialog.dialog({
            title: 'Manage Private Access Users for Server ' + serverName,
            dialogClass: 'redcap-etl-admin',
            width: 540,
            height: 500,
            maxHeight: 640
        }).dialog('open')

        return false;
    });

    // Add functionality for delete buttons in private server users table
    $('table#private-users-table').on('click', 'button', function(e){
        $(this).closest('tr').remove()
    })

    //----------------------------------------------------------------
    // Add user to private server
    //----------------------------------------------------------------
    $('button#add-private-user').on('click', '', function(e){
        let userInfo = $('#user-search').val();
        $("#user-search").val('');

        let usernameAndRest = userInfo.split(" (");
        if (usernameAndRest.length != 2) {
            return false;
        }

        let username = usernameAndRest[0];
        let rest = usernameAndRest[1]
        let nameAndEmail = rest.split(") - ");
        let name = nameAndEmail[0];
        let email = nameAndEmail[1];


        // Get existing usernames
        tds = $('table#private-users-table tbody tr td:first-child');
        usernames = [];
        for (i = 0; i < tds.length; i++) {
            un = $(tds[i]).text();
            usernames.push(un);
        }
        console.log(usernames);

        // console.log(tr);
        if (usernames.includes(username)) {
            alert("User already added.");
            return false;
        }

        let row = '';
        row += '<tr>';
        row += '<td>' + username + '</td>';
        row += '<td>' + name + '</td>';
        row += '<td>' + email + '</td>';
        row += '<td style="text-align: center;">' + '<button>X</button>' + '</td>';
        row += '</tr>';
        $('table#private-users-table tbody').append(row);

        return false;
    })


    $("#user-search").autocomplete({
        source: "<?php echo $userSearchUrl;?>",
        appendTo: "#searchForm",
        minLength: 2,
        select: function(event, ui) {
            $("#user-search").val(ui.item.username);
            $("#username").val(ui.item.username);
            $("#userLabel").val(ui.item.label);
            // $("#searchForm").submit();
        }
    })
    .autocomplete("instance")._renderItem = function(ul, item) {
        var newLabel = item.label.replace(new RegExp(this.term, "gi"), "<span style=\"font-weight:bold;\">$&</span>");
        return $("<li>")
            .append("<div>" + newLabel + "</div>")
            .appendTo(ul);
    };


});

</script>

<!-- ========================================================================================
= Dialog for managing users of a private server
========================================================================================= -->
<div id="manage-private-users-dialog"
     style="display: none; padding: 10px; margin: 4px; background-color: #eafeea; border: 1px solid green;">
    <form id="searchForm" action="<?php echo $selfUrl;?>" method="post" style="margin-bottom: 22px;">
       <span style="font-weight: bold;">Username:</span>
       <input id="user-search" name="user-search" type="text" size="48"/>
       <button id="add-private-user">Add</button>

        <input type="hidden" name="username" id="username">
        <input type="hidden" name="userLabel" id="userLabel">

        <?php Csrf::generateFormToken(); ?>
        <input type="hidden" name="redcap_csrf_token" value="<?php echo $module->getCsrfToken(); ?>"/>
    </form>

    <p style="font-weight: bold;">Users with access to server <?php echo Filter::escapeForHtml($serverName); ?></p>
    <table id="private-users-table" class="cron-schedule">
        <thead>
            <tr><th>Username</th><th>Name</th><th>E-mail</th><th>Delete</th></tr>
        </thead>
        <tbody>
        </tbody>
    </table>

    <div style="margin-bottom: 4em;">&nbsp;</div>
</div>



<?php
#----------------------------------------------------
# Server configuration form
#----------------------------------------------------
if (!empty($serverName)) {
    $authMethod = $serverConfig->getAuthMethod();
    $isActive   = $serverConfig->getIsActive();
    $activeChecked = '';
    if ($isActive) {
        $activeChecked = ' checked ';
    }
    $accessLevels = ServerConfig::ACCESS_LEVELS;
    $accessLevel = $serverConfig->getAccessLevel();
    $access = $accessLevel;
    if (empty($accessLevel)) {
        $access = ServerConfig::ACCESS_LEVEL_PUBLIC;
    }
    if ($access === ServerConfig::ACCESS_LEVEL_PRIVATE) {
        $privateUsers = $module->getPrivateServerUsers($serverName);
        sort($privateUsers);
    }
    ?>

<script>
$(document).ready(function(){
    $("#delete-userlist-dialog").dialog({
            autoOpen : false,
            resizable : false,
            modal : true,
            height: 220,
            width: 400,
            title: 'Delete Private-Access User List',
            buttons: [{
                text: 'Delete list',
                icons: {
                           primary: "ui-icon-check"
                       },
                click: function() {
                           var deleteUsers =
                               document.getElementById("deletePrivateAccessUsers");
                           deleteUsers.value = true;
                           /*submit form */
                           scFormId.submit();
                       }},{
                text: 'Save list',
        icons: {
               primary: "ui-icon-cancel"
               },
                click: function() {
                           /*submit form */
                           scFormId.submit();
                       }
            }]
        });
});

$(function() {
     $("#accessLevelId").change(function() {
         var newLevel = $(this).val();
         var previousLevel = '<?php echo($accessLevel); ?>';

         if (newLevel !== 'private' && previousLevel === 'private') {
             var privateUsernames = '<?php echo json_encode($privateUsers); ?>';
             if (privateUsernames !== '[]' && privateUsernames !== 'null') {
                $("#delete-userlist-dialog").dialog("open");
             } else {
                //submit form
                 scFormId.submit();
             }
         } else {
             //submit form
             scFormId.submit();
         }
     });
});
</script>


<form name="scForm" id="scFormId" action=<?php echo $selfUrl;?> method="post">
  <input type="hidden" name="serverName"
      value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getName());?>">
 
  <!-- ACTIVE SETTING -->
  <table style="margin-bottom: 12px;">
    <tr>
      <td style="font-weight: bold; padding-right: 6px;">Active:</td>
      <td><input type="checkbox" id="isActive" name="isActive" value="checked" <?php echo $activeChecked; ?> ></td>
    </tr>
  </table>
  
  
  <!-- ACCESS LEVEL SETTING -->
  <fieldset class="server-config">
     <legend>Access Level Settings</legend>
     <table> 
        <tr>
            <!--<select onchange="scFormId.submit()" name="accessLevel" id="accessLevelId"> -->
            <select name="accessLevel" id="accessLevelId">
                <?php
                foreach ($accessLevels as $value) {
                    if (strcmp($value, $access) === 0) {
                        echo '<option value="' . $value . '" selected>' . $value . "</option>\n";
                    } else {
                        echo '<option value="' . $value . '">' . $value . "</option>\n";
                    }
                }
                ?>
           </select>
           <br /> &nbsp;
        </tr>

        <?php
           $usersRowStyle = '';
           $privateUsersStyle = '';
        if ($accessLevel != 'private') {
            $usersRowStyle = ' style="display: none;" ';
        } else {
            if (!$privateUsers) {
                $privateUsersStyle = ' style="display: none;" ';
            }
        }
        ?>
        
        <tr> 
           <!-- PRIVATE ACCESS LEVEL -->
           <div id="usersRow" name="usersRow" <?php echo $usersRowStyle; ?>>
              <!-- <legend>Users Currently Granted Access</legend> -->
              <div id="privateUsers" name="privateUsers" <?php echo $privateUsersStyle; ?>>
                 <!--
                 Remove<br />
                 -->
                    <?php
                    # $userList = implode(", ", $privateUsers);
                    # echo Filter::escapeForHtml($userList);
                    #oreach ($privateUsers as $username) {
                        #echo '<input type="checkbox" name="removeUserCheckbox['
                        #   . Filter::escapeForHtmlAttribute($username) . ']" '
                        #   . 'style="vertical-align: middle; margin: 0px 10px 0px 25px;"'  . ">\n";
                        #cho '<label for="removeUserCheckbox[' . Filter::escapeForHtmlAttribute($username) . ']">'
                        #   . Filter::escapeForHtml($username) . "</label>\n<br />";
                    #
                    ?>
              </div>

              <!--
              <div>
                 <a href='<?php # echo $configureUserUrl ?>'>Add User Access</a>
                 <br />
              </div>
              -->

              <div>
                  <button name="<?php echo Filter::escapeforHtmlAttribute($serverName); ?>"
                          id="manage-private-access-users">
                      Manage Private Access Users
                  </button>
              </div>

             <input type="hidden" id="deletePrivateAccessUsers" name="deletePrivateAccessUsers" />

           </div>
        </tr>
     </table>
  </fieldset> 

    <!-- RUN SETTINGS -->
    <fieldset class="server-config">
        <legend>Run Settings</legend>

        <?php
        $checked = '';
        if ($serverConfig->getAllowOnDemandRun()) {
            $checked = ' checked';
        }
        ?>
        <input type="checkbox" name="allowOnDemandRun" <?php echo $checked; ?>/>
        Allow ETL processes to be run interactively?

        <br/>

        <?php
        $checked = '';
        if ($serverConfig->getAllowCronRun()) {
            $checked = ' checked';
        }
        ?>
        <input type="checkbox" name="allowCronRun" <?php echo $checked; ?>/>
        Allow user scheduled ETL cron jobs?

    </fieldset>

    <!-- SERVER CONNECTION SETTINGS -->
    <?php
    if (strcasecmp($serverName, ServerConfig::EMBEDDED_SERVER_NAME) !== 0) {
        # REMOTE SERVER
        ?>
        <fieldset class="server-config">
            <legend>Server Connection Settings</legend>
            <table>  
                <tr>
                    <td>Server address:</td>
                    <td><input type="text" name="serverAddress"
                        value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getServerAddress());?>"
                        size="60" style="margin: 4px;"></td>
                </tr>
    
                <tr>
                    <td style="padding-top: 4px; padding-bottom: 4px; vertical-align: top;">Authentication method:</td>
                    <td style="padding: 4px;">
                        <input type="radio" name="authMethod" id="authMethodSshKey"
                            value="<?php echo ServerConfig::AUTH_METHOD_SSH_KEY;?>"
                        <?php
                        if ($authMethod == ServerConfig::AUTH_METHOD_SSH_KEY) {
                            echo ' checked ';
                        }
                        ?>
                        style="vertical-align: middle; margin: 0;">
                        <span style="vertical-align: top; margin-right: 8px;">SSH Key</span>
                        <input type="radio" name="authMethod" id="authMethodPassword"
                            value="<?php echo ServerConfig::AUTH_METHOD_PASSWORD;?>"
                        <?php
                        if ($authMethod == ServerConfig::AUTH_METHOD_PASSWORD) {
                            echo ' checked ';
                        }
                        ?>
                        style="vertical-align: middle; margin: 0;">
                        <span style="vertical-align: top; margin-right: 8px;">Password</span>
                    </td>
                </tr>
    
                <tr>
                    <td>Username:</td>
                    <td><input type="text" name="username"
                        value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getUsername());?>"
                        size="28" style="margin: 4px;"></td>
                </tr>
    
                    <?php
                    $passwordStyle = '';
                    $sshStyle = '';
                    if ($authMethod == ServerConfig::AUTH_METHOD_PASSWORD) {
                        $sshStyle = ' style="display: none;" ';
                    } else {
                        $passwordStyle = ' style="display: none;" ';
                    }
                    ?>
    
                <tr id="passwordRow" <?php echo $passwordStyle; ?> >
                    <td>Password:</td>
                    <td>
                        <input type="password" name="password"
                            value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getPassword());?>"
                            size="28" style="margin: 4px;" id="password" autocomplete="off">
                        <input type="checkbox" id="showPassword" style="vertical-align: middle; margin: 0;">
                        <span style="vertical-align: middle;">Show</span>
                    </td>
                </tr>

      <tr id="sshKeyFileRow" <?php echo $sshStyle; ?> >
        <td>SSH key file:</td>
        <td><input type="text" name="sshKeyFile"
            value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getSshKeyFile());?>"
            size="44" style="margin: 4px;" autocomplete="off"></td>
      </tr>
      <tr id="sshKeyPasswordRow" <?php echo $sshStyle; ?> >
        <td>SSH key password:</td>
        <td>
          <input type="password" name="sshKeyPassword"
              value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getSshKeyPassword());?>"
              size="44" style="margin: 4px;" id="sshKeyPassword" autocomplete="off">
          <input type="checkbox" id="showSshKeyPassword" style="vertical-align: middle; margin: 0;">
          <span style="vertical-align: middle;">Show</span>
        </td>
      </tr>
        
    </table>
    </fieldset>
  
    <!-- SERVER COMMAND SETTINGS -->
    <fieldset class="server-config">
    <legend>Server Command Settings</legend>
    <table>    
      <tr>
        <td>Configuration directory:</td>
        <td><input type="text" name="configDir"
            value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getConfigDir());?>"
            size="60" style="margin: 4px;"></td>
      </tr>
      <tr>
        <td>ETL command prefix:</td>
        <td><input type="text" name="etlCommandPrefix"
            value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getEtlCommandPrefix());?>"
            size="60" style="margin: 4px;"></td>
      </tr>    
      <tr>
        <td>ETL command:</td>
        <td>
          <input type="text" name="etlCommand"
              value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getEtlCommand());?>"
              size="60" style="margin: 4px;">
        </td>
      </tr>
      <tr>
        <td>ETL command suffix:</td>
        <td><input type="text" name="etlCommandSuffix"
            value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getEtlCommandSuffix());?>"
            size="60" style="margin: 4px;"></td>
      </tr>
    </table>
    </fieldset>


        <?php
    } else {
        # EMBEDDED SERVER
        $maxZipDownloadSize = $serverConfig->getMaxZipDownloadFileSize();
        if (is_null($maxZipDownloadSize) || $maxZipDownloadSize === '') {
            $maxZipDownloadSize = DataTarget::DEFAULT_MAX_ZIP_DOWNLOAD_FILESIZE;
        }
        ?>
            <!-- DATA LOAD SETTING -->
            <fieldset class="server-config">
            <legend>Data Load Options</legend>

                <!-- CSV ZIP file and Databas download -->
                <input type="radio" name="dataLoadOptions"
                    value="<?php echo ServerConfig::DATA_LOAD_DB_AND_FILE;?>"
                <?php
                if ($serverConfig->getDataLoadOptions() == ServerConfig::DATA_LOAD_DB_AND_FILE) {
                    echo ' checked ';
                }
                ?>
                style="vertical-align: middle; margin: 0;">
                <span style="vertical-align: top; margin-right: 8px;">CSV ZIP file and Database</span>

                <!-- Database only -->
                <input type="radio" name="dataLoadOptions"
                    value="<?php echo ServerConfig::DATA_LOAD_DB_ONLY;?>"
                <?php
                if ($serverConfig->getDataLoadOptions() == ServerConfig::DATA_LOAD_DB_ONLY) {
                    echo ' checked ';
                }
                ?>
                style="vertical-align: middle; margin: 0;">
                <span style="vertical-align: top; margin-right: 8px;">Database only</span>


                <!-- CSV ZIP file download only -->
                <input type="radio" name="dataLoadOptions"
                    value="<?php echo ServerConfig::DATA_LOAD_FILE_ONLY;?>"
                <?php
                if ($serverConfig->getDataLoadOptions() == ServerConfig::DATA_LOAD_FILE_ONLY) {
                    echo ' checked ';
                }
                ?>
                style="vertical-align: middle; margin: 0;">
                <span style="vertical-align: top; margin-right: 8px;">CSV ZIP file only</span>

                <!-- HELP -->
                <div style="float: right;">
                    <a href="#" id="data-load-options-help-link" class="etl-help" title="help">?</a>
                    <div id="data-load-options-help" title="Data Load Options" style="display: none;">
                        <?php echo Help::getHelpWithPageLink('data-load-options', $module); ?>
                    </div>
                </div>

                <div style="clear: both;"></div>

            </fieldset>

            <!-- CSV ZIP DOWNLOAD SETTING -->
            <fieldset class="server-config">
            <legend>CSV ZIP Download</legend>
            <table>
              <tr>
                <td>Max file size (MB):</td>
                <td><input type="text" name="maxZipDownloadFileSize" id="maxZipDownloadFileSize"
                     value="<?php echo Filter::escapeForHtmlAttribute($maxZipDownloadSize);?>" size="60">
                </td>
              </tr>
            </table>
            </fieldset>
            <?php
    } // end if not embedded server
    ?>
  
  <!-- SERVER LOGGING SETTINGS -->
  <fieldset class="server-config">
  <legend>Server Logging Settings</legend>
  <table>
    <tr>
      <td>Log file:</td>
      <td><input type="text" name="logFile"
          value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getLogFile());?>" size="60">
      </td>
    </tr>
  </table>
  </fieldset>
  
  
  <!-- SERVER E-MAIL SETTINGS -->
  <fieldset class="server-config">
  <legend>Server E-mail Settings</legend>
  <table>        
    <tr>
      <td>E-mail from address:</td>
      <td><input type="text" name="emailFromAddress"
          value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getEmailFromAddress());?>"
          size="40" style="margin: 4px;"></td>
    </tr>
    
    <tr>
      <td>Enable error e-mail:</td>
      <td>
        <?php
        $checked = '';
        if ($serverConfig->getEnableErrorEmail()) {
            $checked = ' checked ';
        }
        ?>
        <input type="checkbox" name="enableErrorEmail" value="true" <?php echo $checked; ?> >
      </td>
    </tr>
    
    <tr>
      <td>Enable summary e-mail:&nbsp;</td>
      <td>
        <?php
        $checked = '';
        if ($serverConfig->getEnableSummaryEmail()) {
            $checked = ' checked ';
        }
        ?>
        <input type="checkbox" name="enableSummaryEmail" value="true" <?php echo $checked; ?> >
      </td>
    </tr>   
       
  </table>
  </fieldset>

  
  <fieldset class="server-config">
  <legend>Database Connection SSL Settings</legend>
  <table>
    <tr>
      <td>Database SSL:</td>
      <td>
        <?php
        $checked = '';
        if ($serverConfig->getDbSsl()) {
            $checked = ' checked ';
        }
        ?>
        <input type="checkbox" name="dbSsl" value="true" <?php echo $checked; ?> >
      </td>
    </tr>
    
    <tr>
      <td style="margin-right: 1em;">Database SSL verification:&nbsp;</td>
      <td>
        <?php
        $checked = '';
        if ($serverConfig->getDbSslVerify()) {
            $checked = ' checked ';
        }
        ?>
        <input type="checkbox" name="dbSslVerify" value="true" <?php echo $checked; ?> >
      </td>
    </tr>
    
    <tr>
      <td style="margin-right: 1em;">CA certificate file:</td>
      <td><input type="text" name="caCertFile"
          value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getCaCertFile());?>" size="60">
      </td>
    </tr>
        
  </table>
  </fieldset>
  
  
  <div style="margin-top: 20px;">
    <div style="width: 50%; float: left;">
      <input type="submit" name="submitValue" value="Save" class="etl-submit" style="margin: auto; display: block;">
    </div>
    <div style="width: 50%; float: right;">
      <input type="submit" name="submitValue" value="Cancel" class="etl-submit" style="margin: auto; display: block;">
    </div>
    <div style="clear: both;">
    </div>
  </div>
  <div style="margin-top: 4ex;">
    <input type="submit" name="submitValue" value="Test Server Connection"> <br/>
    <textarea id="testOutput" name="testOutput" rows="4" cols="40"><?php
        echo Filter::escapeForHtml($testOutput);
    ?>&nbsp;
    </textarea>
  </div>
    <?php Csrf::generateFormToken(); ?>
    <input type="hidden" name="redcap_csrf_token" value="<?php echo $module->getCsrfToken(); ?>"/>
</form>
    <?php
}
?>


<?php
#--------------------------------------
# Delete user list dialog
#--------------------------------------
?>
<div id="delete-userlist-dialog" style="display: none;">
    Do you want to delete the list of allowed users for the private-level access? 
</div>


<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

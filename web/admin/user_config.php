<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

#---------------------------------------------
# Check that the user has access permission
#---------------------------------------------
$module->checkAdminPagePermission();


require_once __DIR__.'/../../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

$success = '';
$warning = '';
$error   = '';

$deleteButtonLabel = 'Delete User from REDCap-ETL';

$selfUrl       = $module->getUrl(RedCapEtlModule::USER_CONFIG_PAGE);
$userSearchUrl = $module->getUrl('web/admin/user_search.php');
$adminUrl      = $module->getUrl(RedCapEtlModule::ADMIN_HOME_PAGE);

$adminConfigJson = $module->getSystemSetting(AdminConfig::KEY);
$adminConfig = new AdminConfig();
        
$submitValue = $_POST['submitValue'];

$username = $_POST['username-result'];
if (empty($username)) {
    $username = $_GET['username'];
}

$userLabel = $_POST['userLabel'];

try {
    if (!empty($username)) {
        if (strcmp($submitValue, $deleteButtonLabel) === 0) {
            $module->deleteUser($username);
            $success = 'User "'.$username.'" deleted from REDCap-ETL.';
            $urlValue = RedCapEtlModule::USERS_PAGE.'?success='.Filter::escapeForUrlParameter($success);
            $usersUrl = $module->getUrl($urlValue);
            header('Location: '.$usersUrl);
        } else {
            if (strcasecmp($submitValue, 'Save') === 0) {
                $checkbox = $_POST['checkbox'];
                $userEtlProjects = array();
                foreach (array_keys($checkbox) as $projectId) {
                    array_push($userEtlProjects, $projectId);
                }
                $module->addUser($username);
                $module->setUserEtlProjects($username, $userEtlProjects);
                $success = 'User '.$username.' saved.';
                $url = $module->getUrl(RedCapEtlModule::USERS_PAGE.'?success='.Filter::escapeForUrlParameter($success));
                header('Location: '.$url);
            }
            $db = new RedCapDb();
            $userProjects = $db->getUserProjects($username);
            $userEtlProjects = $module->getUserEtlProjects($username);
        }
    }
} catch (Exception $exception) {
    $error = $exception->getMessage();
}


#---------------------------------------------
# Include REDCap's Control Center page header
#---------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
?>


<?php
#print "SUBMIT = {$submitValue} <br/> \n";
#print "username = {$username} <br/> \n";
#print "usersUrl = {$usersUrl} <br/> \n";
#print "<pre><br />\n"; print_r($_POST); print "</pre> <br/> \n";
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>

<?php

$module->renderAdminPageContentHeader($selfUrl, $error, $warning, $success);

?>


<form id="searchForm" action="<?php echo $selfUrl;?>" method="post">
User:
<input type="text" id="user-search" name="user-search" size="48" 
     value="<?php echo Filter::escapeForHtmlAttribute($username); ?>">
<input type="hidden" name="username-result" id="username-result">
<input type="hidden" name="userLabel" id="userLabel">
<?php Csrf::generateFormToken(); ?>
</form>
    <!--
<div class="ui-widget">
  <label for="user">User: </label>
  <input type="text" id="user-search" size="40">
</div>
-->

<script>
$(function() {
    $("#user-search").autocomplete({
        source: "<?php echo $userSearchUrl;?>",
        minLength: 2,
        select: function(event, ui) {
            $("#user-search").val(ui.item.username);
            $("#username-result").val(ui.item.username);
            $("#userLabel").val(ui.item.label);
            $("#searchForm").submit();
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


<?php
if (!empty($username)) {
    echo "<p>Projects for user ".Filter::escapeForHtml($userLabel)."</p>\n";
?>

<?php
#--------------------------------------
# Delete User from REDCap-ETL dialog
#--------------------------------------
?>
<div id="deleteDialog"
    title="<?php echo $deleteButtonLabel;?>"
    style="display: none;"
    >
    <form id="deleteUserForm" action="<?php echo $selfUrl;?>" method="post">
    <p>To delete user &quot;<?php echo $username;?>&quot; from the REDCap-ETL users,
    click on the <span style="font-weight: bold;"><?php echo $deleteButtonLabel;?></span> button.
    </p>
    <p>This action will NOT delete the user from REDCap and will NOT delete any
    of the REDCap-ETL configurations this user has created or edited.
    </p>
    <input type="hidden" name="username-result" value="<?php echo $username;?>">
    <input type="hidden" name="submitValue" value="<?php echo $deleteButtonLabel;?>">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>

<script>
$(function() {
    // Delete user from REDCap-ETL form
    deleteUserForm = $("#deleteUserForm").dialog({
        autoOpen: false,
        height: 240,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "<?php echo $deleteButtonLabel;?>": function() {deleteUserForm.submit();}
        },
        title: "<?php echo $deleteButtonLabel;?>"
    });

    function deleteUser(event) {
        var username = event.data.username;
        $("#deleteUserForm").data('username', username).dialog("open");
    }    

    $("#deleteUser").click({username: "<?php echo Filter::escapeForJavaScriptInDoubleQuotes($username);?>"},
        deleteUser);
});

</script>


<?php
#-----------------------------------------------
# User configuration form
#-----------------------------------------------
?>
<form action="<?php echo $selfUrl;?>" method="post">
<input type="hidden" name="username-result" value="<?php echo Filter::escapeForHtmlAttribute($username);?>">
<table class="user-projects">
    <thead>
        <tr> <th>ETL Access?</th> </th><th>PID</th> <th>Project</th> <th>ETL Configurations</th> </tr>
    </thead>
    <tbody>
        <?php
        $row = 1;
        foreach ($userProjects as $project) {
            $projectId = $project['project_id'];
            
            $configNames = $module->getConfigurationNames($projectId);
            
            if ($row % 2 == 0) {
                echo '<tr class="even-row">'."\n";
            } else {
                echo '<tr class="odd-row">'."\n";
            }
            
            $checked = '';
            if (!empty($userEtlProjects) && in_array($projectId, $userEtlProjects)) {
                $checked = ' checked ';
            }
            echo '<td style="text-align: center;"><input type="checkbox" name="checkbox['.(int)$projectId.']" '
                .$checked.'></td>'."\n";
            echo '<td style="text-align: right">'.(int)$projectId."</td>\n";
            
            # Project title
            echo "<td>\n";
            echo '<a href="'.APP_PATH_WEBROOT.'index.php?pid='
                .Filter::escapeForUrlParameter($project['project_id']).'" target="_blank">'
                .Filter::escapeForHtml($project['app_title'])."</a>\n";
            echo "</td>\n";
            
            echo "<td>\n";
            $isFirst = true;
            foreach ($configNames as $configName) {
                $configUrl = $module->getURL(
                    RedCapEtlModule::USER_ETL_CONFIG_PAGE
                    .'?pid='.Filter::escapeForUrlParameter($projectId)
                    .'&configName='.Filter::escapeForUrlParameter($configName)
                    #.'&username='.Filter::escapeForUrlParameter($username)
                );
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    echo ", ";
                }
                echo '<a href="'.$configUrl.'">'.Filter::escapeForHtml($configName)."</a>\n";
            }
            echo "\n";
            echo "</td>\n";
            
            echo "</tr>\n";
            $row++;
        }
        ?>
    </tbody>
</table>
<hr />
<p>
<input type="submit" name="submitValue" value="Save">
<input type="button" name="submitValue" value="<?php echo $deleteButtonLabel;?>" id="deleteUser"
    style="float: right;">
<div style="clear: both;"></div>
</p>
<?php Csrf::generateFormToken(); ?>
</form>
<?php
}
?>

<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

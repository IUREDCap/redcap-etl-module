<?php

if (!SUPER_USER) {
    exit("Only super users can access this page!");
}

require_once __DIR__.'/../../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

$module = new RedCapEtlModule();
$selfUrl = $module->getUrl(RedCapEtlModule::USER_CONFIG_PAGE);
$userSearchUrl = $module->getUrl('web/admin/user_search.php');
$adminUrl = $module->getURL(RedCapEtlModule::ADMIN_HOME_PAGE);

$adminConfigJson = $module->getSystemSetting(AdminConfig::KEY);
$adminConfig = new AdminConfig();


$submitValue = $_POST['submitValue'];

$username = $_POST['username-result'];
if (empty($username)) {
    $username = $_GET['username'];
}

$userLabel = $_POST['userLabel'];

if (!empty($username)) {
    #if (strcasecmp($submitValue, 'Add User') === 0) {
    #    $module->addUser($username);
    if (strcasecmp($submitValue, 'Save') === 0) {
        $checkbox = $_POST['checkbox'];
        $userEtlProjects = array();
        foreach (array_keys($checkbox) as $projectId) {
            array_push($userEtlProjects, $projectId);
        }
        $module->addUser($username);
        $module->setUserEtlProjects($username, $userEtlProjects);
        header('Location: '.$adminUrl);
    }
    $db = new RedCapDb();
    $userProjects = $db->getUserProjects($username);
    $userEtlProjects = $module->getUserEtlProjects($username);
}

#---------------------------------------------
# Include REDCap's Control Center page header
#---------------------------------------------
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
#$users = $module->getUsers();
#print "Users: <pre><br />\n"; print_r($users); print "</pre> <br/> \n";
#print "<pre><br />\n"; print_r($_POST); print "</pre> <br/> \n";
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>

<?php $module->renderAdminTabs($selfUrl); ?>

<?php #echo "user-search: ".$_POST['user-search']."<br/>\n"; ?>
<?php #echo "user label: ".$_POST['userLabel']."<br/>\n"; ?>
<?php #echo "username-result: ".$_POST['username-result']."<br/>\n"; ?>
<?php # print "<pre>"; print_r($userProjects); print "</pre>"; ?>

<form id="searchForm" action="<?php echo $selfUrl;?>" method="post">
User: <input type="text" id="user-search" name="user-search" size="48" value="<?php echo $username; ?>">
<!-- <input type="submit" name="submitValue" value="Add User"><br /> -->
<input type="hidden" name="username-result" id="username-result">
<input type="hidden" name="userLabel" id="userLabel">
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
    echo "<p>Projects for user {$userLabel}</p>\n";
?>
<form action="<?php echo $selfUrl;?>" method="post">
<input type="hidden" name="username-result" value="<?php echo $username;?>">
<table class="user-projects">
    <thead>
        <tr> <th>ETL Access?</th> </th><th>PID</th> <th>Project</th> <th>ETL Configurations</th> </tr>
    </thead>
    <tbody>
        <?php
        $row = 1;
        foreach ($userProjects as $project) {
            $projectId = $project['project_id'];
            
            $configNames = $module->getUserConfigurationNames($username, $projectId);
            
            if ($row % 2 == 0) {
                echo '<tr class="even-row">'."\n";
            } else {
                echo '<tr class="odd-row">'."\n";
            }
            
            $checked = '';
            if (!empty($userEtlProjects) && in_array($projectId, $userEtlProjects)) {
                $checked = ' checked ';
            }
            echo '<td style="text-align: center;"><input type="checkbox" name="checkbox['.$projectId.']" '
                .$checked.'></td>'."\n";
            echo '<td style="text-align: right">'.$projectId."</td>\n";
            
            # Project title
            echo "<td>\n";
            echo '<a href="'.APP_PATH_WEBROOT.'index.php?pid='.$project['project_id'].'" target="_blank">'
                .$project['app_title']."</a>\n";
            echo "</td>\n";
            
            echo "<td>\n";
            $isFirst = true;
            foreach ($configNames as $configName) {
                $configUrl = $module->getURL(
                    RedCapEtlModule::ADMIN_ETL_CONFIG_PAGE.'?config='.$configName
                    .'&username='.$username.'&pid='.$projectId
                );
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    echo ", ";
                }
                echo '<a href="'.$configUrl.'">'.$configName."</a>\n";
            }
            echo "\n";
            echo "</td>\n";
            
            echo "</tr>\n";
            $row++;
        }
        ?>
    </tbody>
</table>
<p>
<input type="submit" name="submitValue" value="Save">
</p>
</form>
<?php
}
?>

<!--
<h5 style="margin-top: 2em;">REDCap-ETL Users</h5>
<table class="dataTable">
    <thead>
        <tr> <th>username</th> </tr>
    </thead>
    <tbody>
    <?php
    $row = 1;
    foreach ($users as $user) {
        if ($row % 2 == 0) {
            echo "<tr class=\"even\"><td>{$user}</td></tr>\n";
        } else {
            echo "<tr class=\"odd\"><td>{$user}</td></tr>\n";
        }
        $row++;
    }
    ?>
    </tbody>
</table>
-->

<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

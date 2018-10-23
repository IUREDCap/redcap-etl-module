<?php

if (!SUPER_USER) {
    exit("Only super users can access this page!");
}

require_once __DIR__.'/dependencies/autoload.php';

#require_once __DIR__.'/AdminConfig.php';
#require_once __DIR__.'/RedCapDb.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\RedCapDb;

$module = new \IU\RedCapEtlModule\RedCapEtlModule();
$selfUrl = $module->getUrl(basename(__FILE__));
$userSearchUrl = $module->getUrl('user_search.php');

$adminConfigJson = $module->getSystemSetting(AdminConfig::KEY);
$adminConfig = new AdminConfig();


$submit = $_POST['submit'];

$username = $_POST['username-result'];
$userLabel = $_POST['userLabel'];
if (!empty($username)) {
    if (strcasecmp($submit, 'Add User') === 0) {
        $module->addUser($username);
    }
    $db = new RedCapDb();
    $userProjects = $db->getUserProjects($username);
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
#$users = $module->getUsers();
#print "Users: <pre><br />\n"; print_r($users); print "</pre> <br/> \n";
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>

<?php $module->renderAdminTabs($selfUrl); ?>

<?php #echo "user-search: ".$_POST['user-search']."<br/>\n"; ?>
<?php #echo "user label: ".$_POST['userLabel']."<br/>\n"; ?>
<?php #echo "username-result: ".$_POST['username-result']."<br/>\n"; ?>
<?php # print "<pre>"; print_r($userProjects); print "</pre>"; ?>

<form id="searchForm" action="<?php echo $selfUrl;?>" method="post">
User: <input type="text" id="user-search" name="user-search" size="48" value="<?php echo $username; ?>">
<input type="submit" name="submitValue" value="Add User"><br />
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
<table class="user-projects">
    <thead>
        <tr> <th>ETL Access?</th> </th><th>ID</th> <th>Name</th> </tr>
    </thead>
    <tbody>
        <?php
        $row = 1;
        foreach ($userProjects as $project) {
            if ($row % 2 == 0) {
                echo '<tr class="even-row">'."\n";
            } else {
                echo '<tr class="odd-row">'."\n";
            }
            echo '<td style="text-align: center;"><input type="checkbox"></td>'."\n";
            echo '<td style="text-align: right">'.$project['project_id']."</td>\n";
            
            # Project title
            echo "<td>\n";
            echo '<a href="'.APP_PATH_WEBROOT.'index.php?pid='.$project['project_id'].'" target="_blank">'.$project['app_title']."</a>\n";
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

<?php

if (!SUPER_USER) exit("Only super users can access this page!");

require_once __DIR__.'/AdminConfig.php';
require_once __DIR__.'/RedCapDb.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\RedCapDb;

$module = new \IU\RedCapEtlModule\RedCapEtlModule();
$selfUrl = $module->getUrl(basename(__FILE__));
$userSearchUrl = $module->getUrl('user_search.php');

$adminConfigJson = $module->getSystemSetting(AdminConfig::KEY);
$adminConfig = new AdminConfig();


$submit = $_POST['submit'];

$username = $_POST['username-result'];
if (!empty($username)) {
    if (strcasecmp($submit, 'Add User') === 0) {
        $module->addUser($username);
    }
#    $db = new RedCapDb();
#    $userInfo = $db->getUserInfo($username);
}

?>

<?php include APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>

<?php
#print "SUBMIT = {$submit} <br/> \n";
$users = $module->getUsers();
#print "Users: <pre><br />\n"; print_r($users); print "</pre> <br/> \n";
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>

<?php $module->renderAdminTabs($selfUrl); ?>

<?php # echo "user-search: ".$_POST['user-search']."<br/>\n"; ?>
<?php # echo "username-result: ".$_POST['username-result']."<br/>\n"; ?>
<?php # print "<pre>"; print_r($userInfo); print "</pre>"; ?>

<form action="<?php echo $selfUrl;?>" method="post">
User: <input type="text" id="user-search" name="user-search" size="48">
<input type="submit" name="submit" value="Add User"><br />
<input type="hidden" name="username-result" id="username-result">
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
            return false;
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


<h5 style="margin-top: 2em;">REDCap-ETL Users</h5>
<table class="dataTable">
  <thead>
    <tr> <th>username</th> </tr>
  </thead>
  <tbody>
    <?php
    foreach ($users as $user) {
      echo "<tr><td>{$user}</td></tr>\n";
    }
    ?>
  </tbody>
</table>

<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

<?php

if (!SUPER_USER) exit("Only super users can access this page!");

require_once __DIR__.'/AdminConfig.php';

use IU\RedCapEtlModule\AdminConfig;

$module = new \IU\RedCapEtlModule\RedCapEtlModule();
$selfUrl = $module->getUrl(basename(__FILE__));
$userSearchUrl = $module->getUrl('user_search.php');

$adminConfigJson = $module->getSystemSetting(AdminConfig::KEY);
$adminConfig = new AdminConfig();

?>

<?php include APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>

<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>

<?php $module->renderAdminTabs($selfUrl); ?>

<?php # echo "user-search: ".$_POST['user-search']."<br/>\n"; ?>
<?php # echo "username-result: ".$_POST['username-result']."<br/>\n"; ?>

<form action="<?php echo $selfUrl;?>" method="post">
User: <input type="text" id="user-search" name="user-search" size="40"> <input type="submit" value="Add User"><br />
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

<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

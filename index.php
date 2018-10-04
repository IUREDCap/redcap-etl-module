<?php

require_once __DIR__.'/dependencies/autoload.php';

$module = new \IU\RedCapEtlModule\RedCapEtlModule();

$deleteConfigName = $_POST['deleteConfigName'];
if (!empty($deleteConfigName)) {
    $module->removeConfiguration($deleteConfigName);
}


# include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

#----------------------------------
# How to add to <head>
#----------------------------------
ob_start();
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$buffer = ob_get_clean();
$buffer = str_replace('</head>', "\n<!-- my comment -->\n</head>", $buffer);
echo $buffer;
?>

<div class="projhdr"> <!--h4 style="color:#800000;margin:0 0 10px;"> -->
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>
<!-- </h4> -->

<?php


$configurationNames = $module->getUserConfigurationNames();

$selfUrl   = $module->getUrl(basename(__FILE__));
$configUrl = $module->getUrl("configure.php");
$runUrl    = $module->getUrl("run.php");

?>

<?php $module->renderUserTabs($selfUrl); ?>


<table class="dataTable">
<thead>
<tr class="hrd"> <th>Configuration Name</th> <th>Configure</th> <th>Run</th> <th>Delete</th> </tr>
</thead>
<tbody>
<?php
    
$row = 1;
foreach ($configurationNames as $configurationName) {
    if ($row % 2 === 0) {
        print '<tr class="even">'."\n";
    } else {
        print '<tr class="odd">'."\n";
    }
    
    $configureUrl = $configUrl.'&configName='.$configurationName;
    $runConfigurationUrl = $runUrl.'&configName='.$configurationName;
    print "<td>{$configurationName}</td>\n";
    print '<td style="text-align:center;">'
        .'<a href="'.$configureUrl.'"><img src='.APP_PATH_IMAGES.'gear.png></a>'
        ."</td>\n";
    print '<td style="text-align:center;">'
        .'<a href="'.$runConfigurationUrl.'"><img src='.APP_PATH_IMAGES.'application_go.png></a>'
        ."</td>\n";
    print '<td style="text-align:center;">'
        .'<img src="'.APP_PATH_IMAGES.'delete.png" class="deleteConfig" id="delete'.$configurationName.'"/>'
        ."</td>\n";

    print "</tr>\n";
    $row++;
}

?>
</tbody>
</table>

<?php
#--------------------------------------
# Delete config dialog
#--------------------------------------
?>
<script>
$(function() {
    // Delete ETL configuration form
    deleteForm = $("#deleteForm").dialog({
        autoOpen: false,
        height: 170,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Delete configuration": function() {deleteForm.submit();}
        },
        title: "Delete configuration"
    });
    
    $(".deleteConfig").click(function(){
        var id = this.id;
        var server = id.substring(6);
        $("#configToDelete").text('"'+server+'"');
        $('#deleteConfigName').val(server);
        $("#deleteForm").data('server', server).dialog("open");
    });
});
</script>
<div id="deleteDialog"
    title="Configuration Delete"
    style="display: none;"
    >
    <form id="deleteForm" action="<?php echo $selfUrl;?>" method="post">
    To delete the ETL configuration <span id="configToDelete" style="font-weight: bold;"></span>,
    click on the <span style="font-weight: bold;">Delete configuration</span> button.
    <input type="hidden" name="deleteConfigName" id="deleteConfigName" value="">
    </form>
</div>



<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>



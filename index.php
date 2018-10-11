<?php

require_once __DIR__.'/dependencies/autoload.php';

$module = new \IU\RedCapEtlModule\RedCapEtlModule();

$copyFromConfigName = $_POST['copyFromConfigName'];
$copyToConfigName   = $_POST['copyToConfigName'];
if (!empty($copyFromConfigName) && !empty($copyToConfigName)) {
    try {
        $module->copyConfiguration($copyFromConfigName, $copyToConfigName);
    } catch (Exception $exception) {
        $error = 'ERROR: ' . $exception->getMessage();
    }
}


$renameConfigName    = $_POST['renameConfigName'];
$renameNewConfigName = $_POST['renameNewConfigName'];
if (!empty($renameConfigName) && !empty($renameNewConfigName)) {
    try {
        $module->renameConfiguration($renameConfigName, $renameNewConfigName);
    } catch (Exception $exception) {
        $error = 'ERROR: ' . $exception->getMessage();
    }
}

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

$adminConfig = $module->getAdminConfig();

$selfUrl     = $module->getUrl(basename(__FILE__));
$configUrl   = $module->getUrl("configure.php");
$scheduleUrl = $module->getUrl("schedule.php");
$runUrl      = $module->getUrl("run.php");

?>

<?php $module->renderUserTabs($selfUrl); ?>


<table class="dataTable">
<thead>
<tr class="hrd">
    <th>Configuration Name</th>
    <th>Configure</th>
    <?php
    
    if ($adminConfig->getAllowOnDemand()) {
        echo "<th>Run</th>\n";
    }

    if ($adminConfig->getAllowCron()) {
        echo "<th>Schedule</th>\n";
    }
    ?>
    <th>Copy</th>
    <th>Rename</th>
    <th>Delete</th>
</tr>
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
    $scheduleConfigUrl = $scheduleUrl.'&configName='.$configurationName;
    
    print "<td>{$configurationName}</td>\n";
    print '<td style="text-align:center;">'
        .'<a href="'.$configureUrl.'"><img src='.APP_PATH_IMAGES.'gear.png></a>'
        ."</td>\n";
        
    if ($adminConfig->getAllowOnDemand()) {
        print '<td style="text-align:center;">'
            .'<a href="'.$runConfigurationUrl.'"><img src='.APP_PATH_IMAGES.'application_go.png></a>'
            ."</td>\n";
    }

    if ($adminConfig->getAllowCron()) {
        print '<td style="text-align:center;">'
            .'<a href="'.$scheduleConfigUrl.'"><img src='.APP_PATH_IMAGES.'clock_frame.png></a>'
            ."</td>\n";
    }
        
    print '<td style="text-align:center;">'
        .'<img src="'.APP_PATH_IMAGES.'page_copy.png" class="copyConfig" style="cursor: pointer;"'
        .' id="copy'.$configurationName.'"/>'
        ."</td>\n";
    print '<td style="text-align:center;">'
        .'<img src="'.APP_PATH_IMAGES.'page_white_edit.png" class="renameConfig" style="cursor: pointer;"'
        .' id="rename'.$configurationName.'"/>'
        ."</td>\n";
    print '<td style="text-align:center;">'
        .'<img src="'.APP_PATH_IMAGES.'delete.png" class="deleteConfig" style="cursor: pointer;"'
        .' id="delete'.$configurationName.'"/>'
        ."</td>\n";

    print "</tr>\n";
    $row++;
}

?>
</tbody>
</table>



<?php
#--------------------------------------
# Copy config dialog
#--------------------------------------
?>
<script>
$(function() {
    copyForm = $("#copyForm").dialog({
        autoOpen: false,
        height: 200,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Copy configuration": function() {copyForm.submit(); $(this).dialog("close");}
        },
        title: "Copy configuration"
    });
    $(".copyConfig").click(function(){
        var id = this.id;
        var configName = id.substring(4);
        $("#configToCopy").text('"'+configName+'"');
        $('#copyFromConfigName').val(configName);
        $("#copyForm").dialog("open");
    });
});
</script>
<div id="copyDialog"
    title="Configuration Copy"
    style="display: none;"
    >
    <form id="copyForm" action="<?php echo $selfUrl;?>" method="post">
    To copy the configuration <span id="configToCopy" style="font-weight: bold;"></span>,
    enter the name of the new configuration below, and click on the
    <span style="font-weight: bold;">Copy configuration</span> button.
    <p>
    <span style="font-weight: bold;">New configuration name:</span>
    <input type="text" name="copyToConfigName" id="copyToConfigName">
    </p>
    <input type="hidden" name="copyFromConfigName" id="copyFromConfigName" value="">
    </form>
</div>

<?php
#--------------------------------------
# Rename config dialog
#--------------------------------------
?>
<script>
$(function() {
    // Rename ETL configuration form
    renameForm = $("#renameForm").dialog({
        autoOpen: false,
        height: 220,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Rename configuration": function() {renameForm.submit();}
        },
        title: "Rename configuration"
    });
    
    $(".renameConfig").click(function(){
        var id = this.id; // id contains the configuration name
        var configName = id.substring(6);
        $("#configToRename").text('"'+configName+'"');
        $('#renameConfigName').val(configName);
        $("#renameForm").dialog("open");
    });
});
</script>
<div id="renameDialog"
    title="Configuration Rename"
    style="display: none;"
    >
    <form id="renameForm" action="<?php echo $selfUrl;?>" method="post">
    To rename the configuration <span id="configToRename" style="font-weight: bold;"></span>,
    enter the new name for the new sconfiguration below, and click on the
    <span style="font-weight: bold;">Rename configuration</span> button.
    <p>
    <span style="font-weight: bold;">New configuration name:</span>
    <input type="text" name="renameNewConfigName" id="renameNewConfigName">
    </p>
    <input type="hidden" name="renameConfigName" id="renameConfigName" value="">
    </form>
</div>


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
        var configName = id.substring(6);
        $("#configToDelete").text('"'+configName+'"');
        $('#deleteConfigName').val(configName);
        $("#deleteForm").dialog("open");
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



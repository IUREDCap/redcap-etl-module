<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';


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

$submitValue = $_POST['submitValue'];
if (strcasecmp($submitValue, 'Add') === 0) {
    if (!array_key_exists('configurationName', $_POST) || empty($_POST['configurationName'])) {
        $error = 'ERROR: No configuration name was specified.';
    } else {
        $configurationName = $_POST['configurationName'];
        $configuration = $module->getConfiguration($configurationName);
        if (isset($configuration)) {
            $error = 'ERROR: configuration "'.$configurationName.'" already exists.';
        } else {
            $indexUrl = $module->getUrl("web/index.php");
            $module->addConfiguration($configurationName);
            header('Location: '.$indexUrl);
        }
    }
}

#---------------------------------------------
# Add custom files to head section of page
#---------------------------------------------
ob_start();
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);

#$buffer = $module->renderProjectPageHeader();
echo $buffer;
?>

<div class="projhdr">
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>

<?php


$configurationNames = $module->getUserConfigurationNames();

$adminConfig = $module->getAdminConfig();

$selfUrl     = $module->getUrl('web/index.php');
$configUrl   = $module->getUrl("web/configure.php");
$scheduleUrl = $module->getUrl("web/schedule.php");
$runUrl      = $module->getUrl("web/run.php");

$userEtlProjects = $module->getUserEtlProjects();
$projectId = $module->getProjectId();

?>

<?php

$module->renderProjectPageContentHeader($selfUrl, $error, $success);

?>



<?php

#--------------------------------------------------------------------
# If the user does NOT have permission to use ETL for this project,
# display a link to send e-mail to request access
#--------------------------------------------------------------------
if (!in_array($projectId, $userEtlProjects)) {
    echo '<div style="padding-top:15px; padding-bottom:15px;">'."\n";
    $label = 'Request ETL access for this project';
    $projectUrl = APP_PATH_WEBROOT_FULL.'index.php?pid='.$projectId;

    # The underscore variable names are internal REDCap variables
    // phpcs:disable
    $homepageContactEmail = $homepage_contact_email;
    $redcapVervaion = $redcap_version;
    $userFirstName = $user_firstname;
    $userLastName  = $user_lastname;
    // phpcs:enable
    
    echo '<a href="mailto:'.$homepageContactEmail
        .'?subject='.rawurlencode('REDCap-ETL Access Request')
        .'&body='
        .rawurlencode(
            'Username: '.USERID."\n"
            .'Project title: "'.' '.strip_tags(REDCap::getProjectTitle()).'"'."\n"
            .'Project link: '.APP_PATH_WEBROOT_FULL."redcap_v{$redcapVersion}/index.php?pid={$projectId}\n\n"
            .'Dear REDCap administrator,'."\n\n"
            .'Please add REDCap-ETL access for me to project "'.REDCap::getProjectTitle().'"'."\n\n"
            ."Sincerely,\n"
            .$userFirstName.' '.$userLastName
        )
        .'" '
        .' class="btn-contact-admin btn btn-primary btn-xs" style="color:#fff;">'
        .'<span class="glyphicon glyphicon-envelope"></span> '.$label
        .'</a>'."\n";
    ;
    echo "</div>\n";
} else {
?>

<?php
#------------------------------------------------------------
# Add configuration form
#------------------------------------------------------------
?>
<form action="<?=$selfUrl;?>" method="post" style="margin-bottom: 12px;">
    REDCap-ETL configuration name: <input name="configurationName" type="text">
    <input type="submit" name="submitValue" value="Add" />
</form>



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

#----------------------------------------------------------
# Displays rows of table of user's ETL configurations
#----------------------------------------------------------
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
    
    print "<td>".REDCap::escapeHtml($configurationName)."</td>\n";
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
        
    print '<script>var test1 = 123;</script>'."\n";
    print '<td style="text-align:center;">'
        .'<img src="'.APP_PATH_IMAGES.'page_copy.png" class="copyConfig" style="cursor: pointer;"'
        .' id="copyConfig'.$row.'"/>'
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
}
?>

<?php
#--------------------------------------
# Copy config dialog
#--------------------------------------
?>
<script>
$(function() {
    copyForm = $("#copyForm").dialog({
        autoOpen: false,
        height: 220,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Copy configuration": function() {copyForm.submit(); $(this).dialog("close");}
        },
        title: "Copy configuration"
    });
    <?php
    $row = 1;
    foreach ($configurationNames as $configurationName) {
        echo '$("#copyConfig'.$row.'").click({fromConfig: "'.$configurationName.'"}, copyConfig);'."\n";
        $row++;
    }
    ?>
    function copyConfig(event) {
        var configName = event.data.fromConfig;
        $("#configToCopy").text('"'+configName+'"');
        $('#copyFromConfigName').val(configName);
        $("#copyForm").dialog("open");
    }
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



<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Filter;

#--------------------------------------------------------------
# If the user doesn't have permission to access REDCap-ETL for
# this project, redirect them to the access request page which
# should display a link to send e-mail to request permission.
#--------------------------------------------------------------
if (!Authorization::hasEtlProjectPagePermission($module, USERID)) {
    $requestAccessUrl = $module->getUrl('web/request_access.php');
    header('Location: '.$requestAccessUrl);
}

#-----------------------------------------------------------------
# Process form submissions (configuration add/copy/delete/rename)
#-----------------------------------------------------------------
$submitValue = $_POST['submitValue'];
if (strcasecmp($submitValue, 'add') === 0) {
    #--------------------------------------
    # Add configuration
    #--------------------------------------
    if (!array_key_exists('configurationName', $_POST) || empty($_POST['configurationName'])) {
        $error = 'ERROR: No configuration name was specified.';
    } else {
        try {
            $configurationName = $_POST['configurationName'];
            $configuration = $module->getConfiguration($configurationName);
            if (isset($configuration)) {
                $error = 'ERROR: configuration "'.$configurationName.'" already exists.';
            } else {
                $indexUrl = $module->getUrl("web/index.php");
                $module->addConfiguration($configurationName);
                header('Location: '.$indexUrl);
            }
        } catch (\Exception $exception) {
            $error = 'ERROR: '.$exception->getMessage();
        }
    }
}


require_once APP_PATH_DOCROOT . 'Config/init_global.php';

// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addStylesheet("jquery-ui.min.css", 'screen,print');
$objHtmlPage->addStylesheet("style.css", 'screen,print');
$objHtmlPage->addStylesheet("home.css", 'screen,print');
$objHtmlPage->PrintHeader();

include APP_PATH_VIEWS . 'HomeTabs.php';


#---------------------------------------------
# Add custom files to head section of page
#---------------------------------------------
###ob_start();
###include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
###$buffer = ob_get_clean();
###$cssFile = $module->getUrl('resources/redcap-etl.css');
###$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
###$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);

#$buffer = $module->renderProjectPageHeader();
###echo $buffer;
?>

<div class="projhdr">
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>

<h1>HELP</h1>

<?php

$configurationNames = $module->getConfigurationNames();

$adminConfig = $module->getAdminConfig();

$selfUrl     = $module->getUrl('web/help.php');


?>



<?php

#--------------------------------------------------------------------
# If the user does NOT have permission to use ETL for this project,
# display a link to send e-mail to request access
#--------------------------------------------------------------------
if (!SUPER_USER && !in_array($projectId, $userEtlProjects)) {
    echo '<div style="padding-top:15px; padding-bottom:15px;">'."\n";
    $label = 'Request ETL access for this project';

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
    REDCap-ETL configuration name:
    <input name="configurationName" type="text" size="40" />
    <input type="submit" name="submitValue" value="Add" />
</form>

<?php
}
?>

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
  
    <?php
    # Set up click event handlers for the Delete Configuration  buttons
    $row = 1;
    foreach ($configurationNames as $configurationName) {
        echo '$("#deleteConfig'.$row.'").click({configName: "'
           .Filter::escapeForJavaScriptInDoubleQuotes($configurationName)
           .'"}, deleteConfig);'."\n";
        $row++;
    }
    ?>
    
    function deleteConfig(event) {
        var configName = event.data.configName;
        $("#configToDelete").text('"'+configName+'"');
        $('#deleteConfigName').val(configName);
        $("#deleteForm").dialog("open");
    }
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
    <input type="hidden" name="submitValue" value="delete">
    </form>
</div>



<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>



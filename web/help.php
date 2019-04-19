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



require_once APP_PATH_DOCROOT . 'Config/init_global.php';

// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addStylesheet("jquery-ui.min.css", 'screen,print');
$objHtmlPage->addStylesheet("style.css", 'screen,print');
$objHtmlPage->addStylesheet("home.css", 'screen,print');
$objHtmlPage->PrintHeader();

require_once APP_PATH_VIEWS . 'HomeTabs.php';


#---------------------------------------------
# Add custom files to head section of page
#---------------------------------------------
###ob_start();
###require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
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

$adminConfig = $module->getAdminConfig();

$selfUrl     = $module->getUrl('web/help.php');


?>




<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>


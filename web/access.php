<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\RedCapEtlModule\RedCapEtlModule;
use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Filter;

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$selfUrl = $module->getUrl('web/access.php');
$requestLabel = 'Request ETL access for this project';

$requestError = '';

$submitValue = '';
if (array_key_exists('submitValue', $_POST)) {
    $submitValue = $_POST['submitValue'];
    if ($submitValue === $requestLabel) {
        // phpcs:disable
        $to = $homepage_contact_email; 
        $from = RedCapEtlModule::getFromEmail();
        $subject = 'REDCap-ETL Access Request';
        $projectLink = APP_PATH_WEBROOT_FULL."redcap_v{$redcap_version}/index.php?pid=".PROJECT_ID;
        $adminLink = $module->getUrl(RedCapEtlModule::USER_CONFIG_PAGE.'?username='.USERID);

        $message = '<html><bod>'
            .'REDCap-ETL access request from user '.$user_firstname.' '.$user_lastname.' ('.USERID.')'
            .' [<a href="mailto:'.$user_email.'">'.$user_email.'</a>]'."\n"
            .'for project "'.strip_tags(REDCap::getProjectTitle()).'" [Project ID='.PROJECT_ID.'].'."<br/>\n\n"
            .'Project link: <a href="'.$projectLink.'">'.$projectLink.'</a><br/>'."\n\n"
            .'Admin link: <a href="'.$adminLink.'">'.$adminLink.'</a>'."\n\n"
            .'</body></html>'
            ;
        // phpcs:enable

        $cc = null;

        try {
            REDCap::email($to, $from, $subject, $message, $cc);
        } catch (Exception $exception) {
            $requestError = $exception->getMessage();
        }
    }
}
?>

<div class="projhdr">
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png" alt="">REDCap-ETL
</div>


<?php

$accessError = (int) Filter::sanitizeInt($_GET['accessError']);

$projectId = $module->getProjectId();

if ($accessError === RedCapEtlModule::CSRF_ERROR) {
    echo 'You do not have permission to perform that operation. Your session'
        .' may have expired. Please make sure that you are logged in and '
        .' try again.';
} elseif ($accessError === RedCapEtlModule::USER_RIGHTS_ERROR) {
    echo 'You do not have permission to use REDCap-ETL'
        .' for this project. You need to have:'
        .' <ul>'
        .' <li>REDCap user right "Project Design and Setup"</li>'
        .' <li>REDCap data export user right of "Full Data Set"</li>'
        .' <li>No data access group (i.e., you can access all records)</li>.'
        .' </ul>';
} elseif ($accessError === RedCapEtlModule::NO_CONFIGURATION_PERMISSION) {
    echo 'You do not have permission to access the specified configuration.';
} elseif ($accessError === RedCapEtlModule::NO_ETL_PROJECT_PERMISSION) {
    #--------------------------------------------------------------------
    # The user does NOT have permission to use ETL for this project,
    # but does have permission to request ETL permission, so
    # display a link to send e-mail to request access
    #--------------------------------------------------------------------

    echo "<p>You don't currently have permission to use REDCap-ETL for this project."
        ." To request access, click on the button below\n";

    echo '<div style="padding-top:15px; padding-bottom:15px;">'."\n";

    echo '<form action="'.$selfUrl.'" method="post">'."\n";
    echo '    <input type="submit" name="submitValue" id="requestButton" '
        .' value="Request ETL access for this project" '
        .' onclick="$(\'#requestButton\').css(\'cursor\', \'progress\'); $(\'body\').css(\'cursor\', \'progress\');" >'
        ;

    echo '    <?php Csrf::generateFormToken(); ?>'."\n";
    echo "</form>\n";


    # The underscore variable names are internal REDCap variables
    // phpcs:disable
    $homepageContactEmail = $homepage_contact_email;
    $redcapVersion = $redcap_version;
    $userFirstName = $user_firstname;
    $userLastName  = $user_lastname;
    // phpcs:enable
    
    echo "</div>\n";
} elseif ($submitValue === $requestLabel) {
    if (empty($requestError)) {
        echo 'Request for REDCap-ETL access for project "'.strip_tags(REDCap::getProjectTitle()).'" sent.';
    } else {
        echo "Request for REDCap-ETL failed: {$requestError}";
    }
} else {
    echo 'An unknown access error occurred.';
}
?>

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>



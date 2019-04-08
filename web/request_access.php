<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Filter;

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
?>

<div class="projhdr">
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>


<?php

$userEtlProjects = $module->getUserEtlProjects();
$projectId = $module->getProjectId();


if (!Authorization::hasEtlRequestPermission($module, USERID)) {
    echo "You do not have permission to use, or request the use of, REDCap-ETL"
        ." for this project.";
} elseif (!Authorization::hasEtlProjectPagePermission($module, USERID)) {
    #--------------------------------------------------------------------
    # The user does NOT have permission to use ETL for this project,
    # but does have permission to request ETL permission, so
    # display a link to send e-mail to request access
    #--------------------------------------------------------------------
    echo '<div style="padding-top:15px; padding-bottom:15px;">'."\n";
    $label = 'Request ETL access for this project';

    # The underscore variable names are internal REDCap variables
    // phpcs:disable
    $homepageContactEmail = $homepage_contact_email;
    $redcapVersion = $redcap_version;
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
    echo "You have been granted access to this project.";
}
?>

<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>



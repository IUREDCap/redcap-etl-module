<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

if (!SUPER_USER) {
    exit("Only super users can access this page!");
}

require_once __DIR__.'/../../dependencies/autoload.php';

use IU\RedCapEtlModule\AdminConfig;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

$selfUrl  = $module->getUrl(RedCapEtlModule::USERS_PAGE);
$adminUrl = $module->getURL(RedCapEtlModule::ADMIN_HOME_PAGE);
$userUrl  = $module->getURL(RedCapEtlModule::USER_CONFIG_PAGE);

$adminConfigJson = $module->getSystemSetting(AdminConfig::KEY);
$adminConfig = new AdminConfig();


$submitValue = $_POST['submitValue'];
$username = $_POST['username-result'];
$userLabel = $_POST['userLabel'];

$users = $module->getUsers();


if (!empty($username)) {
    if (strcasecmp($submitValue, 'Save') === 0) {
        $checkbox = $_POST['checkbox'];
        $userEtlProjects = array();
        foreach (array_keys($checkbox) as $projectId) {
            array_push($userEtlProjects, $projectId);
        }
        $module->addUser($username);
        $module->setUserEtlProjects($username, $userEtlProjects);
        header('Location: '.$adminUrl);
        exit;
    }
    $db = new RedCapDb();
    $userProjects = $db->getUserProjects($username);
    $userEtlProjects = $module->getUserEtlProjects($username);
}

#---------------------------------------------
# Include REDCap's Control Center page header
#---------------------------------------------
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
#print "<pre><br />\n"; print_r($_POST); print "</pre> <br/> \n";
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>

<?php

$errorMessage = $_GET['error'];
$successMessage = $_GET['success'];

$module->renderAdminPageContentHeader($selfUrl, $errorMessage, $successMessage);

?>


<h5 style="margin-top: 2em;">REDCap-ETL Users</h5>
<table class="dataTable">
    <thead>
        <tr> <th>username</th> <th>ETL Project Permissions</th> <th>ETL Configurations</th> </tr>
    </thead>
    <tbody>
    <?php
    $row = 1;
    foreach ($users as $user) {
        $etlProjects = $module->getUserEtlProjects($user);
        $configCount = 0;
        foreach ($etlProjects as $etlProject) {
            $configNames = $module->getConfigurationNames($etlProject);
            $configCount += count($configNames);
        }
        $userConfigUrl = $userUrl.'&username='.Filter::escapeForUrlParameter($user);
        if ($row % 2 == 0) {
            echo "<tr class=\"even\">\n";
        } else {
            echo "<tr class=\"odd\">\n";
        }
        echo '<td><a href="'.$userConfigUrl.'">'.Filter::escapeForHtml($user).'</td>'."\n";
        echo '<td style="text-align: right;">'.count($etlProjects)."</td>\n";
        echo '<td style="text-align: right;">'.$configCount."</td>\n";
        echo "</tr>\n";
        $row++;
    }
    ?>
    </tbody>
</table>


<?php include APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

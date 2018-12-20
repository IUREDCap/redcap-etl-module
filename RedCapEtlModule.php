<?php

namespace IU\RedCapEtlModule;

# This is required for cron jobs
// phpcs:disable
require_once(__DIR__.'/dependencies/autoload.php');
// phpcs:enable

/**
 * Main REDCap-ETL module class.
 */
class RedCapEtlModule extends \ExternalModules\AbstractExternalModule
{
    const ADMIN_HOME_PAGE    = 'web/admin/config.php';
    const CRON_DETAIL_PAGE   = 'web/admin/cron_detail.php';
    const USERS_PAGE         = 'web/admin/users.php';
    const USER_CONFIG_PAGE   = 'web/admin/user_config.php';
    const SERVERS_PAGE       = 'web/admin/servers.php';
    const SERVER_CONFIG_PAGE = 'web/admin/server_config.php';
    const ADMIN_ETL_CONFIG_PAGE = 'web/admin/admin_etl_config.php';

    private $db;
    private $settings;

    public function __construct()
    {
        $this->db = new RedCapDb();
        $this->settings = new Settings($this);
        parent::__construct();
    }
    

    /**
     * Cron method that is called by REDCap as configured in the
     * config.json file for this module.
     */
    public function cron()
    {
        $adminConfig = $this->getAdminConfig();
        
        #---------------------------------------------------
        # If ETL cron jobs are allowed
        #---------------------------------------------------
        if ($adminConfig->getAllowCron()) {
            $now = new \DateTime();
            $day  = $now->format('w');  // 0-6 (day of week; Sunday = 0)
            $hour = $now->format('G');  // 0-23 (24-hour format without leading zeroes)
            $date = $now->format('Y-m-d');

            #\REDCap::logEvent('REDCap-ETL cron check on day '.$day.', hour '.$hour);
            
            if ($adminConfig->isAllowedCronTime($day, $hour)) {
                #\REDCap::logEvent('REDCap-ETL cron - cron allowed for day '.$day.', hour '.$hour);
                
                if ($this->isLastRunTime($date, $hour)) {
                    #\REDCap::logEvent('REDCap-ETL cron - cron already processed for day '.$day.', hour '.$hour);
                    ; // This time has already been processed, so don't do anything'
                } else {
                    $cronJobs = $this->getCronJobs($day, $hour);
        
                    #\REDCap::logEvent('REDCap-ETL cron - processing '
                    #    .count($cronJobs).' jobs for day '.$day.', hour '.$hour);
                    
                    foreach ($cronJobs as $cronJob) {
                        $username   = $cronJob['username'];
                        $projectId  = $cronJob['projectId'];
                        $serverName = $cronJob['server'];
                        $configName = $cronJob['config'];
                        
                        $userEtlProjects = $this->getUserEtlProjects($username);
                        
                        #------------------------------------------------------
                        # If user has permission to run ETL for this project
                        #------------------------------------------------------
                        if (!empty($userEtlProjects) && in_array($projectId, $userEtlProjects)) {
                            $etlConfig    = $this->getConfiguration($configName, $username, $projectId);
                            $serverConfig = $this->getServerConfig($serverName);
                            
                            #----------------------------------------------------
                            # If the server is active
                            #----------------------------------------------------
                            if ($serverConfig->getIsActive()) {
                                \REDCap::logEvent(
                                    'REDCap-ETL cron job config "'.$configName.'" for user "'
                                    .$username.'" on server "'.$serverName.'" on day '.$day.', hour '.$hour
                                );
                
                                if (strcasecmp($serverName, ServerConfig::EMBEDDED_SERVER_NAME) == 0) {
                                    if ($adminConfig->getAllowEmbeddedServer()) {
                                        $logger = new \IU\REDCapETL\Logger('REDCap-ETL');
                                        $logger->turnOff();
                                        $logger->setPrintInfo(true);
                                        $properties = $etlConfig->getPropertiesArray();
                                        $redCapEtl  = new \IU\REDCapETL\RedCapEtl($logger, $properties);
                                        $redCapEtl->run();
                                    }
                                } else {
                                    $serverConfig->run($etlConfig);
                                }
                            }
                        }
                    } # END - foreach cron job
                    
                    $this->setLastRunTime($date, $hour);
                }
            }
        }
    }


    /**
     * Gets the module version number based on its directory.
     *
     * NOTE: version is stored in the database, we should
     * probably have just used that value (system setting,
     * key = 'version').
     */
    public function getVersionNumber()
    {
        $versionNumber = '';
        $dirName = $this->getModuleDirectoryName();
        if (preg_match('/.*_v(.*)/', $dirName, $matches) === 1) {
            $versionNumber = $matches[1];
        }
        return $versionNumber;
    }

    /**
     * Gets the settings for this module.
     *
     * @return Settings the settings for this module.
     */
    public function getSettings()
    {
        return $this->settings;
    }
    
    
    
    #-------------------------------------------------------------------
    # UserList methods
    #-------------------------------------------------------------------

    public function getUsers()
    {
        return $this->settings->getUsers();
    }

    public function addUser($username)
    {
        $this->settings->addUser($username);
    }


    #-------------------------------------------------------------------
    # UserInfo methods
    #-------------------------------------------------------------------
    
    private function getUserInfo($username = USERID, $projectId = PROJECT_ID)
    {
        return $this->settings->getUserInfo($username, $projectId);
    }

    private function setUserInfo($userInfo, $username = USERID, $projectId = PROJECT_ID)
    {
        $this->settings->setUserInfo($userInfo, $username, $projectId);
    }

    public function getUserConfigurationNames($username = USERID, $projectId = PROJECT_ID)
    {
        return $this->settings->getUserConfigurationNames($username, $projectId);
    }


    #-------------------------------------------------------------------
    # User ETL project methods
    #-------------------------------------------------------------------
        
    public function getUserEtlProjects($username = USERID)
    {
        return $this->settings->getUserEtlProjects($username);
    }
    
    /**
     * @param array $projects an array of REDCap project IDS
     *     for which the user has ETL permission.
     */
    public function setUserEtlProjects($username, $projects)
    {
        $this->settings->setUserEtlProjects($username, $projects);
    }


    #-------------------------------------------------------------------
    # (ETL) Configuration methods
    #-------------------------------------------------------------------
    
    /**
     * Gets the specified configuration for the current user.
     *
     * @param string $name the name of the configuration to get.
     * @return Configuration the specified configuration.
     */
    public function getConfiguration($name, $username = USERID, $projectId = PROJECT_ID)
    {
        return $this->settings->getConfiguration($name, $username, $projectId);
    }

    /**
     * @param Configuration $configuration
     * @param string $username
     * @param string $projectId
     */
    public function setConfiguration($configuration, $username = USERID, $projectId = PROJECT_ID)
    {
        $this->settings->setConfiguration($configuration, $username, $projectId);
    }

    public function setConfigSchedule($configName, $server, $schedule, $username = USERID, $projectId = PROJECT_ID)
    {
        $this->settings->setConfigSchedule($configName, $server, $schedule, $username, $projectId);
    }
    
    public function addConfiguration($name, $username = USERID, $projectId = PROJECT_ID)
    {
        $this->settings->addConfiguration($name, $username, $projectId);
    }


    /**
     * Copy configuration (only supports copying from/to same
     * user and project).
     */
    public function copyConfiguration($fromConfigName, $toConfigName)
    {
        $this->settings->copyConfiguration($fromConfigName, $toConfigName);
    }
    
    /**
     * Rename configuration (only supports rename from/to same
     * user and project).
     */
    public function renameConfiguration($configName, $newConfigName)
    {
        $this->settings->renameConfiguration($configName, $newConfigName);
    }
    
    public function removeConfiguration($configName)
    {
        $this->settings->removeConfiguration($configName);
    }
   
    #-------------------------------------------------------------------
    # Cron job methods
    #-------------------------------------------------------------------
     
    /**
     * Gets all the cron jobs (for all users and all projects).
     */
    public function getAllCronJobs()
    {
        return $this->settings->getAllCronJobs();
    }
    
    /**
     * Gets the cron jobs for the specified day (0 = Sunday, 1 = Monday, ...)
     * and time (0 = 12am - 1am, 1 = 1am - 2am, ..., 23 = 11pm - 12am).
     */
    public function getCronJobs($day, $time)
    {
        return $this->settings->getCronJobs($day, $time);
    }


    #==================================================================================
    # Admin Config methods
    #==================================================================================

    public function getAdminConfig()
    {
        return $this->settings->getAdminConfig();
    }
    
    public function setAdminConfig($adminConfig)
    {
        $this->settings->setAdminConfig($adminConfig);
    }

    #==================================================================================
    # Server methods
    #==================================================================================
    
    public function getServers()
    {
        return $this->settings->getServers();
    }

    public function addServer($serverName)
    {
        $this->settings->addServer($serverName);
    }

    public function copyServer($fromServerName, $toServerName)
    {
        $this->settings->copyServer($fromServerName, $toServerName);
    }
    
    public function renameServer($serverName, $newServerName)
    {
        $this->settings->renameServer($serverName, $newServerName);
    }
    
    
    public function removeServer($serverName)
    {
        $this->settings->removeServer($serverName);
    }


    #==================================================================================
    # Server Config methods
    #==================================================================================
    
    public function getServerConfig($serverName)
    {
        return $this->settings->getServerConfig($serverName);
    }
    
    public function setServerConfig($serverConfig)
    {
        $this->settings->setServerConfig($serverConfig);
    }
    
    private function copyServerConfig($fromServerName, $toServerName)
    {
        $this->settings->copyServerConfig($fromServerName, $toServerName);
    }
    
    public function renameServerConfig($serverName, $newServerName)
    {
        $this->settings->renameServerConfig($serverName, $newServerName);
    }
        
    public function removeServerConfig($serverName)
    {
        return $this->settings->removeServerConfig($serverName);
    }


    #==================================================================================
    # Last run time methods
    #==================================================================================
    
    public function getLastRunTime()
    {
        $this->settings->getLastRunTime();
    }

    public function setLastRunTime($date, $time)
    {
        $this->settings->setLastRunTime($date, $time);
    }
    
    public function isLastRunTime($date, $time)
    {
        return $this->settings->isLastRunTime($date, $time);
    }



    /**
     * Renders the page content tabs for REDCap-ETL admin pages.
     */
    public function renderAdminTabs($activeUrl = '')
    {
        $adminUrl = $this->getUrl(self::ADMIN_HOME_PAGE);
        $adminLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' Config';

        $cronJobsUrl = $this->getUrl(self::CRON_DETAIL_PAGE);
        $cronJobsLabel = '<span class="glyphicon glyphicon-time" aria-hidden="true"></span>'
           .' Cron Detail';

        $usersUrl = $this->getUrl(self::USERS_PAGE);
        #$manageUsersLabel = '<span>Manage Users</span>';
        #$manageUsersLabel = '<span><img aria-hidden="true" src="/redcap/redcap_v8.5.11/Resources/images/users3.png">'
        $usersLabel = '<span class="glyphicon glyphicon-list" aria-hidden="true"></span>'
           .' Users</span>';

        $configureUserUrl = $this->getUrl(self::USER_CONFIG_PAGE);
        $configureUserLabel = '<span class="glyphicon glyphicon-user" aria-hidden="true"></span>'
           .' User Config</span>';
           
        $serversUrl = $this->getUrl(self::SERVERS_PAGE);
        $serversLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' ETL Servers';

        $serverConfigUrl = $this->getUrl(self::SERVER_CONFIG_PAGE);
        $serverConfigLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' ETL Server Config';

        $tabs = array();
        
        $tabs[$adminUrl]         = $adminLabel;
        $tabs[$cronJobsUrl]      = $cronJobsLabel;
        $tabs[$usersUrl]         = $usersLabel;
        $tabs[$configureUserUrl] = $configureUserLabel;

        $tabs[$serversUrl]       = $serversLabel;
        $tabs[$serverConfigUrl]  = $serverConfigLabel;
        
        $this->renderTabs($tabs, $activeUrl);
    }

    /**
     * Renders the top-level tabs for the user interface.
     */
    public function renderUserTabs($activeUrl = '')
    {
        $listUrl = $this->getUrl('web/index.php');
        $listLabel = '<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span>'
           .' My ETL Configurations';

        #$addUrl = $this->getUrl('add.php');
        #$addLabel = '<span style="color: #008000;" class="glyphicon glyphicon-plus" aria-hidden="true"></span>'
        #   .' New Configuration';

        $configUrl = $this->getUrl('web/configure.php');
        $configLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' Configure';

        $runUrl = $this->getUrl('web/run.php');
        $runLabel = '<span style="color: #008000;" class="glyphicon glyphicon-play-circle" aria-hidden="true"></span>'
           .' Run';

        $scheduleUrl = $this->getUrl('web/schedule.php');
        $scheduleLabel = '<span class="glyphicon glyphicon-time" aria-hidden="true"></span>'
           .' Schedule';

        $adminConfig = $this->getAdminConfig();
        
        $tabs = array();
        
        $tabs[$listUrl]     = $listLabel;
        #$tabs[$addUrl]      = $addLabel;
        $tabs[$configUrl]   = $configLabel;
        
        if ($adminConfig->getAllowCron()) {
            $tabs[$scheduleUrl] = $scheduleLabel;
        }
    
        if ($adminConfig->getAllowOnDemand()) {
            $tabs[$runUrl] = $runLabel;
        }
        
        $this->renderTabs($tabs, $activeUrl);
    }


    /**
     * RENDER TABS FROM ARRAY WITH 'PAGE' AS KEY AND LABEL AS VALUE
     */
    public function renderTabs($tabs = array(), $activeUrl = '')
    {
        echo '<div id="sub-nav" style="margin:5px 0 20px;">'."\n";
        echo '<ul>'."\n";
        foreach ($tabs as $thisUrl => $thisLabel) {
            // Check for Active tab
            $isActive = false;
            $class = '';
            if (strcasecmp($thisUrl, $activeUrl) === 0) {
                $class = ' class="active"';
                $isActive = true;
            }
            echo '<li '.$class.'>'."\n";
            echo '<a href="'.$thisUrl.'" style="font-size:13px;color:#393733;padding:6px 9px 5px 10px;">';
            echo $thisLabel.'</a>'."\n";
        }
        echo '</li>'."\n";
        echo '</ul>'."\n";
        echo '</div>'."\n";
        echo '<div class="clear"></div>'."\n";
    }
    
    public function renderSuccessMessageDiv($message)
    {
        if (!empty($message)) {
            echo '<div align="center" class="darkgreen" style="margin: 20px 0;">'."\n";
            echo '<img src="'.(APP_PATH_IMAGES.'accept.png').'">';
            echo Filter::escapeForHtml($message)."\n";
            echo "</div>\n";
        }
    }
    
        
    public function renderErrorMessageDiv($message)
    {
        if (!empty($message)) {
            echo '<div align="center" class="red" style="margin: 20px 0;">'."\n";
            echo '<img src="'.(APP_PATH_IMAGES.'exclamation.png').'">';
            echo Filter::escapeForHtml($message)."\n";
            echo "</div>\n";
        }
    }
    
    
    public function renderAdminPageContentHeader($selfUrl, $errorMessage, $successMessage)
    {
        $this->renderAdminTabs($selfUrl);
        $this->renderErrorMessageDiv($errorMessage);
        $this->renderSuccessMessageDiv($successMessage);
    }
    
    /**
     * Renders the page content header for a project page.
     *
     * @param string $selfUrl the URL of the page where the content header is to rendered.
     * @param string $errorMessage the error message to print (if any).
     * @param string $successMessage the success message to print (if any).
     */
    public function renderProjectPageContentHeader($selfUrl, $errorMessage, $successMessage)
    {
        $this->renderUserTabs($selfUrl);
        $this->renderErrorMessageDiv($errorMessage);
        $this->renderSuccessMessageDiv($successMessage);
    }
    
    public function renderProjectPageHeader()
    {
        ob_start();
        include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
        $buffer = ob_get_clean();
        #$cssFile = $this->getUrl('resources/redcap-etl.css');
        #$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
        #$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
        return $buffer;
    }
}

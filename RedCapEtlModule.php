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
    const ADMIN_CONFIG_KEY         = 'admin-config';
    const SERVER_CONFIG_KEY_PREFIX = 'server-config:';
    const SERVERS_KEY              = 'servers';
    const USER_LIST_KEY            = 'user-list';
    const LAST_RUN_TIME_KEY        = 'last-run-time'; // for storing day and time of last run

    const USER_PROJECTS_KEY_PREFIX = 'user-projects:';  // appdend with username to make key
    
    const CONFIG_SESSION_KEY = 'redcap-etl-config';
    
    /**
     * Cron method that is called by REDCap as configured in the
     * config.json file for this module.
     */
    public function cron()
    {
        $day  = date('w');  // 0-6 (day of week; Sunday = 0)
        $hour = date('G');  // 0-23 (24-hour format without leading zeroes)
        $date = date('Y-m-d');
        \REDCap::logEvent('REDCap-ETL cron check for day '.$day.' and hour '.$hour);
                
        if ($this->isLastRunTime($date, $hour)) {
            ; // This time has already been processed, so don't do anything'
        } else {
            $cronJobs = $this->getCronJobs($day, $hour);
        
            foreach ($cronJobs as $cronJob) {
                $username   = $cronJob['username'];
                $serverName = $cronJob['server'];
                $configName = $cronJob['config'];
            
                $etlConfig    = $this->getConfiguration($configName, $username);
                $serverConfig = $this->getServerConfig($serverName);
                \REDCap::logEvent('REDCap-ETL cron job config "'.$configName.'" for user "'
                    .$username.'" on server "'.$serverName.'" on day '.$day.', hour '.$hour);
                $serverConfig->run($etlConfig);
                $this->setLastRunTime($date, $hour);
            }
        }
    }

    /**
     * Gets the module version number based on its directory.
     *
     * NOTE: version is stored in the database, we should
     * probably just used that value (system setting,
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

    #-------------------------------------------------------------------
    # UserList methods
    #-------------------------------------------------------------------

    public function getUsers()
    {
        $userList = new UserList();
        $json = $this->getSystemSetting(self::USER_LIST_KEY);
        $userList->fromJson($json);
        $users = $userList->getUsers();
        return $users;
    }

    public function addUser($username)
    {
        $userList = new UserList();
        $json = $this->getSystemSetting(self::USER_LIST_KEY);
        $userList->fromJson($json);
        $userList->addUser($username);
        $json = $userList->toJson();
        $this->setSystemSetting(self::USER_LIST_KEY, $json);
    }


    #-------------------------------------------------------------------
    # UserInfo methods
    #-------------------------------------------------------------------
    
    private function getUserInfo($username = USERID, $projectId = PROJECT_ID)
    {
        $key = $this->getUserKey($username);
        $json = $this->getProjectSetting($key, $projectId);
        $userInfo = new UserInfo($username);
        $userInfo->fromJson($json);
        return $userInfo;
    }

    private function setUserInfo($userInfo, $username = USERID, $projectId = PROJECT_ID)
    {
        $key = $this->getUserKey($username);
        $json = $userInfo->toJson();
        $this->setProjectSetting($key, $json, $projectId);
    }

    public function getUserConfigurationNames($username = USERID, $projectId = PROJECT_ID)
    {
        $userInfo = $this->getUserInfo($username, $projectId);
        $names = $userInfo->getConfigNames();
        return $names;
    }

    /**
     * Gets the key for REDCap's external module settings table
     * for the specified username, or the current username,
     * if no username was specified.
     */
    public function getUserKey($username = USERID, $projectId = PROJECT_ID)
    {
        $key = 'user:'.$username;
        return $key;
    }


    #===================================================================
    # User ETL project methods
    #===================================================================
    
    /**
     * @param array $projects an array of REDCap project IDS
     *     for which the user has ETL permission.
     */
    public function setUserEtlProjects($username, $projects)
    {
        $key = self::USER_PROJECTS_KEY_PREFIX . $username;
        $json = json_encode($projects);
        $this->setSystemSetting($key, $json);
    }
    
    public function getUserEtlProjects($username = USERID)
    {
        $key = self::USER_PROJECTS_KEY_PREFIX . $username;
        $json = $this->getSystemSetting($key);
        $projects = json_decode($json, true);
        return $projects;
    }


    #==================================================================================
    # (ETL) Configuration methods
    #==================================================================================
    
    /**
     * Gets the specified configuration for the current user.
     *
     * @param string $name the name of the configuration to get.
     * @return Configuration the specified configuration.
     */
    public function getConfiguration($name, $username = USERID, $projectId = PROJECT_ID)
    {
        $configuraion = null;
        $key = $this->getConfigurationKey($name, $username);

        $setting = $this->getProjectSetting($key, $projectId);
        $configValues = json_decode($setting, true);
        if (isset($configValues) && is_array($configValues)) {
            $configuration = new Configuration($configValues['name']);
            $configuration->set($configValues['properties']);
        }
        return $configuration;
    }


    public function setConfiguration($configuration, $username = USERID, $projectId = PROJECT_ID)
    {
        $key = $this->getConfigurationKey($configuration->getName());

        $json = json_encode($configuration);
        $setting = $this->setProjectSetting($key, $json, $projectId);
    }

    public function setConfigSchedule($configName, $server, $schedule, $username = USERID, $projectId = PROJECT_ID)
    {
        $configuration = $this->getConfiguration($configName, $username, $projectId);
        $configuration->setProperty(Configuration::CRON_SERVER, $server);
        $configuration->setProperty(Configuration::CRON_SCHEDULE, $schedule);
        $this->setConfiguration($configuration, $username, $projectId);
        
        \REDCap::logEvent(
            'REDCap-ETL cron schedule change',
            'Cron schedule changed for configuration "'.$configName.'".'
        );
    }
    
    
    public function addConfiguration($name, $username = USERID, $projectId = PROJECT_ID)
    {
        # Add configuration entry for user
        $userInfo = $this->getUserInfo();
        if (!isset($userInfo)) {
            $userInfo = new UserInfo($username);
        }

        if (!$userInfo->hasConfigName($name)) {
            $userInfo->addConfigName($name);
            $json = $userInfo->toJson();
            $userKey = $this->getUserKey();
            $this->setProjectSetting($userKey, $json, $projectId);
        }
        
        # Add the actual configuration
        $key = $this->getConfigurationKey($name);
        $configuration = $this->getSystemSetting($key);
        if (!isset($configuration)) {
            $configuration = new Configuration($name);
            $jsonConfiguration = json_encode($configuration);
            $this->setProjectSetting($key, $jsonConfiguration, $projectId);
        }

        \REDCap::logEvent('Added REDCap-ETL configuration '.$name.'.');
    }


    /**
     * Copy configuration (only supports copying from/to same
     * user and project).
     */
    public function copyConfiguration($fromConfigName, $toConfigName)
    {
        #--------------------------------------------------------
        # Add the configuration name to the user's information
        #--------------------------------------------------------
        $userInfo = $this->getUserInfo();
        $userInfo->addConfigName($toConfigName);
        $json = $userInfo->toJson();
        $userKey = $this->getUserKey();
        $this->setSystemSetting($userKey, $json);
        
        #-----------------------------------------------------
        # Copy the actual configuration
        #-----------------------------------------------------
        $toConfig = $this->getConfiguration($fromConfigName);
        $toConfig->setName($toConfigName);
        $json = $toConfig->toJson();
        $key = $this->getConfigurationKey($toConfigName);
        $this->setSystemSetting($key, $json);
    }
    
    /**
     * Rename configuration (only supports rename from/to same
     * user and project).
     */
    public function renameConfiguration($configName, $newConfigName)
    {
        $this->copyConfiguration($configName, $newConfigName);
        $this->removeConfiguration($configName);
    }
    
    public function removeConfiguration($configName)
    {
        #-----------------------------------------------------------
        # Remove the configuration name from the user's information
        #-----------------------------------------------------------
        $userInfo = $this->getUserInfo();
        if (isset($userInfo) && $userInfo->hasConfigName($configName)) {
            $userInfo->removeConfigName($configName);
            $json = $userInfo->toJson();
            $userKey = $this->getUserKey();
            $this->setProjectSetting($userKey, $json);
        }
        
        #------------------------------------------------
        # Remove the actual configuration
        #------------------------------------------------
        $key = $this->getConfigurationKey($configName);
        $this->removeProjectSetting($key);
    }



    public function getConfigurationKey($name, $username = USERID)
    {
        $key = 'user:'.$username.';configuration:'.$name;
        return $key;
    }
    
    
    #-------------------------------------------------------------------
    # Cron job methods
    #-------------------------------------------------------------------
     
    /**
     * Gets all the cron jobs (for all users and all projects).
     */
    public function getAllCronJobs()
    {
        $cronJobs = array();
        foreach (range(0, 6) as $day) {
            $cronJobs[$day] = array();
            foreach (range(0, 23) as $hour) {
                $cronJobs[$day][$hour] = array();
            }
        }
        
        $usernames = $this->getUsers();
        foreach ($usernames as $username) {
            $user = $this->getUserInfo($username);
            $configNames = $user->getConfigNames();
            foreach ($configNames as $configName) {
                $config = $this->getConfiguration($configName, $username);
                if (isset($config)) {
                    $server = $config->getProperty(Configuration::CRON_SERVER);
                    $times = $config->getProperty(Configuration::CRON_SCHEDULE);
                    for ($day = 0; $day < 7; $day++) {
                        $hour = $times[$day];
                        if (isset($hour)) {
                            $run = array('username' => $username, 'config' => $configName, 'server' => $server);
                            array_push($cronJobs[$day][$hour], $run);
                        }
                    }
                }
            }
        }
        
        return $cronJobs;
    }
    
    /**
     * Gets the cron jobs for the specified day (0 = Sunday, 1 = Monday, ...)
     * and time (0 = 12am - 1am, 1 = 1am - 2am, ..., 23 = 11pm - 12am).
     */
    public function getCronJobs($day, $time)
    {
        $cronJobs = array();
        
        $usernames = $this->getUsers();
        #\REDCap::logEvent('REDCap-ETL getCronJobs: usernames: '.print_r($usernames, true));
        
        foreach ($usernames as $username) {
            #\REDCap::logEvent('REDCap-ETL getCronJobs: username: '.$username);
            $user = $this->getUserInfo($username);
            $configNames = $user->getConfigNames();
            #\REDCap::logEvent('REDCap-ETL getCronJobs: configNames: '.print_r($configNames, true));
            
            foreach ($configNames as $configName) {
                $config = $this->getConfiguration($configName, $username);
                if (isset($config)) {
                    $server = $config->getProperty(Configuration::CRON_SERVER);
                    $times  = $config->getProperty(Configuration::CRON_SCHEDULE);
                    
                    if (isset($times) && is_array($times)) {
                        for ($cronDay = 0; $cronDay < 7; $cronDay++) {
                            $cronTime = $times[$cronDay];
                            if (isset($cronTime) && $time == $cronTime && $day == $cronDay) {
                                $job = array('username' => $username, 'config' => $configName, 'server' => $server);
                                array_push($cronJobs, $job);
                            }
                        }
                    }
                }
            }
        }
                
        return $cronJobs;
    }


    #==================================================================================
    # Admin Config methods
    #==================================================================================

    public function getAdminConfig()
    {
        $adminConfig = new AdminConfig();
        $setting = $this->getSystemSetting(self::ADMIN_CONFIG_KEY);
        $adminConfig->fromJson($setting);
        return $adminConfig;
    }
    
    public function setAdminConfig($adminConfig)
    {
        $json = $adminConfig->toJson();
        $this->setSystemSetting(self::ADMIN_CONFIG_KEY, $json);
    }

    #==================================================================================
    # Server methods
    #==================================================================================
    
    public function getServers()
    {
        $servers = new Servers();
        $json = $this->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers = $servers->getServers();
        return $servers;
    }

    public function addServer($serverName)
    {
        $servers = new Servers();
        $json = $this->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers->addServer($serverName);
        $json = $servers->toJson();
        $this->setSystemSetting(self::SERVERS_KEY, $json);
    }

    public function copyServer($fromServerName, $toServerName)
    {
        $servers = new Servers();
        $json = $this->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers->addServer($toServerName);
        $json = $servers->toJson();
        $this->setSystemSetting(self::SERVERS_KEY, $json);
        
        $this->copyServerConfig($fromServerName, $toServerName);
    }
    
    public function renameServer($serverName, $newServerName)
    {
        $isError = false;
        try {
            db_query("SET AUTOCOMMIT=0");
            db_query("BEGIN");
            $servers = new Servers();
            $json = $this->getSystemSetting(self::SERVERS_KEY, true);
            $servers->fromJson($json);
            $servers->addServer($newServerName);
            $servers->removeServer($serverName);
            $json = $servers->toJson();
            $this->setSystemSetting(self::SERVERS_KEY, $json);
        
            $this->renameServerConfig($serverName, $newServerName);
        } catch (Exception $exception) {
            $isError = true;
        }
        
        if ($isError) {
            db_query("ROLLBACK");
        } else {
            db_query("COMMIT");
        }
        db_query("SET AUTOCOMMIT=1");
    }
    
    
    public function removeServer($serverName)
    {
        $this->removeServerConfig($serverName);
        
        $servers = new Servers();
        $json = $this->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers->removeServer($serverName);
        $json = $servers->toJson();
        error_log('JSON: '.$json);
        $this->setSystemSetting(self::SERVERS_KEY, $json);
    }


    #==================================================================================
    # Server Config methods
    #==================================================================================
    
    public function getServerConfig($serverName)
    {
        $serverConfig = new ServerConfig($serverName);
        $key = self::SERVER_CONFIG_KEY_PREFIX . $serverName;
        $setting = $this->getSystemSetting($key);
        $serverConfig->fromJson($setting);
        return $serverConfig;
    }
    
    public function setServerConfig($serverConfig)
    {
        $json = $serverConfig->toJson();
        $key = self::SERVER_CONFIG_KEY_PREFIX . $serverConfig->getName();
        $this->setSystemSetting($key, $json);
    }
    
    private function copyServerConfig($fromServerName, $toServerName)
    {
        $toServerConfig = $this->getServerConfig($fromServerName);
        $toServerConfig->setName($toServerName);
        $json = $toServerConfig->toJson();
        $key = self::SERVER_CONFIG_KEY_PREFIX . $toServerName;
        $this->setSystemSetting($key, $json);
    }
    
    public function renameServerConfig($serverName, $newServerName)
    {
        $this->copyServerConfig($serverName, $newServerName);
        $this->removeServerConfig($serverName);
    }
    
    
    public function removeServerConfig($serverName)
    {
        $key = self::SERVER_CONFIG_KEY_PREFIX . $serverName;
        $result = $this->removeSystemSetting($key);
        return $result;
    }


    #==================================================================================
    # Last run time methods
    #==================================================================================
    
    public function getLastRunTime()
    {
        $dateAndTime = $this->getSystemSetting(self::LAST_RUN_TIME_KEY);
        if (empty($dateAndTime)) {
            $dateAndTime = array(-1, -1);
        }
        $lastRunTime = explode(',', $dateAndTime);
        return $lastRunTime;
    }

    public function setLastRunTime($date, $time)
    {
        $lastRunTime = $date.','.$time;
        $this->setSystemSetting(self::LAST_RUN_TIME_KEY, $lastRunTime);
    }
    
    public function isLastRunTime($date, $time)
    {
        $lastRunTime = $this->getLastRunTime();
        return $lastRunTime[0] == $date && $lastRunTime[1] == $time;
    }




    public function renderAdminTabs($activeUrl = '')
    {
        $adminUrl = $this->getUrl('admin.php');
        $adminLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' Configure';

        $cronJobsUrl = $this->getUrl('cron_jobs.php');
        $cronJobsLabel = '<span class="glyphicon glyphicon-time" aria-hidden="true"></span>'
           .' Cron Detail';

        $manageUsersUrl = $this->getUrl('users.php');
        #$manageUsersLabel = '<span>Manage Users</span>';
        #$manageUsersLabel = '<span><img aria-hidden="true" src="/redcap/redcap_v8.5.11/Resources/images/users3.png">'
        $manageUsersLabel = '<span class="glyphicon glyphicon-user" aria-hidden="true"></span>'
           .' Manage Users</span>';

        $serversUrl = $this->getUrl('servers.php');
        $serversLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' ETL Servers';

        $serverConfigUrl = $this->getUrl('server_config.php');
        $serverConfigLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' ETL Server Config';

        $tabs = array();
        
        $tabs[$adminUrl]        = $adminLabel;
        $tabs[$cronJobsUrl]     = $cronJobsLabel;
        $tabs[$manageUsersUrl]  = $manageUsersLabel;
        $tabs[$serversUrl]      = $serversLabel;
        $tabs[$serverConfigUrl] = $serverConfigLabel;
        
        $this->renderTabs($tabs, $activeUrl);
    }

    /**
     * Renders the top-level tabs for the user interface.
     */
    public function renderUserTabs($activeUrl = '')
    {
        $listUrl = $this->getUrl('index.php');
        $listLabel = '<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span>'
           .' My ETL Configurations';

        #$addUrl = $this->getUrl('add.php');
        #$addLabel = '<span style="color: #008000;" class="glyphicon glyphicon-plus" aria-hidden="true"></span>'
        #   .' New Configuration';

        $configUrl = $this->getUrl('configure.php');
        $configLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' Configure';

        $runUrl = $this->getUrl('run.php');
        $runLabel = '<span style="color: #008000;" class="glyphicon glyphicon-play-circle" aria-hidden="true"></span>'
           .' Run';

        $scheduleUrl = $this->getUrl('schedule.php');
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
            echo "{$message}\n";
            echo "</div>\n";
        }
    }
    
        
    public function renderErrorMessageDiv($message)
    {
        if (!empty($message)) {
            echo '<div align="center" class="red" style="margin: 20px 0;">'."\n";
            echo '<img src="'.(APP_PATH_IMAGES.'exclamation.png').'">';
            echo "{$message}\n";
            echo "</div>\n";
        }
    }
}

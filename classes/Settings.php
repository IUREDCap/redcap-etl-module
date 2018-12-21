<?php

namespace IU\RedCapEtlModule;

class Settings
{
    const ADMIN_CONFIG_KEY         = 'admin-config';
    const SERVER_CONFIG_KEY_PREFIX = 'server-config:';
    const SERVERS_KEY              = 'servers';
    const USER_LIST_KEY            = 'user-list';
    const LAST_RUN_TIME_KEY        = 'last-run-time'; // for storing day and time of last run

    const USER_PROJECTS_KEY_PREFIX = 'user-projects:';  // appdend with username to make key
    
    const CONFIG_SESSION_KEY = 'redcap-etl-config';
    
    private $module;
    
    /** @var RedCapDb $db REDCap database object. */
    private $db;
    
    public function __construct($module, $db)
    {
        $this->module = $module;
        $this->db     = $db;
    }
    
    #----------------------------------------------------------
    # Users settings methods
    #----------------------------------------------------------
    
    public function getUsers()
    {
        // Note: only 1 database access, so don't need transaction option
        $userList = new UserList();
        $json = $this->module->getSystemSetting(self::USER_LIST_KEY);
        $userList->fromJson($json);
        $users = $userList->getUsers();
        return $users;
    }
    
    public function addUser($username, $transaction = true)
    {
        $commit = true;
        $userList = new UserList();
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $json = $this->module->getSystemSetting(self::USER_LIST_KEY);
        $userList->fromJson($json);
        $userList->addUser($username);
        $json = $userList->toJson();
        $this->module->setSystemSetting(self::USER_LIST_KEY, $json);
        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }

    #----------------------------------------------------------
    # UserInfo settings methods
    #----------------------------------------------------------
    
    /**
     * Gets the key for REDCap's external module settings table
     * for the specified username, or the current username,
     * if no username was specified.
     */
    private function getUserKey($username = USERID, $projectId = PROJECT_ID)
    {
        $key = 'user:'.$username;
        return $key;
    }


    public function getUserInfo($username = USERID, $projectId = PROJECT_ID)
    {
        $key = $this->getUserKey($username);
        $json = $this->module->getProjectSetting($key, $projectId);
        $userInfo = new UserInfo($username);
        $userInfo->fromJson($json);
        return $userInfo;
    }
    
    public function setUserInfo($userInfo, $username = USERID, $projectId = PROJECT_ID)
    {
        $key = $this->getUserKey($username);
        $json = $userInfo->toJson();
        $this->module->setProjectSetting($key, $json, $projectId);
    }

    /**
     * Gets the ETL configurations for the specified user and project.
     *
     * @param string $username the REDCap username.
     * @param int $projectId the REDCap project ID.
     *
     * @return array array of ETL configuration names for the specified
     *     username and project ID.
     */
    public function getUserConfigurationNames($username = USERID, $projectId = PROJECT_ID)
    {
        $userInfo = $this->getUserInfo($username, $projectId);
        $names = $userInfo->getConfigNames();
        return $names;
    }
    
    
    #-------------------------------------------------------------------
    # User ETL project methods
    #-------------------------------------------------------------------
    
        
    public function getUserEtlProjects($username = USERID)
    {
        $key = self::USER_PROJECTS_KEY_PREFIX . $username;
        $json = $this->module->getSystemSetting($key);
        $projects = json_decode($json, true);
        return $projects;
    }
    
    /**
     * Sets the projects to which a user has permission to use ETL.
     *
     * @param array $projects an array of REDCap project IDS
     *     for which the user has ETL permission.
     */
    public function setUserEtlProjects($username, $projects)
    {
        $key = self::USER_PROJECTS_KEY_PREFIX . $username;
        $json = json_encode($projects);
        $this->module->setSystemSetting($key, $json);
    }
    
    
    #-------------------------------------------------------------------
    # (ETL) Configuration methods
    #-------------------------------------------------------------------
    
        
    public function getConfigurationKey($name, $username = USERID)
    {
        $key = 'user:'.$username.';configuration:'.$name;
        return $key;
    }


    /**
     * Gets the specified configuration from the REDCap database.
     *
     * @param string $name the name of the configuration to get.
     * @return Configuration the specified configuration, or null if no
     *     configuration is found.
     */
    public function getConfiguration($name, $username = USERID, $projectId = PROJECT_ID)
    {
        $configuraion = null;
        $key = $this->getConfigurationKey($name, $username);

        $setting = $this->module->getProjectSetting($key, $projectId);
        $configValues = json_decode($setting, true);
        if (isset($configValues) && is_array($configValues)) {
            $configuration = new Configuration($configValues['name']);
            $configuration->set($configValues['properties']);
        }
        return $configuration;
    }
    
    
    /**
     * Set the specified configuration in the REDCap database.
     *
     * @param Configuration $configuration
     * @param string $username
     * @param string $projectId
     */
    public function setConfiguration($configuration, $username = USERID, $projectId = PROJECT_ID)
    {
        $key = $this->getConfigurationKey($configuration->getName(), $username);

        $json = json_encode($configuration);
        $this->module->setProjectSetting($key, $json, $projectId);
    }
    
    /**
     * Sets the schedule for a configuration.
     *
     * @param string $configName the name of the configuration to set.
     * @param string $server the name of the server to use for the cron schedule.
     * @param array $schedule array of schedule hours, indexed by day of week.
     */
    public function setConfigSchedule(
        $configName,
        $server,
        $schedule,
        $username = USERID,
        $projectId = PROJECT_ID,
        $transaction = true
    ) {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $configuration = $this->getConfiguration($configName, $username, $projectId);
        if (empty($configuration)) {
            $commit = false;
            $errorMessage = 'Configuration "'.$configName.'" not found for user '
                .$username.' and project ID '.$projectId.'.';
        }
        $configuration->setProperty(Configuration::CRON_SERVER, $server);
        $configuration->setProperty(Configuration::CRON_SCHEDULE, $schedule);
        $this->setConfiguration($configuration, $username, $projectId);
        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
        
        if (!empty($errorMessage)) {
            throw new \Exception($errorMessage);
        }
    }
    
    /**
     * Adds an ETL configuration for a user.
     *
     * @param string $name the name of the configuration.
     */
    public function addConfiguration($name, $username = USERID, $projectId = PROJECT_ID, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        try {
            # Add configuration entry for user
            $userInfo = $this->getUserInfo();
            if (!isset($userInfo)) {
                $userInfo = new UserInfo($username);
            }

            if (!$userInfo->hasConfigName($name)) {
                $userInfo->addConfigName($name);
                $json = $userInfo->toJson();
                $userKey = $this->getUserKey();
                $this->module->setProjectSetting($userKey, $json, $projectId);
            }
        
            # Add the actual configuration
            $key = $this->getConfigurationKey($name);
            $configuration = $this->module->getSystemSetting($key);
            if (!isset($configuration)) {
                $configuration = new Configuration($name);
                $jsonConfiguration = json_encode($configuration);
                $this->module->setProjectSetting($key, $jsonConfiguration, $projectId);
            }
        } catch (\Exception $exception) {
            $commit = false;
            $this->db->endTransaction($commit);
            throw $exception;
        }

        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }
    
    /**
     * Copy configuration (only supports copying from/to same
     * user and project).
     */
    public function copyConfiguration($fromConfigName, $toConfigName, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        try {
            #--------------------------------------------------------
            # Add the configuration name to the user's information
            #--------------------------------------------------------
            $userInfo = $this->getUserInfo();
            $userInfo->addConfigName($toConfigName);
            $json = $userInfo->toJson();
            $userKey = $this->getUserKey();
            $this->module->setProjectSetting($userKey, $json);
        
            #-----------------------------------------------------
            # Copy the actual configuration
            #-----------------------------------------------------
            $toConfig = $this->getConfiguration($fromConfigName);
            $toConfig->setName($toConfigName);
            $json = $toConfig->toJson();
            $key = $this->getConfigurationKey($toConfigName);
            $this->module->setProjectSetting($key, $json);
        } catch (\Exception $exception) {
            $commit = false;
            $this->db->endTransaction($commit);
            throw $exception;
        }
    
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }
    
    /**
     * Rename configuration (only supports rename from/to same
     * user and project).
     */
    public function renameConfiguration($configName, $newConfigName, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        try {
            $this->copyConfiguration($configName, $newConfigName, false);
            $this->removeConfiguration($configName, false);
        } catch (\Exception $exception) {
            $commit = false;
            $this->db->endTransaction($commit);
            throw $exception;
        }
        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }
    
    public function removeConfiguration($configName, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        #-----------------------------------------------------------
        # Remove the configuration name from the user's information
        #-----------------------------------------------------------
        $userInfo = $this->getUserInfo();
        if (isset($userInfo) && $userInfo->hasConfigName($configName)) {
            $userInfo->removeConfigName($configName);
            $json = $userInfo->toJson();
            $userKey = $this->getUserKey();
            $this->module->setProjectSetting($userKey, $json);
        }
        
        #------------------------------------------------
        # Remove the actual configuration
        #------------------------------------------------
        $key = $this->getConfigurationKey($configName);
        $this->module->removeProjectSetting($key);
                        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }

    #-------------------------------------------------------------------
    # Cron job methods
    #-------------------------------------------------------------------
     
    /**
     * Gets all the cron jobs (for all users and all projects).
     */
    public function getAllCronJobs($transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $cronJobs = array();
        foreach (range(0, 6) as $day) {
            $cronJobs[$day] = array();
            foreach (range(0, 23) as $hour) {
                $cronJobs[$day][$hour] = array();
            }
        }
        
        $usernames = $this->getUsers();
        foreach ($usernames as $username) {
            $etlProjects = $this->getUserEtlProjects($username);
            foreach ($etlProjects as $etlProject) {
                $user = $this->getUserInfo($username, $etlProject);
                $configNames = $user->getConfigNames();
                foreach ($configNames as $configName) {
                    $config = $this->getConfiguration($configName, $username, $etlProject);
                    if (isset($config)) {
                        $server = $config->getProperty(Configuration::CRON_SERVER);
                        $times  = $config->getProperty(Configuration::CRON_SCHEDULE);
                        for ($day = 0; $day < 7; $day++) {
                            $hour = $times[$day];
                            if (isset($hour)) {
                                $run = array(
                                    'username'  => $username,
                                    'projectId' => $etlProject,
                                    'config'    => $configName,
                                    'server'    => $server
                                );
                                array_push($cronJobs[$day][$hour], $run);
                            }
                        }
                    }
                }
            }
        }
                                        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
        
        return $cronJobs;
    }
    
    /**
     * Gets the cron jobs for the specified day (0 = Sunday, 1 = Monday, ...)
     * and time (0 = 12am - 1am, 1 = 1am - 2am, ..., 23 = 11pm - 12am).
     */
    public function getCronJobs($day, $time, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $cronJobs = array();
        
        $usernames = $this->getUsers();
        #\REDCap::logEvent('REDCap-ETL getCronJobs: usernames: '.print_r($usernames, true));
        
        foreach ($usernames as $username) {
            $etlProjects = $this->getUserEtlProjects($username);
            foreach ($etlProjects as $etlProject) {
                #\REDCap::logEvent('REDCap-ETL getCronJobs: username: '.$username);
                $user = $this->getUserInfo($username, $etlProject);
                $configNames = $user->getConfigNames();
                #\REDCap::logEvent('REDCap-ETL getCronJobs: configNames: '.print_r($configNames, true));
            
                foreach ($configNames as $configName) {
                    $config = $this->getConfiguration($configName, $username, $etlProject);
                    if (isset($config)) {
                        $server = $config->getProperty(Configuration::CRON_SERVER);
                        $times  = $config->getProperty(Configuration::CRON_SCHEDULE);
                    
                        if (isset($times) && is_array($times)) {
                            for ($cronDay = 0; $cronDay < 7; $cronDay++) {
                                $cronTime = $times[$cronDay];
                                if (isset($cronTime) && $time == $cronTime && $day == $cronDay) {
                                    $job = array(
                                        'username'  => $username,
                                        'projectId' => $etlProject,
                                        'config'    => $configName,
                                        'server'    => $server
                                    );
                                    array_push($cronJobs, $job);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
                        
        return $cronJobs;
    }


    #-------------------------------------------------------------------
    # Admin Config methods
    #-------------------------------------------------------------------

    public function getAdminConfig()
    {
        $adminConfig = new AdminConfig();
        $setting = $this->module->getSystemSetting(self::ADMIN_CONFIG_KEY);
        $adminConfig->fromJson($setting);
        return $adminConfig;
    }
    
    public function setAdminConfig($adminConfig)
    {
        $json = $adminConfig->toJson();
        $this->module->setSystemSetting(self::ADMIN_CONFIG_KEY, $json);
    }
    
    #-------------------------------------------------------------------
    # Server methods
    #-------------------------------------------------------------------
    
    public function getServers()
    {
        $servers = new Servers();
        $json = $this->module->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers = $servers->getServers();
        return $servers;
    }

    public function addServer($serverName, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        if (empty($serverName)) {
            $message = 'No server name specified.';
            throw new \Exception($message);
        } elseif ($this->serverConfigExists($serverName)) {
            $message = 'Server "'.$serverName.'" already exists.';
            throw new \Exception($message);
        }
        
        # Add the server to the list of configurations
        $servers = new Servers();
        $json = $this->module->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers->addServer($serverName);
        $json = $servers->toJson();
        $this->module->setSystemSetting(self::SERVERS_KEY, $json);
        
        # Add the server configuration
        $serverConfig = new ServerConfig($serverName);
        $this->setServerConfig($serverConfig);
        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }

    public function copyServer($fromServerName, $toServerName, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $copyException = null;
        try {
            $fromServer = $this->getServerConfig($fromServerName);
        
            $servers = new Servers();
            $json = $this->module->getSystemSetting(self::SERVERS_KEY, true);
            $servers->fromJson($json);
            $servers->addServer($toServerName, false);
            $json = $servers->toJson();
            $this->module->setSystemSetting(self::SERVERS_KEY, $json);
        
            $this->copyServerConfig($fromServerName, $toServerName, false);
        } catch (\Exception $exception) {
            $commit = false;
            $copyException = $exception;
        }
                                                
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
        
        if (isset($copyException)) {
            throw $copyException;
        }
    }
    
    public function renameServer($serverName, $newServerName, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';

        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $renameException = null;
        try {
            $server = $this->getServerConfig($serverName);
            $servers = new Servers();
            $json = $this->module->getSystemSetting(self::SERVERS_KEY, true);
            $servers->fromJson($json);
            $servers->addServer($newServerName, false);
            $servers->removeServer($serverName, false);
            $json = $servers->toJson();
            $this->module->setSystemSetting(self::SERVERS_KEY, $json);
        
            $this->renameServerConfig($serverName, $newServerName, false);
        } catch (\Exception $exception) {
            $commit = false;
            $renameException = $exception;
        }
                    
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
        
        if (isset($renameException)) {
            throw $renameException;
        }
    }
    
    /**
     * Removes the server from the REDCap database.
     */
    public function removeServer($serverName)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $this->removeServerConfig($serverName, false);
        
        $servers = new Servers();
        $json = $this->module->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers->removeServer($serverName, false);
        $json = $servers->toJson();
        $this->module->setSystemSetting(self::SERVERS_KEY, $json);
        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }


    #-------------------------------------------------------------------
    # Server Config methods
    #-------------------------------------------------------------------
    
    public function serverConfigExists($name)
    {
        $exists = false;
        $key = self::SERVER_CONFIG_KEY_PREFIX . $name;
        \REDCap::logEvent("KEY = {$key}");
        $setting = $this->module->getSystemSetting($key);
        \REDCap::logEvent("SETTING = {$setting}");
        if (!empty($setting)) {
            $exists = true;
        }
        return $exists;
    }
    
    public function getServerConfig($serverName)
    {
        $key = self::SERVER_CONFIG_KEY_PREFIX . $serverName;
        $setting = $this->module->getSystemSetting($key);
        if (empty($setting)) {
            $message = 'Server "'.$serverName.'" not found.';
            throw new \Exception($message);
        }
        $serverConfig = new ServerConfig($serverName);
        $serverConfig->fromJson($setting);
        return $serverConfig;
    }
    
    public function setServerConfig($serverConfig)
    {
        $json = $serverConfig->toJson();
        $key = self::SERVER_CONFIG_KEY_PREFIX . $serverConfig->getName();
        $this->module->setSystemSetting($key, $json);
    }
    
    private function copyServerConfig($fromServerName, $toServerName, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $toServerConfig = $this->getServerConfig($fromServerName);
        $toServerConfig->setName($toServerName);
        $json = $toServerConfig->toJson();
        $key = self::SERVER_CONFIG_KEY_PREFIX . $toServerName;
        $this->module->setSystemSetting($key, $json);
        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }
    
    public function renameServerConfig($serverName, $newServerName, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $this->copyServerConfig($serverName, $newServerName, false);
        $this->removeServerConfig($serverName, false);
        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }
    
    
    public function removeServerConfig($serverName, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $key = self::SERVER_CONFIG_KEY_PREFIX . $serverName;
        $result = $this->module->removeSystemSetting($key);
        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
        
        return $result;
    }


    #-------------------------------------------------------------------
    # Last run time methods
    #-------------------------------------------------------------------
    
    public function getLastRunTime()
    {
        $dateAndTime = $this->module->getSystemSetting(self::LAST_RUN_TIME_KEY);
        if (empty($dateAndTime)) {
            $dateAndTime = array(-1, -1);
        }
        $lastRunTime = explode(',', $dateAndTime);
        return $lastRunTime;
    }

    public function setLastRunTime($date, $time)
    {
        $lastRunTime = $date.','.$time;
        $this->module->setSystemSetting(self::LAST_RUN_TIME_KEY, $lastRunTime);
    }
    
    public function isLastRunTime($date, $time)
    {
        $lastRunTime = $this->getLastRunTime();
        return $lastRunTime[0] == $date && $lastRunTime[1] == $time;
    }
}

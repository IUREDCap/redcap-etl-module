<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

/**
 * Class for managing the storage and retrieval of external module settings stored in the REDCap database.
 */
class Settings
{
    const ADMIN_CONFIG_KEY         = 'admin-config';
    const SERVER_CONFIG_KEY_PREFIX = 'server-config:';
    const ETL_CONFIG_KEY           = 'configuration:';
    const PROJECT_INFO_KEY         = 'project-info';
    const SERVERS_KEY              = 'servers';
    const USER_LIST_KEY            = 'user-list';
    const LAST_RUN_TIME_KEY        = 'last-run-time'; // for storing day and time of last run

    const USER_PROJECTS_KEY_PREFIX = 'user-projects:';  // append with username to make key
    const USER_SERVERS_KEY_PREFIX  = 'user-servers:';
    const PRIVATE_SERVER_USERS_KEY_PREFIX  = 'private-server-users:';
    
    const WORKFLOWS_KEY               = 'workflows';

    const VERSION_KEY = 'version';
    
    const CONFIG_SESSION_KEY = 'redcap-etl-config';
    
    private $module;
    
    /** @var RedCapDb $db REDCap database object. */
    private $db;
    
    public function __construct($module, $db)
    {
        $this->module = $module;
        $this->db     = $db;
    }
    
    /**
     * Gets the REDCap-ETL external module version number.
     */
    public function getVersion()
    {
        $version = $this->module->getSystemSetting(self::VERSION_KEY);
        return $version;
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
    
    public function deleteUser($username, $transaction = true)
    {
        $commit = true;
        $userList = new UserList();
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        # Remove the user from the list of users
        $json = $this->module->getSystemSetting(self::USER_LIST_KEY);
        $userList->fromJson($json);
        $userList->deleteUser($username);
        $json = $userList->toJson();
        $this->module->setSystemSetting(self::USER_LIST_KEY, $json);

        # Remove the user's ETL project permissions
        $key = self::USER_PROJECTS_KEY_PREFIX . $username;
        $this->module->removeSystemSetting($key);

        # Remove the user's access to private servers
        $key = self::USER_SERVERS_KEY_PREFIX . $username;
        $this->module->removeSystemSetting($key);

        # Remove this user from any private-server list of allowed users
        $assignedPrivateServers = $this->getUserPrivateServerNames($username);
        foreach ($assignedPrivateServers as $serverName) {
            $this->removeUserFromPrivateServer($username, $serverName);
        }

        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }

    #----------------------------------------------------------
    # User allowable-servers methods
    #----------------------------------------------------------
    public function processUserPrivateServers($username, $userPrivateServerNames, $serversToCheck, $transaction = true)
    {
        $commit = true;
        if ($transaction) {
            $this->db->startTransaction();
        }

        ###UPDATE THE USER-LIST FOR SERVERS THAT THIS USER IS NO LONGER ALLOWED TO ACCESS
        if (!$serversToCheck) {
           #servers that this user was allowed to access before any changes were made
            $serversToCheck = $this->getUserPrivateServerNames($username);
        }

        #For each of the servers-to-check, see if access is still allowed for this user
        foreach ($serversToCheck as $serverName) {
           #if this server is not in the list of allowed servers for this user
           #then remove the username from the allowed-user list for the server if it's there
            if (!in_array($serverName, $userPrivateServerNames)) {
                $this->removeUserFromPrivateServer($username, $serverName);
            }
        }


        ###UPDATE THE USER-LIST FOR SERVERS THAT THIS USER IS ALLOWED TO ACCESS
        foreach ($userPrivateServerNames as $serverName) {
            $privateServerUsers = $this->getPrivateServerUsers($serverName);
            if (!in_array($username, $privateServerUsers)) {
                $privateServerUsers[] = $username;
                $this->setPrivateServerUsers($serverName, $privateServerUsers);
            }
        }


        ###UPDATE THE SERVER-LIST OF ALLOWED SERVERS FOR THIS USER
        $this->setUserPrivateServerNames($username, $userPrivateServerNames);

        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }

    public function processPrivateServerUsers(
        $serverName,
        $removeUsernames,
        $transaction = true
    ) {
        if ($removeUsernames) {
            # find out who is currently allowed to access this server
            $currentUsernames = $this->getPrivateServerUsers($serverName);

            $commit = true;
            if ($transaction) {
                $this->db->startTransaction();
            }

            # loop through the users to remove
            foreach ($removeUsernames as $username) {
                # get the server-list for this user
                $userPrivateServerNames = $this->getUserPrivateServerNames($username);

                if (($serverKey = array_search($serverName, $userPrivateServerNames)) !== false) {
                    #remove this server from this user's list of allowed servers
                    unset($userPrivateServerNames[$serverKey]);

                    #update the server-list for this user
                    $this->setUserPrivateServerNames($username, $userPrivateServerNames);
                }

                #remove this user from this server's list of allowed users
                if (($userKey = array_search($username, $currentUsernames)) !== false) {
                    unset($currentUsernames[$userKey]);
                }
            }
        
            #update the user-list for this server
            $this->setPrivateServerUsers($serverName, $currentUsernames);

            if ($transaction) {
                $this->db->endTransaction($commit);
            }
        }
    }

    public function removeUserFromPrivateServer($username, $serverName)
    {
        #get the current allowed-users for this server
        $privateServerUsers = $this->getPrivateServerUsers($serverName);

        #find the username in the array, remove it, and save the updated user list
        if (($key = array_search($username, $privateServerUsers)) !== false) {
            unset($privateServerUsers[$key]);
            $this->setPrivateServerUsers($serverName, $privateServerUsers);
        }
    }

    public function setPrivateServerUsers($serverName, $usernames)
    {
        $key = self::PRIVATE_SERVER_USERS_KEY_PREFIX . $serverName;
        $json = json_encode($usernames);
        $this->module->setSystemSetting($key, $json);
    }

    public function getPrivateServerUsers($serverName)
    {
        $key = self::PRIVATE_SERVER_USERS_KEY_PREFIX . $serverName;
        $json = $this->module->getSystemSetting($key);
        $usernames = json_decode($json, true);
        return $usernames;
    }

   /**
     * Sets the servers to which a user has permission to use ETL.
     *
     * @param array $serverNames an array of REDCap server names
     *     for which the user has ETL permission.
     */
    public function setUserPrivateServerNames($username, $serverNames)
    {
        $key = self::USER_SERVERS_KEY_PREFIX . $username;
        $json = json_encode($serverNames);
        $this->module->setSystemSetting($key, $json);
    }

    public function getUserPrivateServerNames($username = USERID)
    {
        $key = self::USER_SERVERS_KEY_PREFIX . $username;
        $json = $this->module->getSystemSetting($key);
        $userPrivateServerNames = json_decode($json, true);
        return $userPrivateServerNames;
    }
    
    #----------------------------------------------------------
    # ProjectInfo settings methods
    #----------------------------------------------------------
    
    public function getProjectInfo($projectId = PROJECT_ID)
    {
        $key = self::PROJECT_INFO_KEY;
        $json = $this->module->getProjectSetting($key, $projectId);
        $projectInfo = new ProjectInfo();
        $projectInfo->fromJson($json);
        return $projectInfo;
    }
    
    /**
     * Gets the ETL configurations for the specified project.
     *
     * @param int $projectId the REDCap project ID.
     *
     * @return array array of ETL configuration names for the specified
     *     username and project ID.
     */
    public function getConfigurationNames($projectId = PROJECT_ID)
    {
        $projectInfo = $this->getProjectInfo($projectId);
        $names = $projectInfo->getConfigNames();
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
    
    #/**
    # * Indicates if the project that has the specified project ID
    # * has a user who has permission to run ETL.
    # */
    #public function hasEtlUser($projectId)
    #{
    #    # Get set of ETL project IDs
    #    $projectIds = array();
    #    $usernames = $this->getUsers();
    #    foreach ($usernames as $username) {
    #        $etlProjects = $this->getUserEtlProjects($username);
    #        foreach ($etlProjects as $etlProject) {
    #            $projectIds[$etlProject] = 1;
    #        }
    #    }
    #
    #    return array_key_exists($projectId, $projectIds);
    #}
    
    
    #-------------------------------------------------------------------
    # (ETL) Configuration methods
    #-------------------------------------------------------------------
    
        
    public function getConfigurationKey($name)
    {
        $key = self::ETL_CONFIG_KEY . $name;
        return $key;
    }


    /**
     * Gets the specified configuration from the REDCap database.
     *
     * @param string $name the name of the configuration to get.
     * @return Configuration the specified configuration, or null if no
     *     configuration is found.
     */
    public function getConfiguration($name, $projectId = PROJECT_ID)
    {
        $configuraion = null;
        $key = $this->getConfigurationKey($name);
        
        $setting = $this->module->getProjectSetting($key, $projectId);
        $configValues = json_decode($setting, true);

        if (isset($configValues) && is_array($configValues)) {
            $projectIdUpdated = false;

            if (!empty($projectId) && $configValues['projectId'] !== $projectId) {
                #-------------------------------------------------------------------------
                # if the project ID in the stored configuration is wrong (i.e., it
                # doesn't match the current user's project ID) reset it, and clear
                # the API token, API token user and cron schedule. This can happen
                # when a REDCap project that has ETL configurations is copied (the
                # project IDs stored in the configurations are not updated with the
                # copy).
                #-------------------------------------------------------------------------
                $configValues['projectId'] = $projectId;
                $configValues['properties'][Configuration::DATA_SOURCE_API_TOKEN] = '';
                $configValues['properties'][Configuration::API_TOKEN_USERNAME]    = '';
                $configValues['properties'][Configuration::CRON_SCHEDULE]         = '';
                $projectIdUpdated = true;
            }

            $configuration = new Configuration(
                $configValues['name'],
                $configValues['username'],
                $configValues['projectId']
            );
            $configuration->set($configValues['properties']);

            if ($projectIdUpdated) {
                # If the project ID was updated, then save the updated configuration
                $this->setConfiguration($configuration, $username, $projectId);
            }
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
        $key = $this->getConfigurationKey($configuration->getName());

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
        
        $configuration = $this->getConfiguration($configName, $projectId);
        if (empty($configuration)) {
            $commit = false;
            $errorMessage = 'Configuration "' . $configName . '" not found for user '
                . $username . ' and project ID ' . $projectId . '.';
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
    public function addConfiguration(
        $name,
        $username = USERID,
        $projectId = PROJECT_ID,
        $dataExportRight = 0,
        $transaction = true
    ) {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        try {
            # Add configuration entry for project
            $projectInfo = $this->getProjectInfo();
            if (!isset($projectInfo)) {
                $projectInfo = new ProjectInfo();
            }

            if (!$projectInfo->hasConfigName($name)) {
                $projectInfo->addConfigName($name);
                $json = $projectInfo->toJson();
                $projectKey = self::PROJECT_INFO_KEY;
                $this->module->setProjectSetting($projectKey, $json, $projectId);
            }
        
            # Add the actual configuration
            $key = $this->getConfigurationKey($name);
            $configuration = $this->module->getProjectSetting($key);
            if (isset($configuration)) {
                throw new \Exception('Configuration "' . $name . '" already exists.');
            }

            $configuration = new Configuration($name);
            $configuration->setDataExportRight($dataExportRight);
            $jsonConfiguration = json_encode($configuration);
            $this->module->setProjectSetting($key, $jsonConfiguration, $projectId);
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
    public function copyConfiguration($fromConfigName, $toConfigName, $toExportRight = null, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        try {
            #--------------------------------------------------------
            # Add the configuration name to the projects's information
            #--------------------------------------------------------
            $projectInfo = $this->getProjectInfo();
            $projectInfo->addConfigName($toConfigName);
            $json = $projectInfo->toJson();
            $projectKey = self::PROJECT_INFO_KEY;
            $this->module->setProjectSetting($projectKey, $json);
        
            #-----------------------------------------------------
            # Copy the actual configuration
            #-----------------------------------------------------
            $toConfig = $this->getConfiguration($fromConfigName);
            $toConfig->setName($toConfigName);
            if (isset($toExportRight)) {
                $toConfig->setDataExportRight($toExportRight);
            }
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
            $this->copyConfiguration($configName, $newConfigName, null, false);
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
        
        #-------------------------------------------------------------
        # Remove the configuration name from the project's information
        #-------------------------------------------------------------
        $projectInfo = $this->getProjectInfo();
        if (isset($projectInfo) && $projectInfo->hasConfigName($configName)) {
            $projectInfo->removeConfigName($configName);
            $json = $projectInfo->toJson();
            $projectKey = self::PROJECT_INFO_KEY;
            $this->module->setProjectSetting($projectKey, $json);
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

        # Get all ETL configuration settings
        $allEtlConfigSettings = $this->db->getEtlConfigurationsSettings($this->module);
        foreach ($allEtlConfigSettings as $etlConfigSettings) {
            $projectId  = $etlConfigSettings['project_id'];
            $configJson = $etlConfigSettings['value'];

            $configValues = json_decode($configJson, true);
            $config = null;
            if (isset($configValues) && is_array($configValues)) {
                $configName = $configValues['name'];
                $username   = $configValues['username'];
                
                $config = new Configuration(
                    $configName,
                    $username,
                    $projectId
                );
                $config->set($configValues['properties']);
            }

            if (isset($config)) {
                $server = $config->getProperty(Configuration::CRON_SERVER);
                $times  = $config->getProperty(Configuration::CRON_SCHEDULE);
                    
                for ($day = 0; $day < 7; $day++) {
                    $hour = $times[$day];
                    if (isset($hour)) {
                        $run = array(
                            'username'  => $username,
                            'projectId' => $projectId,
                            'config'    => $configName,
                            'server'    => $server
                        );
                        array_push($cronJobs[$day][$hour], $run);
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
    public function getTaskCronJobs($day, $time, $transaction = true)
    {
        $commit = true;
        $errorMessage = '';
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $cronJobs = array();
                
        # Get all ETL configuration settings
        $allEtlConfigSettings = $this->db->getEtlConfigurationsSettings($this->module);

        foreach ($allEtlConfigSettings as $etlConfigSettings) {
            $projectId  = $etlConfigSettings['project_id'];
            $configJson = $etlConfigSettings['value'];

            $configValues = json_decode($configJson, true);
            $config = null;
            if (isset($configValues) && is_array($configValues)) {
                $configName = $configValues['name'];
                $username   = $configValues['username'];
                
                $config = new Configuration(
                    $configName,
                    $username,
                    $projectId
                );
                $config->set($configValues['properties']);
            }

            if (isset($config)) {
                $server = $config->getProperty(Configuration::CRON_SERVER);
                $times  = $config->getProperty(Configuration::CRON_SCHEDULE);
                    
                if (isset($times) && is_array($times)) {
                    for ($cronDay = 0; $cronDay < 7; $cronDay++) {
                        $cronTime = $times[$cronDay];
                        if (isset($cronTime) && $cronTime != "" && $time == $cronTime && $day == $cronDay) {
                            $job = array(
                                'username'  => $username,
                                'projectId' => $projectId,
                                'config'    => $configName,
                                'server'    => $server
                            );
                            array_push($cronJobs, $job);
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
            $message = 'Server "' . $serverName . '" already exists.';
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
        $setting = $this->module->getSystemSetting($key);
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
            # If the server configuration is NOT found then
            # create it if it is the embedded server
            # Else, throw an exception
            if (strcmp($serverName, ServerConfig::EMBEDDED_SERVER_NAME) === 0) {
                $serverConfig = new ServerConfig($serverName);
                $serverConfig->setIsActive(true);
                if (SUPER_USER) {
                    # If admin, then add embedded server to system settings
                    # (non-admin users do not have permission to set system
                    # settings, and attempting to do so causes a permission error)
                    $this->setServerConfig($serverConfig);
                }
            } else {
                $message = 'Server "' . $serverName . '" not found.';
                throw new \Exception($message);
            }
        } else {
            $serverConfig = new ServerConfig($serverName);
            $serverConfig->fromJson($setting);
        }
        
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
    
    /**
     * Gets the last time that the REDCap-ETL cron jobs were run
     */
    public function getLastRunTime()
    {
        $lastRunTime = null;
        $dateAndTime = $this->module->getSystemSetting(self::LAST_RUN_TIME_KEY);
        if (!empty($dateAndTime)) {
            $lastRunTime = explode(',', $dateAndTime);
        }
        return $lastRunTime;
    }

    public function setLastRunTime($date, $hour, $minutes)
    {
        $lastRunTime = $date . ',' . $hour . ',' . $minutes;
        $this->module->setSystemSetting(self::LAST_RUN_TIME_KEY, $lastRunTime);
    }
    
    public function isLastRunTime($date, $hour)
    {
        $lastRunTime = $this->getLastRunTime();
        return $lastRunTime[0] == $date && $lastRunTime[1] == $hour;
    }


    #--------------------------------------------------------------
    # Help settings methods, for custom, site-specific, help
    #--------------------------------------------------------------

    public function getHelpSetting($topic)
    {
        $key = Help::HELP_SETTING_PREFIX . $topic;
        $helpSetting = $this->module->getSystemSetting($key);
        if (empty($helpSetting)) {
            $helpSetting = Help::DEFAULT_TEXT;
        }
        return $helpSetting;
    }
    
    public function setHelpSetting($topic, $setting)
    {
        $key = Help::HELP_SETTING_PREFIX . $topic;
        $this->module->setSystemSetting($key, $setting);
    }

    public function getCustomHelp($topic)
    {
        $key = Help::HELP_TEXT_PREFIX . $topic;
        $customHelp = $this->module->getSystemSetting($key);
        return $customHelp;
    }
    
    public function setCustomHelp($topic, $help)
    {
        $key = Help::HELP_TEXT_PREFIX . $topic;
        $this->module->setSystemSetting($key, $help);
    }

    #-------------------------------------------------------------------
    # Workflow methods
    #-------------------------------------------------------------------

    public function addWorkflow(
        $workflowName,
        $username = USERID,
        $projectId = PROJECT_ID,
        $dataExportRight = 0,
        $transaction = true
    ) {
        $commit = true;
        $message = '';

        if ($transaction) {
            $this->db->startTransaction();
        }

        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);

        if (empty($workflowName) && $workflowName !== '0') {
            $message = 'When adding new workflow, no workflow name specified.';
            throw new \Exception($message);
        } elseif ($workflows->workflowExists($workflowName)) {
            $message = 'A workflow with the name "' . $workflowName . '" already exists.';
            throw new \Exception($message);
        }

        $workflows->createWorkflow($workflowName, $username);
        $workflows->addProjectToWorkflow($workflowName, $projectId, null);
        $json = $workflows->toJson();

        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);
      
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }

    public function addProjectToWorkflow(
        $workflowName,
        $project,
        $username = USERID
    ) {
        $message = '';

        $this->db->startTransaction();

        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);

        $projectId = $project["project_id"];

        $workflows->addProjectToWorkflow($workflowName, $projectId, $username);
        $json = $workflows->toJson();

        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);

        $commit = true;
        $this->db->endTransaction($commit);
    }

    public function deleteTaskFromWorkflow(
        $workflowName,
        $taskKey,
        $username = USERID
    ) {
        $this->db->startTransaction();

        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);

        $workflows->deleteTaskFromWorkflow($workflowName, $taskKey, $username);
        $json = $workflows->toJson();

        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);

        $commit = true;
        $this->db->endTransaction($commit);
    }

    public function getWorkflows()
    {
        $workflows = new Workflow();
        $key = self::WORKFLOWS_KEY;
        $json = $this->module->getSystemSetting($key);
        $workflows->fromJson($json);

        return $workflows->getWorkflows();
    }

    public function getWorkflow($workflowName)
    {
        $workflows = new Workflow();
        $key = self::WORKFLOWS_KEY;
        $json = $this->module->getSystemSetting($key);
        $workflows->fromJson($json);

        return $workflows->getWorkflow($workflowName);
    }

    public function getWorkflowTasks($workflowName)
    {
        $workflows = new Workflow();
        $key = self::WORKFLOWS_KEY;
        $json = $this->module->getSystemSetting($key);
        $workflows->fromJson($json);

        return $workflows->getWorkflowTasks($workflowName);
    }

    public function getWorkflowStatus($workflowName)
    {
        $workflows = new Workflow();
        $key = self::WORKFLOWS_KEY;
        $json = $this->module->getSystemSetting($key);
        $workflows->fromJson($json);

        return $workflows->getWorkflowStatus($workflowName);
    }

    public function getProjectAvailableWorkflows(
        $projectId = PROJECT_ID,
        $excludeIncomplete = false
    ) {
        $workflowsObject = new Workflow();
        $key = self::WORKFLOWS_KEY;
        $json = $this->module->getSystemSetting($key);
        $workflowsObject->fromJson($json);
        $workflows = $workflowsObject->getWorkflows();
        $projectWorkflows = array();
        foreach ($workflows as $workflowName => $workflow) {
            if ($workflow['metadata']['workflowStatus'] !== 'Removed') {
                if (in_array($projectId, array_column($workflow, 'projectId'))) {
                    if ($excludeIncomplete) {
                        if ($workflow['metadata']['workflowStatus'] !== 'Incomplete') {
                            $projectWorkflows[] = $workflowName;
                        }
                    } else {
                        $projectWorkflows[] = $workflowName;
                    }
                }
            }
        }

        return $projectWorkflows;
    }

    /**
     * Deletes a user's workflow.
     */
    public function deleteUserWorkflow($workflowName, $username, $transaction = true)
    {
        $commit = true;

        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);

        # Change the workflow status to 'removed'
        $workflows->removeWorkflow($workflowName, $username);

        $json = $workflows->toJson();

        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);

        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }

    public function deleteWorkflow($workflowName)
    {
        $this->db->startTransaction();

        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);

        $workflows->deleteWorkflow($workflowName);

        $json = $workflows->toJson();
        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);

        $commit = true;
        $this->db->endTransaction($commit);
    }

    public function reinstateWorkflow($workflowName, $username)
    {
        $this->db->startTransaction();
        
        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);
        $workflows->reinstateWorkflow($workflowName, $username);
        $json = $workflows->toJson();
        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);

        $commit = true;
        $this->db->endTransaction($commit);
    }

    public function copyWorkflow(
        $fromWorkflowName,
        $toWorkflowName,
        $username,
        $toExportRight = null,
        $transaction = true
    ) {

        if ($fromWorkflowName == $toWorkflowName) {
            $message = 'The new workflow name must be different from the existing workflow name.';
            throw new \Exception($message);
        }

        $commit = true;
        
        if ($transaction) {
            $this->db->startTransaction();
        }

        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);

        $workflows->copyWorkflow($fromWorkflowName, $toWorkflowName, $username);

        $json = $workflows->toJson();
        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);

        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }

    public function renameWorkflow($workflowName, $newWorkflowName, $username, $transaction = true)
    {
        $commit = true;
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        try {
            $this->copyWorkflow($workflowName, $newWorkflowName, $username, null, false);
            $this->deleteWorkflow($workflowName, false);
        } catch (\Exception $exception) {
            $commit = false;
            $this->db->endTransaction($commit);
            throw $exception;
        }
        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }

    public function moveWorkflowTask($workflowName, $direction, $moveTaskKey)
    {
        $message = 'In moving workflow task, ';
        if (empty($workflowName) && $workflowName !== '0') {
            $message .= 'no workflow name was specified.';
            throw new \Exception($message);
        } elseif (empty($direction)) {
            $message .= 'there was no specification to move the task up or down.';
            throw new \Exception($message);
        } elseif (empty($moveTaskKey) && $moveTaskKey != 0) {
            $message .= 'no project/task was specified.';
            throw new \Exception($message);
        }

        $this->db->startTransaction();

        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);
        $tasks = $workflows->getWorkflowTasks($workflowName);
        $keys = array_keys($tasks);
        $keysIndex = array_search($moveTaskKey, $keys);
        $numberOfTasks = count($tasks);

        if ($direction === 'up') {
            if ($keysIndex === 0) {
               #assign the sequence for the task being moved to be the last in the sequence
                $tasks[$moveTaskKey]['taskSequenceNumber'] = $numberOfTasks;

               #assign the sequence for the task being displaced
               #$switchTaskKey = key(array_slice($tasks, -1, 1, true));

               #move all of the others sequences down by one
                foreach ($tasks as $key => $task) {
                    if ($key != $moveTaskKey) {
                        --$tasks[$key]['taskSequenceNumber'];
                    }
                }
            } else {
               #assign the sequence for the task being moved
                --$tasks[$moveTaskKey]['taskSequenceNumber'];

               #assign the sequence for the task being displaced
                $switchPosition = $keysIndex - 1;
                if (isset($keys[$switchPosition])) {
                    $switchTaskKey = $keys[$switchPosition];
                    ++$tasks[$switchTaskKey]['taskSequenceNumber'];
                }
            }
        } elseif ($direction === 'down') {
            $lastIndex = $numberOfTasks - 1;
            if ($keysIndex === $lastIndex) {
               #assign the sequence for the task being moved to be the first in the sequence
                $tasks[$moveTaskKey]['taskSequenceNumber'] = 1;

               #move all of the others sequences up by one
                foreach ($tasks as $key => $task) {
                    if ($key != $moveTaskKey) {
                        ++$tasks[$key]['taskSequenceNumber'];
                    }
                }
            } else {
               #assign the sequence for the task being moved
                ++$tasks[$moveTaskKey]['taskSequenceNumber'];

               #assign the sequence for the task being displaced
                $switchPosition = $keysIndex + 1;
                if (isset($keys[$switchPosition])) {
                    $switchTaskKey = $keys[$switchPosition];
                    --$tasks[$switchTaskKey]['taskSequenceNumber'];
                }
            }
        }

        #make sure the task sequences in sequential order
        $workflows->sequenceWorkflowTasks($workflowName, $tasks, $username);

        $json = $workflows->toJson();
        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);

        $commit = true;
        $this->db->endTransaction($commit);
    }

    public function renameWorkflowTask(
        $workflowName,
        $taskKey,
        $newTaskName,
        $projectId,
        $username
    ) {
        $this->db->startTransaction();

        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);

        $workflows->renameWorkflowTask($workflowName, $taskKey, $newTaskName, $projectId, $username);
        $json = $workflows->toJson();

        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);

        $commit = true;
        $this->db->endTransaction($commit);
    }

    public function assignWorkflowTaskEtlConfig(
        $workflowName,
        $projectId,
        $taskKey,
        $etlConfig,
        $username
    ) {
        $this->db->startTransaction();

        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);

        $workflows->assignWorkflowTaskEtlConfig($workflowName, $projectId, $taskKey, $etlConfig, $username);
        $json = $workflows->toJson();

        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);

        $commit = true;
        $this->db->endTransaction($commit);
    }

    public function getWorkflowGlobalProperties($workflowName)
    {
        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);
        $properties = $workflows->getWorkflowGlobalProperties($workflowName);
        return $properties;
    }
    
    public function getWorkflowGlobalConfiguration($workflowName)
    {
        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);
        $configValues = $workflows->getWorkflowGlobalProperties($workflowName);
        $configuration = new Configuration($workflowName, null, null);

        if ($configValues) {
            $configuration->set($configValues, true);
        } else {
            $initialize = true;
            $configValues = $configuration->getGlobalProperties($initialize);
            $isWorkflow = true;
            $configuration->set($configValues, $isWorkflow);
        }
        return $configuration;
    }
    
    public function setWorkflowGlobalProperties($workflowName, $properties, $username)
    {
        $this->db->startTransaction();

        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);
        $workflows->setGlobalProperties($workflowName, $properties, $username);
        $json = json_encode($workflows);
        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);

        $commit = true;
        $this->db->endTransaction($commit);
    }

    public function setWorkflowSchedule($workflowName, $server, $schedule, $username)
    {
        $this->db->startTransaction();

        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);
        $workflows->setCronSchedule($workflowName, $server, $schedule, $username);
        $json = json_encode($workflows);
        $this->module->setSystemSetting(self::WORKFLOWS_KEY, $json);
        
        $commit = true;
        $this->db->endTransaction($commit);
    }
    
    public function getWorkflowSchedule($workflowName)
    {
        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);

        $cron = $workflows->getCronSchedule($workflowName);

        return $cron;
    }

    public function getWorkflowCronJobs($day, $time)
    {
        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);
        $cronJobs = $workflows->getCronJobs($day, $time);
        return $cronJobs;
    }
    
    public function hasPermissionsForAllTasks($workflowName, $username = USERID)
    {
        $hasPermissions = false;
        $userProjects = $this->db->getUserProjects($username);
        $userProjectIds = array_column($userProjects, 'project_id');

        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);
        $tasks = $workflows->getWorkflowTasks($workflowName);
        $workflowProjectIds = array_column($tasks, 'projectId');
        $numWorkflowProjects = count($workflowProjectIds);

        $commonProjectIds = array_intersect($userProjectIds, $workflowProjectIds);
        $numCommonProjectIds = count($commonProjectIds);

        if ($numWorkflowProjects === $numCommonProjectIds) {
            $hasPermissions = true;
        }
        return $hasPermissions;
    }

    public function getAllProjectTasksInAllWorkflows($projectId)
    {
        $workflows = new Workflow();
        $json = $this->module->getSystemSetting(self::WORKFLOWS_KEY);
        $workflows->fromJson($json);
        $excludeIncomplete = false;
        $projectAvailableWorkflows = $this->getProjectAvailableWorkflows($projectId, $excludeIncomplete);
        return $workflows->getAllProjectTasksInAllWorkflows($projectId, $projectAvailableWorkflows);
    }

/*
    public function setWorkflows($workflows)
    {
        $key = self::WORKFLOWS_KEY;

        $json = json_encode($workflows);
        # print "=== SSSSS.Y    in Settings.php, setWorkflows, ABOUT TO WRITE JSON, json for all workflows is : ";
        # print_r($json);

        $this->module->setSystemSetting($key, $json);
    }

    public function setProjectWorkflows($workflows, $projectId = PROJECT_ID)
    {
        $key = self::PROJECT_WORKFLOWS_KEY_PREFIX . $projectId;
        $json = json_encode($workflows);
        #print "====SSSSS.Y    in Settings.php, setProjectWorkflows, ABOUT TO WRITE JSON, json for all workflows is : ";
        #print_r($json);

        $this->module->setSystemSetting($key, $json);
    } */
}

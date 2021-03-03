<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

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
    const ADMIN_INFO_PAGE    = 'web/admin/info.php';
    
    const HELP_LIST_PAGE     = 'web/admin/help_list.php';
    const HELP_EDIT_PAGE     = 'web/admin/help_edit.php';

    const LOG_PAGE           = 'web/admin/log.php';
            
    const USER_ETL_CONFIG_PAGE  = 'web/configure.php';

    const WORKFLOWS_PAGE     = 'web/workflows.php';
    
    # REDCap event log constants
    const RUN_LOG_ACTION    = 'REDCap-ETL Export';
    const CHANGE_LOG_ACTION = 'REDCap-ETL Change';
    const LOG_EVENT         = -1;
    
    # REDCap external module log constants
    const ETL_CRON        = 'ETL cron';
    const ETL_CRON_JOB    = 'ETL cron job';
    const ETL_RUN         = 'ETL run';
    const ETL_RUN_DETAILS = 'ETL run details';
     
    # Access/permission errors
    const CSRF_ERROR                  = 1;   # (Possible) Cross-Site Request Forgery Error
    const USER_RIGHTS_ERROR           = 2;
    const NO_ETL_PROJECT_PERMISSION   = 3;
    const NO_CONFIGURATION_PERMISSION = 4;
    
    private $db;
    private $settings;
    private $moduleLog;

    private $changeLogAction;

    public function __construct()
    {
        $this->db = new RedCapDb();
        $this->settings = new Settings($this, $this->db);
        $this->moduleLog = new ModuleLog($this);
        parent::__construct();
    }


    /**
     * Returns REDCap user rights for the current project.
     *
     * @return array a map from username to an map of rights for the current project.
     */
    public function getUserRights()
    {
        $userId = USERID;
        $rights = \REDCap::getUserRights($userId)[$userId];
        return $rights;
    }
    
    /**
     * Gets the data export right for the current user. If the current
     * user is an admin 'Full data set' export permission will be
     * returned, otherwise, the REDCap user data exports user right
     * value for the current user will be returned.
     */
    public function getDataExportRight()
    {
        $dataExportRight = 0;  // No data access
        if ($this->isSuperUser()) {
            $dataExportRight = 1; // Full data access
        } else {
            $rights = $this->getUserRights();
            $dataExportRight = $rights['data_export_tool'];
        }
        return $dataExportRight;
    }
    
    /**
     * Cron method that is called by REDCap as configured in the
     * config.json file for this module.
     */
    public function cron()
    {
        #\REDCap::logEvent('REDCap-ETL cron() start');
            
        $now = new \DateTime();
        $day     = $now->format('w');  // 0-6 (day of week; Sunday = 0)
        $hour    = $now->format('G');  // 0-23 (24-hour format without leading zeroes)
        $minutes = $now->format('i');
        $date    = $now->format('Y-m-d');
        
        if ($this->isLastRunTime($date, $hour)) {
            ; # Cron jobs for this time were already processed
            #\REDCap::logEvent('REDCap-ETL cron - cron jobs already processed.');
        } else {
            # Set the last run time processed to this time, so that it won't be processed again
            $this->setLastRunTime($date, $hour, $minutes);

            $this->runCronJobs($day, $hour);
        }
    }

    /**
     * Runs the cron jobs (if any, for the specified day and hour).
     */
    public function runCronJobs($day, $hour)
    {
        $cronJobs = $this->getCronJobs($day, $hour);
        
        $cronJobsRunLogId = $this->moduleLog->logCronJobsRun(count($cronJobs), $day, $hour);

        $pid = -1;   # process ID

        foreach ($cronJobs as $cronJob) {
            try {
                $username   = $cronJob['username'];
                $projectId  = $cronJob['projectId'];
                $serverName = $cronJob['server'];
                $configName = $cronJob['config'];
            
                $details = '';
                $details .= "project ID: {$projectId}\n";
                $details .= "configuration: {$configName}\n";
                $details .= "server: {$serverName}\n";
                $details .= "cron: yes\n";
            
                $sql    = null;
                $record = null;
                $event  = self::LOG_EVENT;

                $isCronJob = true;

                $cronJobLogId = $this->moduleLog->logCronJob($projectId, $serverName, $configName, $cronJobsRunLogId);

                if (strcmp($serverName, ServerConfig::EMBEDDED_SERVER_NAME) === 0) {
                    # Running on the embedded server
                    #if (function_exists('pcntl_fork') && function_exists('pcntl_wait')) {
                    #    $pid = pcntl_fork();
                    #    if ($pid === -1) {
                    #        # The fork was unsuccessful (and this is the only thread)
                    #        $this->run($configName, $serverName, $isCronJob, $projectId);
                    #    } elseif ($pid === 0) {
                    #        # The fork was successful and this is the child process,
                    #        $this->run($configName, $serverName, $isCronJob, $projectId);
                    #        exit(0);
                    #    } else {
                    #        ; # the fork worked, and this is the parent process
                    #    }
                    #} else {
                    # Forking not supported; run serially
                       $this->run($configName, $serverName, $isCronJob, $projectId, $cronJobLogId, $day, $hour);
                    #}
                } else {
                    $this->run($configName, $serverName, $isCronJob, $projectId, $cronJobLogId, $day, $hour);
                }
            } catch (\Exception $exception) {
                $details = "Cron job failed\n".$details.'error: '.$exception->getMessage();
                \REDCap::logEvent(self::RUN_LOG_ACTION, $details, $sql, $record, $event, $projectId);
            }
        }  # End foreach cron job
                    
        # If forking is supported, wait for child processes (if any))
        $status = 0;
        #if (function_exists('pcntl_fork') && function_exists('pcntl_wait')) {
        #    while (pcntl_wait($status) != -1) {
        #        ; // Wait for all child processes to finish
        #    }
        #}
    }

    
    /**
     * Runs an ETL job. Top-level method for running an ETL job that all code should call,
     * so that there is one place to do REDCap authorization checks and logging.
     *
     * @param string $configName the name of the REDCap-ETL configuration to run
     * @param string $serverName name of server to run on
     * @param boolean $isCronJon indicates whether this is being called from a cron job or not.
     * @param int $conJobLogId the log ID of the cron job log entry
     *
     * @return string the status of the run.
     */
    public function run(
        $configName,
        $serverName,
        $isCronJob = false,
        $projectId = PROJECT_ID,
        $cronJobLogId = null,
        $cronDay = null,
        $cronHour = null
    ) {
        try {
            $username = '';
            if (defined('USERID')) {
                $username = USERID;
            }

            #-------------------------------------------------
            # Log run information to external module log
            #-------------------------------------------------
            $this->moduleLog->logEtlRun(
                $projectId,
                $username,
                $isCronJob,
                $configName,
                $serverName,
                $cronJobLogId,
                $cronDay,
                $cronHour
            );

            $adminConfig = $this->getAdminConfig();
            
            #---------------------------------------------
            # Process configuration name
            #---------------------------------------------
            if (empty($configName)) {
                throw new \Exception('No configuration specified.');
            } else {
                $etlConfig = $this->getConfiguration($configName, $projectId);
                if (!isset($etlConfig)) {
                    throw new \Exception('Configuration "'.$configName.'" not found for project ID '.$projectId);
                }
            }
            
            #---------------------------------------------
            # Process server name
            #---------------------------------------------
            if (empty($serverName)) {
                throw new \Exception('No server specified.');
            } else {
                $serverConfig = $this->getServerConfig($serverName); # Method throws exception if server not found
                if (!$serverConfig->getIsActive()) {
                    throw new \Exception('Server "'.$serverName.'" has been deactivated and cannot be used.');
                }
            }
            
            $configUrl  = $etlConfig->getProperty(Configuration::REDCAP_API_URL);
            
            #-----------------------------------------------------
            # Set logging information
            #-----------------------------------------------------
            $cron = 'no';
            if ($isCronJob) {
                $cron = 'yes';
            }
            
            $details = '';
            # If being run on remote REDCap
            if (strcasecmp($configUrl, $this->getRedCapApiUrl()) !== 0) {
                $details .= "REDCap API URL: {$configUrl}\n";
            } else {
                # If local REDCap is being used, set SSL verify to the global value
                $sslVerify = $adminConfig->getSslVerify();
                $etlConfig->setProperty(Configuration::SSL_VERIFY, $sslVerify);
            }
            
            $details .= "project ID: {$projectId}\n";
            if (!$isCronJob) {
                $details .= "user: ".USERID."\n";
            }
            $details .= "configuration: {$configName}\n";
            $details .= "server: {$serverName}\n";
            if ($isCronJob) {
                $details .= "cron: yes\n";
            } else {
                $details .= "cron: no\n";
            }
                    
            $status = '';

            $sql    = null;
            $record = null;
            $event  = self::LOG_EVENT;
            
            if ($isCronJob) {
                $projectId = $etlConfig->getProjectId();
            } else {
                $projectId = null; // Handled automatically in logging if not cron job
            }


            #---------------------------------------------
            # Authorization checks
            #---------------------------------------------
            if ($isCronJob) {
                if (!$adminConfig->getAllowCron()) {
                    # Cron jobs not allowed
                    $message = "Cron job failed - cron jobs not allowed\n".$details;
                    throw new \Exception($message);
                }
                # Note: the following check is no longer valid (an admin could set up a cron job):
                #elseif (!$this->hasEtlUser($projectId)) {
                #    # No user for this project has ETL permission
                #    $message = "Cron job failed - no project user has ETL permission\n".$details;
                #    throw new \Exception($message);
                #}
            } else {
                # If NOT a cron job
                if (!Authorization::hasEtlProjectPagePermission($this)) {
                    $message = 'User "'.USERID.'" does not have permission to run ETL for this project.';
                    throw new \Exception($message);
                }
            }
            
            $details = "ETL job submitted \n".$details;
            \REDCap::logEvent(self::RUN_LOG_ACTION, $details, $sql, $record, $event, $projectId);
            
            #------------------------------------------------------------------------
            # If no server configuration was specified, run on the embedded server
            #------------------------------------------------------------------------
            #if (empty($serverConfig)) {
            #    $status = $this->runEmbedded($etlConfig, $isCronJob);
            #} else {
            #    # Run on the specified server
            #    $status = $serverConfig->run($etlConfig, $isCronJob);
            #}
            $status = $serverConfig->run($etlConfig, $isCronJob, $this->moduleLog);
        } catch (\Exception $exception) {
            $status = "ETL job failed: ".$exception->getMessage();
            $details = "ETL job failed\n".$details
                .'error: '.$exception->getMessage();
            \REDCap::logEvent(self::RUN_LOG_ACTION, $details, $sql, $record, $event, $projectId);
        }
        
        return $status;
    }
    

    /**
     * Runs an ETL configuration on the embedded server.
     */
    #private function runEmbedded($etlConfiguration, $isCronJob = false)
    #{
    #    $properties = $etlConfiguration->getPropertiesArray();
    #
    #    $adminConfig = $this->getAdminConfig();
    #
    #    $logger = new \IU\REDCapETL\Logger('REDCap-ETL');
    #
    #    try {
    #        # Set the from e-mail address from the admin. configuration
    #        $properties[Configuration::EMAIL_FROM_ADDRESS] = $adminConfig->getEmbeddedServerEmailFromAddress();
    #
    #        # Set the log file, and set print logging off
    #        $properties[Configuration::LOG_FILE]      = $adminConfig->getEmbeddedServerLogFile();
    #        $properties[Configuration::PRINT_LOGGING] = false;
    #
    #        # Set process identifting properties
    #        $properties[Configuration::PROJECT_ID]   = $etlConfiguration->getProjectId();
    #        $properties[Configuration::CONFIG_NAME]  = $etlConfiguration->getName();
    #        $properties[Configuration::CONFIG_OWNER] = $etlConfiguration->getUsername();
    #        $properties[Configuration::CRON_JOB]     = $isCronJob;
    #
    #        $redCapEtl = new \IU\REDCapETL\RedCapEtl($logger, $properties);
    #        $redCapEtl->run();
    #    } catch (\Exception $exception) {
    #        $logger->logException($exception);
    #        $logger->log('Processing failed.');
    #    }
    #
    #    $status = implode("\n", $logger->getLogArray());
    #    return $status;
    #}
    
    
    
    /**
     * Gets the REDCap API URL.
     *
     * @return string the REDCap API URL.
     */
    public static function getRedCapApiUrl()
    {
        return APP_PATH_WEBROOT_FULL.'api/';
    }

    /**
     * Gets the external module's version number.
     */
    public function getVersion()
    {
        return $this->settings->getVersion();
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
        $details = 'User '.$username.' added to ETL users.';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }

    public function deleteUser($username)
    {
        $this->settings->deleteUser($username);
        $details = 'User '.$username.' deleted from ETL users.';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }

    public function getServersViaAccessLevels($specificLevel = 'none')
    {
        #get server names
        $servers = array();
        $allServers = $this->settings->getServers();

        #loop through the server names to get their access levels
        foreach ($allServers as $serverName) {
           #get the server configurations for the server name
            $serverConfig=$this->settings->getServerConfig($serverName);

           #if the server has the access level specified,
           #add it to the array of servers
            $accessLevel = $serverConfig->getAccessLevel();
            if ($specificLevel === 'none') {
                $servers[] = $serverName;
            } else {
                if ($accessLevel === $specificLevel) {
                    $servers[] = $serverName;
                }
            }
        }
        #print "<br /><br />specificLevel is: $specificLevel<br />";
        #print "<br /><br />servers object is: <br />";
        #print '<pre>'; print_r($servers); print '</pre>'."<br />";
        return $servers;
    }

    public function getUserAllowedServersBasedOnAccessLevel($username = USERID)
    {
        $servers = array();

       #if this is a REDCap admin, then return all servers
        if ($this->isSuperUser($username)) {
            $servers = $this->getServersViaAccessLevels('none');
        } else {
            #add servers with public access
            $servers=$this->getServersViaAccessLevels('public');

            #add the private servers that the user is allowed to access
            $userPrivateServers = array();
            $userPrivateServers = $this->settings->getUserPrivateServerNames($username);
            $servers = array_merge($servers, $userPrivateServers);
        }

        return $servers;
    }

    public function setUserPrivateServerNames($username, $userServerNames)
    {
        $this->settings->setUserPrivateServerNames($username, $userServerNames);
        $details = 'Allowable private servers modified for user '.$username;
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }

    public function processUserPrivateServers($username, $userServerNames, $privateServers)
    {
        $this->settings->processUserPrivateServers($username, $userServerNames, $privateServers);
    }

    public function getUserPrivateServerNames($username, $privateServers)
    {
        $userServerNames = array();
        $userServers = $this->settings->getUserPrivateServerNames($username);

        foreach ($userServers as $serverName) {
           #if the user-assigned server stills have private access, then keep it
            if (in_array($serverName, $privateServers)) {
                $userServerNames[] = $serverName;
            }
        }
        if ($userServerNames != $userServers) {
            $this->settings->setUserPrivateServerNames($username, $userServerNames);
        }

        return $userServerNames;
    }

    public function getPrivateServerUsers($serverName)
    {
        return $this->settings->getPrivateServerUsers($serverName);
    }

    public function processPrivateServerUsers($serverName, $removeUsernames)
    {
        $this->settings->processPrivateServerUsers($serverName, $removeUsernames);
    }

    #-------------------------------------------------------------------
    # User ETL project methods
    #-------------------------------------------------------------------
    
    /**
     * Gets the ETL projects for the specified user, or for the current
     * user if no user is specified.
     *
     * @param string the username for which to get the ETL projects.
     *
     * @return array project IDs of projects that the user has permission to use REDCap-ETL.
     */
    public function getUserEtlProjects($username = USERID)
    {
        return $this->settings->getUserEtlProjects($username);
    }
    
    #/**
    # * Checks if the specified project has at least one user that has
    # * permission to user REDCap-ETL for the project.
    # *
    # * @return boolean true if there is at least one user who has permission to
    # *     run REDCap-ETL for the project, and false otherwise.
    # */
    #public function hasEtlUser($projectId = PROJECT_ID)
    #{
    #    return $this->settings->hasEtlUser($projectId);
    #}
    
    #/**
    # * Gets the current username.
    # *
    # * @return string the username of the current user.
    # */
    #public function getUsername()
    #{
    #    return USERID;
    #}
    
    public function isSuperUser()
    {
        $isSuperUser = false;
        if (defined('SUPER_USER') && SUPER_USER) {
            $isSuperUser = true;
        }
        return $isSuperUser;
    }

    /**
     * Get a REDCap "from e-mail" address.
     */
    public static function getFromEmail()
    {
        // phpcs:disable
        global $from_email;
        global $homepage_contact_email;

        $fromEmail = '';

        # Need to diable phpcs here, because the REDCap e-mail variables
        # ($from_email and $homepage_contact_email) don't use camel-case.
        if (!empty($from_email)) {
            $fromEmail = $from_email;
        } else {
            $fromEmail = $homepage_contact_email;
        }
        // phpcs:enable

        return $fromEmail;
    }
    
    /**
     * Set the projects for which the specified user is allowed to use
     * REDCap-ETL.
     *
     * @param string username the username for the user whose projects
     *     are being modified.
     * @param array $projects an array of REDCap project IDS
     *     for which the user has ETL permission.
     */
    public function setUserEtlProjects($username, $projects)
    {
        $this->settings->setUserEtlProjects($username, $projects);
        $details = 'ETL projects modified for user '.$username;
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
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
    public function getConfiguration($name, $projectId = PROJECT_ID)
    {
        return $this->settings->getConfiguration($name, $projectId);
    }
    
    /**
     * @param Configuration $configuration
     * @param string $username
     * @param string $projectId
     */
    public function setConfiguration($configuration, $username = USERID, $projectId = PROJECT_ID)
    {
        $this->settings->setConfiguration($configuration, $username, $projectId);
        $details = 'REDCap-ETL configuration "'.$configuration->getName()
            .'" updated (pid='.$configuration->getProjectId().') ';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }

    public function setConfigSchedule($configName, $server, $schedule, $username = USERID, $projectId = PROJECT_ID)
    {
        $this->settings->setConfigSchedule($configName, $server, $schedule, $username, $projectId);
        $details = 'REDCap-ETL configuration "'.$configName
            .'" schedule modified for user '.$username.' and project ID '.$projectId.'.';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }
    
    public function addConfiguration($name, $username = USERID, $projectId = PROJECT_ID)
    {
        $dataExportRight = $this->getDataExportRight();
        $this->settings->addConfiguration($name, $username, $projectId, $dataExportRight);
        $details = 'REDCap-ETL configuration "'.$name.'" created.';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }


    /**
     * Copy configuration (only supports copying from/to same
     * user and project).
     */
    public function copyConfiguration($fromConfigName, $toConfigName, $username = USERID)
    {
        if (Authorization::hasEtlConfigNamePermission($this, $fromConfigName, PROJECT_ID)) {
            $toExportRight = $this->getDataExportRight();
            $this->settings->copyConfiguration($fromConfigName, $toConfigName, $toExportRight);
            $details = 'REDCap-ETL configuration "'.$fromConfigName.'" copied to "'.
                $toConfigName.'" for user '.USERID.', project ID '.PROJECT_ID.'.';
            \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
        } else {
            throw new \Exception('You do not have permission to copy configuration "'.$configName.'".');
        }
    }
    
    /**
     * Renames configuration (only supports rename from/to same project).
     *
     * @param string $configName the name of the configuration to be renamed.
     * @param string $newConfigName the new name for the configuration being renamed.
     */
    public function renameConfiguration($configName, $newConfigName)
    {
        if (Authorization::hasEtlConfigNamePermission($this, $configName, PROJECT_ID)) {
            $this->settings->renameConfiguration($configName, $newConfigName);
            $details = 'REDCap-ETL configuration "'.$configName.'" renamed to "'.
                $newConfigName.'" for user '.USERID.', project ID '.PROJECT_ID.'.';
            \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
        } else {
            throw new \Exception('You do not have permission to remname configuration "'.$configName.'".');
        }
    }
    
    public function removeConfiguration($configName)
    {
        if (Authorization::hasEtlConfigNamePermission($this, $configName, PROJECT_ID)) {
            $this->settings->removeConfiguration($configName);
            $details = 'REDCap-ETL configuration "'.$configName.'" deleted.';
            \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
        } else {
            throw new \Exception('You do not have permission to remove configuration "'.$configName.'".');
        }
    }
    
    public function getConfigurationExportRight($configuration, $projectId = PROJECT_ID)
    {
        $exportRight = $configuration->getProperty(Configuration::DATA_EXPORT_RIGHT);
        return $exportRight;
    }
    
    /**
     * Gets all the configuration names for the specified project.
     */
    public function getConfigurationNames($projectId = PROJECT_ID)
    {
        return $this->settings->getConfigurationNames($projectId);
    }

    /**
     * Gets the configuration names for the specified project that the specified user
     * has permission to access.
     */
    public function getAccessibleConfigurationNames($projectId = PROJECT_ID, $username = USERID)
    {
        $configNames = array();
        $allConfigNames = $this->getConfigurationNames($projectId);
        
        if ($this->getDataExportRight() == 1) {
            # If user has "full data set" export permissions, improve performance by
            # avoiding individual configuration permission checks below
            $configNames = $allConfigNames;
        #} else {
        #    foreach ($allConfigNames as $configName) {
        #        $config = $this->getConfiguration($configName, $projectId);
        #        if (Authorization::hasEtlConfigurationPermission($this, $config)) {
        #            array_push($configNames, $configName);
        #        }
        #    }
        }
        return $configNames;
    }
    
    /**
     * Gets the configuration from the request (if any), and
     * updates the user's session appropriately.
     *
     * @throws \Exception if the configuration name is invalid.
     */
    public function getConfigurationFromRequest()
    {
        $configuration = null;
        $configName = Filter::stripTags($_POST['configName']);
        if (empty($configName)) {
            $configName = Filter::stripTags($_GET['configName']);
            if (empty($configName)) {
                $configName = Filter::stripTags($_SESSION['configName']);
            }
        }
        
        if (!empty($configName)) {
            Configuration::validateName($configName);  # throw exception if invalid
            $configuration = $this->getConfiguration($configName, PROJECT_ID);
            if (empty($configuration)) {
                $configName = '';
            }
        } else {
            $configName = '';
        }
        
        $_SESSION['configName'] = $configName;

        return $configuration;
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
        $details = 'REDCap-ETL admin configuration "'.$configName.'" modified.';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }

    #==================================================================================
    # Server methods
    #==================================================================================
    
    public function getServers()
    {
        return $this->settings->getServers();
    }

    /**
     * Adds a new server with the specified server name.
     *
     * @param string $serverName the name of the server to add.
     */
    public function addServer($serverName)
    {
        $this->settings->addServer($serverName);
        $details = 'Server "'.$serverName.'" created.';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }

    public function copyServer($fromServerName, $toServerName)
    {
        if (strcasecmp($fromServerName, ServerConfig::EMBEDDED_SERVER_NAME) === 0) {
            throw new \Exception('The embedded server "'.ServerConfig::EMBEDDED_SERVER_NAME.'" cannot be copied.');
        }
        $this->settings->copyServer($fromServerName, $toServerName);
        $details = 'Server "'.$fromServerName.'" copied to "'.$toServerName.'".';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }
    
    public function renameServer($serverName, $newServerName)
    {
        if (strcasecmp($serverName, ServerConfig::EMBEDDED_SERVER_NAME) === 0) {
            throw new \Exception('The embedded server "'.ServerConfig::EMBEDDED_SERVER_NAME.'" cannot be renamed.');
        }
        $this->settings->renameServer($serverName, $newServerName);
        $details = 'Server "'.$serverName.'" renamed to "'.$newServerName.'".';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }
    
    
    public function removeServer($serverName)
    {
        if (strcasecmp($serverName, ServerConfig::EMBEDDED_SERVER_NAME) === 0) {
            throw new \Exception('The embedded server "'.ServerConfig::EMBEDDED_SERVER_NAME.'" cannot be deleted.');
        }
        $this->settings->removeServer($serverName);
        $details = 'Server "'.$serverName.'" deleted.';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
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
        $details = 'Server "'.$serverName.'" modified.';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }

    #==================================================================================
    # Last run time methods
    #==================================================================================
    
    public function getLastRunTime()
    {
        return $this->settings->getLastRunTime();
    }

    public function setLastRunTime($date, $hour, $minutes)
    {
        $this->settings->setLastRunTime($date, $hour, $minutes);
        # Don't log this because it is an internal event
    }
    
    public function isLastRunTime($date, $hour)
    {
        return $this->settings->isLastRunTime($date, $hour);
    }

    #=============================================================
    # Help methods
    #=============================================================
    
    public function getHelpSetting($topic)
    {
        return $this->settings->getHelpSetting($topic);
    }
    
    public function setHelpSetting($topic, $setting)
    {
        $this->settings->setHelpSetting($topic, $setting);
    }
    
    public function getCustomHelp($topic)
    {
        return $this->settings->getCustomHelp($topic);
    }
    
    public function setCustomHelp($topic, $help)
    {
        $this->settings->setCustomHelp($topic, $help);
    }
    

    /**
     * Renders the page content tabs for REDCap-ETL admin pages.
     */
    public function renderAdminTabs($activeUrl = '')
    {

        $infoUrl = $this->getUrl(self::ADMIN_INFO_PAGE);
        $infoLabel = '&nbsp;<span class="fa fa-info-circle"></span>&nbsp;Info';
        
        $adminUrl = $this->getUrl(self::ADMIN_HOME_PAGE);
        $adminLabel = '<span class="fas fa-cog"></span>'
           .' Config';

        $cronJobsUrl = $this->getUrl(self::CRON_DETAIL_PAGE);
        $cronJobsLabel = '<span class="fas fa-clock"></span>'
           .' Cron Detail';

        $usersUrl = $this->getUrl(self::USERS_PAGE);
        $usersLabel = '<span class="fas fa-user"></span>'
           .' Users</span>';

        $serversUrl = $this->getUrl(self::SERVERS_PAGE);
        $serversLabel = '<span class="fas fa-server"></span>'
           .' ETL Servers';

        $helpEditUrl = $this->getUrl(self::HELP_LIST_PAGE);
        $helpEditLabel = '<span class="fas fa-edit"></span>'
           .' Help Edit';
                      
        $logUrl = $this->getUrl(self::LOG_PAGE);
        $logLabel = '<span class="fas fa-file-alt"></span>'
           .' Log';
                      
        $tabs = array();
        
        $tabs[$infoUrl]          = $infoLabel;
        
        $tabs[$adminUrl]         = $adminLabel;
        $tabs[$cronJobsUrl]      = $cronJobsLabel;
        $tabs[$usersUrl]         = $usersLabel;
        #$tabs[$configureUserUrl] = $configureUserLabel;

        $tabs[$serversUrl]       = $serversLabel;
        #$tabs[$serverConfigUrl]  = $serverConfigLabel;

        $tabs[$helpEditUrl]      = $helpEditLabel;

        $tabs[$logUrl]  = $logLabel;
                
        $this->renderTabs($tabs, $activeUrl);
    }

    /**
     * Render sub-tabs for the admin ETL server pages.
     */
    public function renderAdminEtlServerSubTabs($activeUrl = '')
    {
        $serversUrl = $this->getUrl(self::SERVERS_PAGE);
        $serversLabel = '<span class="fas fa-list"></span>'
           .' List';

        $serverConfigUrl = $this->getUrl(self::SERVER_CONFIG_PAGE);
        $serverConfigLabel = '<span class="fas fa-cog"></span>'
           .' Configuration';

        $tabs = array();

        $tabs[$serversUrl]       = $serversLabel;
        $tabs[$serverConfigUrl]  = $serverConfigLabel;

        $this->renderSubTabs($tabs, $activeUrl);
    }

    /**
     * Render sub-tabs for the admin user pages.
     */
    public function renderAdminUsersSubTabs($activeUrl = '')
    {
        $usersUrl = $this->getUrl(self::USERS_PAGE);
        $usersLabel = '<span class="fas fa-list"></span>'
           .' List</span>';

        $configureUserUrl = $this->getUrl(self::USER_CONFIG_PAGE);
        $configureUserLabel = '<span class="fas fa-search"></span>'
           .' Search</span>';

        $tabs = array();

        $tabs[$usersUrl]         = $usersLabel;
        $tabs[$configureUserUrl] = $configureUserLabel;

        $this->renderSubTabs($tabs, $activeUrl);
    }
    
     /**
     * Render sub-tabs for the admin help edit pages.
     */
    public function renderAdminHelpEditSubTabs($activeUrl = '')
    {
        $usersUrl = $this->getUrl(self::HELP_LIST_PAGE);
        $usersLabel = '<span class="fas fa-list"></span>'
           .' List</span>';

        $configureUserUrl = $this->getUrl(self::HELP_EDIT_PAGE);
        $configureUserLabel = '<span class="fas fa-edit"></span>'
           .' Edit</span>';

        $tabs = array();

        $tabs[$usersUrl]         = $usersLabel;
        $tabs[$configureUserUrl] = $configureUserLabel;

        $this->renderSubTabs($tabs, $activeUrl);
    }


    /**
     * Renders the top-level tabs for the user interface.
     */
    public function renderUserTabs($activeUrl = '')
    {
        $listUrl = $this->getUrl('web/index.php');
        $listLabel = '<span class="fas fa-list"></span>'
           .' ETL Configurations';

        
        $configUrl = $this->getUrl('web/configure.php');
        $configLabel = '<span style="color: #808080;" class="fas fa-cog"></span>'
           .' Configure';

        $scheduleUrl = $this->getUrl('web/schedule.php');
        $scheduleLabel =
           '<span id="schedule-tab" class="fas fa-clock"></span>'
           .' Schedule';

        $runUrl = $this->getUrl('web/run.php');
        $runLabel = '<span style="color: #008000;" class="fas fa-play-circle"></span>'
           .' Run';

        $workflowsUrl = $this->getUrl('web/workflows.php');
        $workflowsLabel = '<span style="color: #808080;" class="fas fa-list"></span>'
           .' Workflows';

        $userManualUrl = $this->getUrl('web/user_manual.php');
        $userManualLabel =
            '<i class="fas fa-book"></i>'
            .' User Manual';


        $adminConfig = $this->getAdminConfig();
        
        # Map for tabs from URL to the label for the URL
        $tabs = array();
        
        $tabs[$listUrl]     = $listLabel;
        
        $userEtlProjects = $this->getUserEtlProjects(USERID);
        
        $tabs[$workflowsUrl] = $workflowsLabel;

        if (Authorization::hasEtlProjectPagePermission($this)) {
            $tabs[$configUrl]   = $configLabel;
    
            if ($adminConfig->getAllowOnDemand()) {
                $tabs[$runUrl] = $runLabel;
            }
                    
            if ($adminConfig->getAllowCron()) {
                $tabs[$scheduleUrl] = $scheduleLabel;
            }
                      
            $tabs[$userManualUrl] = $userManualLabel;
        }
        
        $this->renderTabs($tabs, $activeUrl);
    }


    /**
     * Renders tabs using built-in REDCap styles.
     *
     * @param array $tabs map from URL to tab label.
     * @param string $activeUrl the URL that should be marked as active.
     */
    public function renderTabs($tabs = array(), $activeUrl = '')
    {
        echo '<div id="sub-nav" style="margin:5px 0 20px;">'."\n";
        echo '<ul>'."\n";
        foreach ($tabs as $tabUrl => $tabLabel) {
            // Check for Active tab
            $isActive = false;
            $class = '';
            if (strcasecmp($tabUrl, $activeUrl) === 0) {
                $class = ' class="active"';
                $isActive = true;
            }
            echo '<li '.$class.'>'."\n";
            # Note: URLs created with the getUrl method, so they should already be escaped
            echo '<a href="'.$tabUrl.'" style="font-size:13px;color:#393733;padding:6px 9px 5px 10px;">';
            # Note: labels are static values in code, and not based on user input
            echo $tabLabel.'</a>'."\n";
        }
        echo '</li>'."\n";
        echo '</ul>'."\n";
        echo '</div>'."\n";
        echo '<div class="clear"></div>'."\n";
    }
    
    /**
     * Renders sub-tabs (second-level tabs) on the page.
     *
     * @param array $tabs map from URL to tab label.
     * @param string $activeUrl the URL that should be marked as active.
     */
    public function renderSubTabs($tabs = array(), $activeUrl = '')
    {
        echo '<div style="text-align:right; margin-bottom: 17px; margin-top: 0px;">';
        $isFirst = true;
        foreach ($tabs as $url => $label) {
            $style = '';
            if (strcasecmp($url, $activeUrl) === 0) {
                $style = ' style="padding: 1px; text-decoration: none; border-bottom: 2px solid black;" ';
            } else {
                $style = ' style="padding: 1px; text-decoration: none;" ';
            }
            
            if ($isFirst) {
                $isFirst = false;
            } else {
                echo "&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;";
            }
            echo '<a href="'.$url.'" '.$style.'>'."{$label}</a>";
        }
        echo "</div>\n";
    }

    
    public function renderSuccessMessageDiv($message)
    {
        if (!empty($message)) {
            echo '<div align="center" class="darkgreen" style="margin: 20px 0;">'."\n";
            echo '<img src="'.(APP_PATH_IMAGES.'accept.png').'" alt="">';
            echo '&nbsp;'.Filter::escapeForHtml($message)."\n";
            echo "</div>\n";
        }
    }
    
    public function renderWarningMessageDiv($message)
    {
        if (!empty($message)) {
            echo '<div align="center" class="yellow" style="margin: 20px 0;">'."\n";
            echo '<img src="'.(APP_PATH_IMAGES.'warning.png').'"  alt="" width="16px">';
            echo '&nbsp;'.Filter::escapeForHtml($message)."\n";
            echo "</div>\n";
        }
    }
           
    public function renderErrorMessageDiv($message)
    {
        if (!empty($message)) {
            echo '<div align="center" class="red" style="margin: 20px 0;">'."\n";
            echo '<img src="'.(APP_PATH_IMAGES.'exclamation.png').'" alt="">';
            echo '&nbsp;'.Filter::escapeForHtml($message)."\n";
            echo "</div>\n";
        }
    }
    
    
    public function renderAdminPageContentHeader($selfUrl, $errorMessage, $warningMessage, $successMessage)
    {
        $this->renderAdminTabs($selfUrl);
        $this->renderErrorMessageDiv($errorMessage);
        $this->renderWarningMessageDiv($warningMessage);
        $this->renderSuccessMessageDiv($successMessage);
    }
    
    /**
     * Renders the page content header for a project page.
     *
     * @param string $selfUrl the URL of the page where the content header is to rendered.
     * @param string $errorMessage the error message to print (if any).
     * @param string $successMessage the success message to print (if any).
     */
    public function renderProjectPageContentHeader($selfUrl, $errorMessage, $warningMessage, $successMessage)
    {
        $this->renderUserTabs($selfUrl);
        $this->renderErrorMessageDiv($errorMessage);
        $this->renderWarningMessageDiv($warningMessage);
        $this->renderSuccessMessageDiv($successMessage);
    }


    /**
     * Checks if the user has permission to a user (non-admin) page, and
     * returns the configuration corresponding to the configuration name in
     * the request, if any. If the user does NOT have permission, the
     * request will be routed to an error page.
     *
     * @return Configuration if a configuration name was specified in the request, the
     *     configuration for that configuration name.
     */
    public function checkUserPagePermission(
        $username = USERID,
        $configCheck = false,
        $runCheck = false,
        $scheduleCheck = false
    ) {
        $configuration = null;
        
        $adminConfig = $this->getAdminConfig();
        
        if (!Csrf::isValidRequest()) {
            # CSRF (Cross-Site Request Forgery) check failed; this should mean that either the
            # request is a CSRF attack or the user's session expired
            $accessUrl = $this->getUrl('web/access.php?accessError='.self::CSRF_ERROR);
            header('Location: '.$accessUrl);
            exit();
        } elseif ($runCheck && !$adminConfig->getAllowOnDemand()) {
            # Trying to access the run page when running on demand has been disabled
            $indexUrl = $this->getUrl('web/index.php');
            header('Location: '.$indexUrl);
            exit();
        } elseif ($scheduleCheck && !$adminConfig->getAllowCron()) {
            # trying to access the schedule page when ETL cron jobs have been disabled
            $indexUrl = $this->getUrl('web/index.php');
            header('Location: '.$indexUrl);
            exit();
        } elseif (!Authorization::hasRedCapUserRightsForEtl($this)) {
            # User does not have REDCap user rights to use ETL for this project
            $accessUrl = $this->getUrl('web/access.php?accessError='.self::USER_RIGHTS_ERROR);
            header('Location: '.$accessUrl);
            exit();
        } elseif (!Authorization::hasEtlProjectPagePermission($this)) {
            # User has REDCap ETL user rights, but does not have specific ETL permission for this project
            $accessUrl = $this->getUrl('web/access.php?accessError='.self::NO_ETL_PROJECT_PERMISSION);
            header('Location: '.$accessUrl);
            exit();
        } else {
            # See if a configuration was specified in the request, and if so, check that the user
            # has permission to access it
            $configuration = $this->getConfigurationFromRequest();
            if (isset($configuration)) {
                if (!Authorization::hasEtlConfigurationPermission($this, $configuration)) {
                    if ($configCheck) {
                        # User does not have permission to access the specified configuration
                        $accessUrl = $this->getUrl('web/access.php?accessError='.self::NO_CONFIGURATION_PERMISSION);
                        header('Location: '.$accessUrl);
                        exit();
                    } else {
                        $configuration = null;
                    }
                }
            }
        }
        return $configuration;
    }
    
    /**
     * Checks admin page access and exits if there is an issue.
     */
    public function checkAdminPagePermission()
    {
        if ((defined('SUPER_USER') && !SUPER_USER) || !defined('SUPER_USER')) {
            exit("Only super users can access this page!");
        } elseif (!Csrf::isValidRequest()) {
            exit(
                "Access not allowed. Your session may have expired."
                ." Please make sure you are logged in and try again."
            );
        }
    }

    public function addWorkflow($workflowName, $username = USERID, $projectId = PROJECT_ID)
    {
        $dataExportRight = $this->getDataExportRight();
        $this->settings->addWorkflow($workflowName, $username, $projectId, $dataExportRight);
        $details = 'REDCap-ETL workflow "'.$workflowName.'" created.';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }

    public function getWorkflow($workflowName, $removeMetadata = null)
    {
         return $this->settings->getWorkflow($workflowName, $removeMetadata);
    }

    public function getWorkflowStatus($workflowName)
    {
         return $this->settings->getWorkflowStatus($workflowName);
    }

    public function getProjectAvailableWorkflows($projectId = PROJECT_ID)
    {
         return $this->settings->getProjectAvailableWorkflows();
    }

    /**
     * Marks a workflow as 'removed'. Does not delete the workflow from the Workflows array.
     */
    public function removeWorkflow($workflowName, $username = USERID)
    {
#        if (Authorization::hasEtlConfigNamePermission($this, $configName, PROJECT_ID)) {
            $this->settings->removeWorkflow($workflowName, $username);
            $details = 'REDCap-ETL workflow "'.$workflowName.'" marked as removed by user '.$username.'.';
            \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
#        } else {
#            throw new \Exception('You do not have permission to remove workflow "'.$workflowName.'".');
#        }
    }

    public function renameWorkflow($workflowName, $newWorkflowName, $username = USERID)
    {
#        if (Authorization::hasEtlConfigNamePermission($this, $configName, PROJECT_ID)) {
            $this->settings->renameWorkflow($workflowName, $newWorkflowName, $username);
            $details = 'REDCap-ETL workflow "'.$workflowName.'" renamed to "'.
                $newWorkflowName.'" by user '.$username.', project ID '.PROJECT_ID.'.';
            \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
#        } else {
#            throw new \Exception('You do not have permission to rename workflow "'.$workflowName.'".');
#        }
    }

    public function copyWorkflow($fromWorkflowName, $toWorkflowName, $username = USERID)
    {
#        if (Authorization::hasEtlConfigNamePermission($this, $configName, PROJECT_ID)) {
            $this->settings->copyWorkflow($fromWorkflowName, $toWorkflowName, $username);
            $details = 'REDCap-ETL workflow "'.$fromWorkflowName.'" copied to "'.
                $toWorkflowName.'" by user '.$username.', project ID '.PROJECT_ID.'.';
            \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
#        } else {
#            throw new \Exception('You do not have permission to rename workflow "'.$workflowName.'".');
#        }
    }

    public function addProjectToWorkflow($workflowName, $project, $username = USERID)
    {
         return $this->settings->addProjectToWorkflow($workflowName, $project, $username);
    }

    public function deleteTaskfromWorkflow($workflowName, $deleteTaskKey, $projectId, $username = USERID)
    {
#       if (Authorization::hasEtlConfigNamePermission($this, $configName, PROJECT_ID)) {

            $this->settings->deleteTaskFromWorkflow($workflowName, $deleteTaskKey, $username);
            $details = 'Task # ' .$deleteTaskKey .' (Project Id ' .$projectId
                .') deleted from REDCap-ETL workflow "'.$workflowName
                .'" by user '.$username.'.';
            \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
#       } else {
#            throw new \Exception('You do not have permission to delete project ## from workflow "'.$workflowName.'".');
#       }
    }

    public function moveWorkflowTask($workflowName, $direction, $moveTaskKey)
    {
        $this->settings->moveWorkflowTask($workflowName, $direction, $moveTaskKey);
    }

    public function getEtlGlobalParametersList()
    {
        $this->settings->getEtlGlobalParametersList();
    }

    public function renameWorkflowTask(
        $workflowName,
        $renameTaskKey,
        $renameNewTaskName,
        $renameProjectId,
        $username
    ) {
#        if (Authorization::hasEtlConfigNamePermission($this, $configName, PROJECT_ID)) {
        $this->settings->renameWorkflowTask($workflowName, $renameTaskKey, $renameNewTaskName, $renameProjectId, $username);
            $details = 'REDCap-ETL workflow "'.$workflowName.
                       '", project ID "'.$renameProjectId.
                       '", task key "'.$renameTaskKey.
                       '" renamed to "'.$newWorkflowName.'" by user '.$username;
            \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
#        } else {
#            throw new \Exception('You do not have permission to rename workflow "'.$workflowName.'".');
#        }
    }

    public function assignWorkflowTaskEtlConfig(
        $workflowName,
        $etlProjectId,
        $etlTaskKey,
        $etlConfig,
        $username
    ) {
    #    if (Authorization::hasEtlConfigNamePermission($this, $configName, PROJECT_ID)) {
        $this->settings->assignWorkflowTaskEtlConfig($workflowName, $etlProjectId, $etlTaskKey, $etlConfig, $username);
            $details = 'REDCap-ETL workflow "'.$workflowName.
                       '", project ID "'.$etlProjectId.
                       '", task key "'.$etlTaskKey.
                       '" was assigned ETL config "'.$etConfig.'" by user '.$username;
            \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
#        } else {
#            throw new \Exception('You do not have permission to rename workflow "'.$workflowName.'".');
#        }
    }

    public function updateWorkflowTasks(
        $workflowName,
        $taskNumbers,
        $taskNames,
        $projectEtlConfigs,
        $username = USERID
    ) {
#        if (Authorization::hasEtlConfigNamePermission($this, $configName, PROJECT_ID)) {
            $this->settings->updateWorkflowTasks($workflowName, $taskNumbers, $taskNames, $projectEtlConfigs, $username);

            #$details = 'REDCap-ETL workflow "'.$workflowName.'" renamed to "'.
            #    $newWorkflowName.'" by user '.$username.', project ID '.PROJECT_ID.'.';
            #\REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
#        } else {
#            throw new \Exception('You do not have permission to rename workflow "'.$workflowName.'".');
#        }
    }
}

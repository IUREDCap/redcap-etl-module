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
    const ADMIN_INFO_PAGE    = 'web/admin/info.php';
        
    const USER_ETL_CONFIG_PAGE  = 'web/configure.php';
    
    const RUN_LOG_ACTION    = 'REDCap-ETL Export';
    const CHANGE_LOG_ACTION = 'REDCap-ETL Change';
    const LOG_EVENT         = -1;
    
    # Access/permission errors
    const CSRF_ERROR                  = 1;
    const USER_RIGHTS_ERROR           = 2;
    const NO_ETL_PROJECT_PERMISSION   = 3;
    const NO_CONFIGURATION_PERMISSION = 4;
    
    private $settings;
    private $db;
    private $changeLogAction;

    public function __construct()
    {
        $this->db = new RedCapDb();
        $this->settings = new Settings($this, $this->db);
        parent::__construct();
    }
    
    // phpcs:disable
    /**
     * Method that determines if the REDCap-ETL link is displayed for
     * the user on project pages.
     */
    public function redcap_module_link_check_display($project_id, $link)
    {
        if (SUPER_USER) {
            return $link;
        }

        if (!empty($project_id) && Authorization::hasRedCapUserRightsForEtl($this, USERID)) {
            return $link;
        }

        return null;
    }
    // phpcs:enable

        
    /**
     * Returns REDCap user rights for the current project.
     *
     * @return array a map from username to an map of rights for the current project.
     */
    public function getUserRights($userId = null)
    {
        $rights = \REDCap::getUserRights($userId);
        return $rights;
    }
    
    public function getDataExportRight($username = USERID)
    {
        $dataExportRight = 0;
        $rights = $this->getUserRights($username)[$username];
        $dataExportRight = $rights['data_export_tool'];
        return $dataExportRight;
    }
    
    /**
     * Cron method that is called by REDCap as configured in the
     * config.json file for this module.
     */
    public function cron()
    {
        #\REDCap::logEvent('REDCap-ETL cron function start');
        
        $adminConfig = $this->getAdminConfig();
            
        $now = new \DateTime();
        $day  = $now->format('w');  // 0-6 (day of week; Sunday = 0)
        $hour = $now->format('G');  // 0-23 (24-hour format without leading zeroes)
        $date = $now->format('Y-m-d');
        
        #if ($this->isLastRunTime($date, $hour)) {
        #    ; # Cron jobs for this time were already processed
        #else {
        if (true) {
            $cronJobs = $this->getCronJobs($day, $hour);
            
            #\REDCap::logEvent('REDCap-ETL cron - '.count($cronJobs).' cron jobs.');
        
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

                    if (!$adminConfig->getAllowCron()) {
                        # Cron jobs not allowed
                        $details = "Cron job failed - cron jobs not allowed\n".$details;
                        \REDCap::logEvent(self::RUN_LOG_ACTION, $details, $sql, $record, $event, $projectId);
                    } elseif (!$this->hasEtlUser($projectId)) {
                        # No user for this project has ETL permission
                        $details = "Cron job failed - no project user has ETL permission\n".$details;
                        \REDCap::logEvent(self::RUN_LOG_ACTION, $details, $sql, $record, $event, $projectId);
                    } else {
                        $etlConfig = $this->getConfiguration($configName, $projectId);
                        $isCronJob = true;
                        if (strcmp($serverName, ServerConfig::EMBEDDED_SERVER_NAME) === 0) {
                            #----------------------------------------------------
                            # Embedded server
                            #----------------------------------------------------
                            if (!$adminConfig->getAllowEmbeddedServer()) {
                                $details = "Cron job failed - embedded server not allowed\n".$details;
                                \REDCap::logEvent(self::RUN_LOG_ACTION, $details, $sql, $record, $event, $projectId);
                            } else {
                                $serverConfig = null;
                                    
                                if ($isCronJob && function_exists('pcntl_fork') && function_exists('pcntl_wait')) {
                                    $pid = pcntl_fork();
                                    if ($pid === -1) {
                                        # The fork was unsuccessful (and this is the only thread)
                                        $this->run($etlConfig, $serverConfig, $isCronJob);
                                    } elseif ($pid === 0) {
                                        # The fork was susccessful and this is the child process,
                                        $this->run($etlConfig, $serverConfig, $isCronJob);
                                        return;
                                    } else {
                                        ; # the fork worked, and this is the parent process
                                    }
                                } else {
                                    # Forking not supported; run serially
                                    $this->run($etlConfig, $serverConfig, $isCronJob);
                                }
                            }
                        } else {
                            #--------------------------------------------------------------
                            # Non-embedded server (standard or custom REDCap-ETL server)
                            #--------------------------------------------------------------
                            $serverConfig = $this->getServerConfig($serverName);
                            if ($serverConfig->getIsActive()) {
                                $this->run($etlConfig, $serverConfig, $isCronJob);
                            }
                        }
                    }
                } catch (\Exception $exception) {
                    $details = "Cron job failed\n".$details.'error: '.$exception->getMessage();
                    \REDCap::logEvent(self::RUN_LOG_ACTION, $details, $sql, $record, $event, $projectId);
                }
            }  # End foreach cron job
            
            # Set the last run time processed to this time, so that it won't be processed again
            $this->setLastRunTime($date, $hour);
                    
            # If forking is supported, wait for child processes (if any))
            if (function_exists('pcntl_fork') && function_exists('pcntl_wait')) {
                while (pcntl_wait($status) != -1) {
                    ; // Wait for all child processes to finish
                }
            }
        }
    }

    
    /**
     * Runs an ETL job. Top-level method for running an ETL job that all code should call,
     * so that there is one place to do REDCap logging
     *
     * @param Configuration $etlConfig REDCap-ETL configuration to run
     * @param ServerConfig $serverConfig server to run on; if empty, use embedded server
     * @param boolean $isCronJon indicates whether this is being called from a cron job or not.
     *
     * @return string the status of the run.
     */
    public function run($etlConfig, $serverConfig = null, $isCronJob = false)
    {
        $adminConfig = $this->getAdminConfig();
        
        $username   = $etlConfig->getUsername();
        $configName = $etlConfig->getName();
        $projectId  = $etlConfig->getProjectId();
        $configUrl  = $etlConfig->getProperty(Configuration::REDCAP_API_URL);
        
        if (!isset($serverConfig)) {
            $serverName = ServerConfig::EMBEDDED_SERVER_NAME;
        } else {
            $serverName = $serverConfig->getName();
        }

        $cron = 'no';
        if ($isCronJob) {
            $cron = 'yes';
        }
        
        $details = '';
        if (strcasecmp($configUrl, $this->getRedCapApiUrl()) !== 0) {
            $details .= "REDCap API URL: {$configUrl}\n";
        }
        $details .= "project ID: {$projectId}\n";
        if (!$isCronJob) {
            $details .= "user: {$username}\n";
        }
        $details .= "configuration: {$configName}\n";
        $details .= "server: {$serverName}\n";
        if ($isCronJob) {
            $details .= "cron: yes\n";
        } else {
            $details .= "cron: no\n";
        }
                    
        $status = '';
        try {
            $sql    = null;
            $record = null;
            $event  = self::LOG_EVENT;
            $projectId = null;
            
            if ($isCronJob) {
                $projectId = $etlConfig->getProjectId();
            }
            
            $details = "ETL job submitted \n".$details;
            \REDCap::logEvent(self::RUN_LOG_ACTION, $details, $sql, $record, $event, $projectId);
            
            #------------------------------------------------------------------------
            # If no server configuration was specified, run on the embedded server
            #------------------------------------------------------------------------
            if (empty($serverConfig)) {
                $status = $this->runEmbedded($etlConfig, $isCronJob);
            } else {
                # Run on the specified server
                $status = $serverConfig->run($etlConfig, $isCronJob);
            }
        } catch (\Exception $exception) {
            $details = "ETL job failed\n".$details
                .'error: '.$exception->getMessage();
            \REDCap::logEvent(self::RUN_LOG_ACTION, $details, $sql, $record, $event, $projectId);
            throw $exception;  # rethrow the exception
        }
        
        return $status;
    }
    

    /**
     * Runs an ETL configuration on the embedded server.
     */
    private function runEmbedded($etlConfiguration, $isCronJob = false)
    {
        $properties = $etlConfiguration->getPropertiesArray();
 
        $adminConfig = $this->getAdminConfig();
        
        $logger = new \IU\REDCapETL\Logger('REDCap-ETL');

        try {
            # Set the from e-mail address from the admin. configuration
            $properties[Configuration::EMAIL_FROM_ADDRESS] = $adminConfig->getEmbeddedServerEmailFromAddress();
        
            # Set the log file, and set print logging off
            $properties[Configuration::LOG_FILE]      = $adminConfig->getEmbeddedServerLogFile();
            $properties[Configuration::PRINT_LOGGING] = false;

            # Set process identifting properties
            $properties[Configuration::PROJECT_ID]   = $etlConfiguration->getProjectId();
            $properties[Configuration::CONFIG_NAME]  = $etlConfiguration->getName();
            $properties[Configuration::CONFIG_OWNER] = $etlConfiguration->getUsername();
            $properties[Configuration::CRON_JOB]     = $isCronJob;

            $redCapEtl = new \IU\REDCapETL\RedCapEtl($logger, $properties);
            $redCapEtl->run();
        } catch (\Exception $exception) {
            $logger->logException($exception);
            $logger->log('Processing failed.');
        }

        $status = implode("\n", $logger->getLogArray());
        return $status;
    }
    
    
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

    #-------------------------------------------------------------------
    # User ETL project methods
    #-------------------------------------------------------------------
        
    public function getUserEtlProjects($username = USERID)
    {
        return $this->settings->getUserEtlProjects($username);
    }
    
    public function hasEtlUser($projectId = PROJECT_ID)
    {
        return $this->settings->hasEtlUser($projectId);
    }
    
    public function isSuperUser()
    {
        return SUPER_USER;
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
        $dataExportRight = $this->getDataExportRight($username);
        $this->settings->addConfiguration($name, $username, $projectId, $dataExportRight);
        $details = 'REDCap-ETL configuration "'.$name.'" created.';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }


    /**
     * Copy configuration (only supports copying from/to same
     * user and project).
     */
    public function copyConfiguration($fromConfigName, $toConfigName)
    {
        if (Authorization::hasEtlConfigNamePermission($this, $configName, USERID, PROJECT_ID)) {
            $this->settings->copyConfiguration($fromConfigName, $toConfigName);
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
        if (Authorization::hasEtlConfigNamePermission($this, $configName, USERID, PROJECT_ID)) {
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
        if (Authorization::hasEtlConfigNamePermission($this, $configName, USERID, PROJECT_ID)) {
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
     * Gets the label for the specified exportRight.
     */
    public function getExportRightLabel($exportRight)
    {
        $label = '';
        switch ($exportRight) {
            case 0:
                $label = 'No access';
                break;
            case 1:
                $label = 'Full data set';
                break;
            case 2:
                $label = 'De-identified data';
                break;
            case 3:
                $label = 'No tagged identifiers';
                break;
        }
        return $label;
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
        
        if ($this->getDataExportRight($username) == 1) {
            # If user has "full data set" export permissions, improve performance by
            # avoiding individual configuration permission checks below
            $configNames = $allConfigNames;
        } else {
            foreach ($allConfigNames as $configName) {
                $config = $this->getConfiguration($configName, $projectId);
                if (Authorization::hasEtlConfigurationPermission($this, $config, $username)) {
                    array_push($configNames, $configName);
                }
            }
        }
        return $configNames;
    }
    
    /**
     * Gets the configuration from the request (if any), and
     * updates the user's session appropriately.
     */
    public function getConfigurationFromRequest()
    {
        $configuration = null;
        $configName = $_POST['configName'];
        if (empty($configName)) {
            $configName = $_GET['configName'];
            if (empty($configName)) {
                $configName = $_SESSION['configName'];
            }
        }
        
        if (!empty($configName)) {
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
        $this->settings->copyServer($fromServerName, $toServerName);
        $details = 'Server "'.$fromServerName.'" copied to "'.$toServerName.'".';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }
    
    public function renameServer($serverName, $newServerName)
    {
        $this->settings->renameServer($serverName, $newServerName);
        $details = 'Server "'.$serverName.'" renamed to "'.$newServerName.'".';
        \REDCap::logEvent(self::CHANGE_LOG_ACTION, $details, null, null, self::LOG_EVENT);
    }
    
    
    public function removeServer($serverName)
    {
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
        $this->settings->getLastRunTime();
    }

    public function setLastRunTime($date, $time)
    {
        $this->settings->setLastRunTime($date, $time);
        # Don't log this because it is an internal event
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
        $serversLabel = '<span class="glyphicon glyphicon-list" aria-hidden="true"></span>'
           .' ETL Servers';

        $serverConfigUrl = $this->getUrl(self::SERVER_CONFIG_PAGE);
        $serverConfigLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' ETL Server Config';

        $helpUrl = $this->getUrl(self::ADMIN_INFO_PAGE);
        $helpLabel = '&nbsp;<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>&nbsp;';
           
        $tabs = array();
        
        $tabs[$helpUrl]          = $helpLabel;
        
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
           .' ETL Configurations';

        #$addUrl = $this->getUrl('add.php');
        #$addLabel = '<span style="color: #008000;" class="glyphicon glyphicon-plus" aria-hidden="true"></span>'
        #   .' New Configuration';

        $configUrl = $this->getUrl('web/configure.php');
        $configLabel = '<span style="color: #808080;" class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' Configure';

        $scheduleUrl = $this->getUrl('web/schedule.php');
        $scheduleLabel = '<span class="glyphicon glyphicon-time" aria-hidden="true"></span>'
           .' Schedule';

        $runUrl = $this->getUrl('web/run.php');
        $runLabel = '<span style="color: #008000;" class="glyphicon glyphicon-play-circle" aria-hidden="true"></span>'
           .' Run';

        $userManualUrl = $this->getUrl('web/user_manual.php');
        $userManualLabel =
            '<span style="color: #000066;" class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>'
            .' User Manual';

        $adminConfig = $this->getAdminConfig();
        
        # Map for tabs from URL to the label for the URL
        $tabs = array();
        
        $tabs[$listUrl]     = $listLabel;
        
        $userEtlProjects = $this->getUserEtlProjects(USERID);
        
        if (Authorization::hasEtlProjectPagePermission($this, USERID)) {
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
    
    public function renderSuccessMessageDiv($message)
    {
        if (!empty($message)) {
            echo '<div align="center" class="darkgreen" style="margin: 20px 0;">'."\n";
            echo '<img src="'.(APP_PATH_IMAGES.'accept.png').'">';
            echo '&nbsp;'.Filter::escapeForHtml($message)."\n";
            echo "</div>\n";
        }
    }
    
    public function renderWarningMessageDiv($message)
    {
        if (!empty($message)) {
            echo '<div align="center" class="yellow" style="margin: 20px 0;">'."\n";
            echo '<img src="'.(APP_PATH_IMAGES.'warning.png').'" width="16px">';
            echo '&nbsp;'.Filter::escapeForHtml($message)."\n";
            echo "</div>\n";
        }
    }
           
    public function renderErrorMessageDiv($message)
    {
        if (!empty($message)) {
            echo '<div align="center" class="red" style="margin: 20px 0;">'."\n";
            echo '<img src="'.(APP_PATH_IMAGES.'exclamation.png').'">';
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
    
    

    /**
     * Checks if the user has permission to a user (non-admin) page, and
     * returns the configuration corresponding to the configuration name in
     * the request, if any. If the user does NOT have permission, the
     * request will be routed to an error page.
     *
     * @return Configuration if a configuration name was specified in the request, the
     *     configuration for that configuration name.
     */
    public function checkUserPagePermission($username = USERID)
    {
        $configuration = null;
        
        if (!Csrf::isValidRequest()) {
            # CSRF (Cross-Site Request Forgery) check failed; this should mean that either the
            # request is a CSRF attack or the user's session expired
            $accessUrl = $this->getUrl('web/access.php?accessError='.self::CSRF_ERROR);
            header('Location: '.$accessUrl);
            exit();
        } elseif (!Authorization::hasEtlRequestPermission($this, $username)) {
            # User does not have REDCap user rights to use ETL for this project
            $accessUrl = $this->getUrl('web/access.php?accessError='.self::USER_RIGHTS_ERROR);
            header('Location: '.$accessUrl);
            exit();
        } elseif (!Authorization::hasEtlProjectPagePermission($this, $username)) {
            # User has REDCap ETL user rights, but does not have specific ETL permission for this project
            $accessUrl = $this->getUrl('web/access.php?accessError='.self::NO_ETL_PROJECT_PERMISSION);
            header('Location: '.$accessUrl);
            exit();
        } else {
            # See if a configuration was specified in the request, and if so, check that the user
            # has permission to access it
            $configuration = $this->getConfigurationFromRequest();
            if (isset($configuration)) {
                if (!Authorization::hasEtlConfigurationPermission($this, $configuration, $username)) {
                    # User does not have permission to access the specified configuration
                    $accessUrl = $this->getUrl('web/access.php?accessError='.self::NO_CONFIGURATION_PERMISSION);
                    header('Location: '.$accessUrl);
                    exit();
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
        if (!SUPER_USER) {
            exit("Only super users can access this page!");
        } elseif (!Csrf::isValidRequest()) {
            exit(
                "Access not allowed. Your session may have expired."
                ." Please make sure you are logged in and try again."
            );
        }
    }
}

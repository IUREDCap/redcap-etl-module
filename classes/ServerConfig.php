<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\PublicKeyLoader;

class ServerConfig implements \JsonSerializable
{
    public const EMBEDDED_SERVER_NAME = '(embedded server)';

    public const AUTH_METHOD_SSH_KEY  = 0;
    public const AUTH_METHOD_PASSWORD = 1;

    public const ACCESS_LEVEL_ADMIN   = 'admin';
    public const ACCESS_LEVEL_PRIVATE = 'private';
    public const ACCESS_LEVEL_PUBLIC  = 'public';

    public const ACCESS_LEVELS = array(
        self::ACCESS_LEVEL_ADMIN,
        self::ACCESS_LEVEL_PRIVATE,
        self::ACCESS_LEVEL_PUBLIC
    );

    # Data load options for the embedded server
    public const DATA_LOAD_DB_AND_FILE = 'data-load-db-and-file';
    public const DATA_LOAD_DB_ONLY     = 'data-load-db-only';
    public const DATA_LOAD_FILE_ONLY   = 'data-load-file-only';

    private $name;

    /** @var boolean indicates if the server is active or not; inactive servers
                     don't show up as choices for users. */
    private $isActive;

    private $accessLevel; # who is allowed to run the server

    private $dataLoadOptions; # where data can be loaded (csv, db, csv and db)

    private $serverAddress; # address of REDCap-ETL server
    private $authMethod;
    private $username;
    private $password;
    private $sshKeyFile;
    private $sshKeyPassword;

    private $configDir;

    private $etlCommand;  # full path of command to run on REDCap-ETL server
    private $etlCommandPrefix;
    private $etlCommandSuffix;

    private $logFile;

    private $emailFromAddress;
    private $enableErrorEmail;
    private $enableSummaryEmail;

    # Database SSL verification
    private $dbSsl;
    private $dbSslVerify;
    private $caCertFile;

    private $maxZipDownloadFileSize;

    #----------------------------------------------------
    # Run settings - originally, this was set globally.
    #     $useCustomRunSettings exists to maintain
    #     backward compatibility, and by default is set
    #     to false.
    #----------------------------------------------------
    private $allowOnDemandRun;
    private $allowCronRun;


    public function __construct($name)
    {
        self::validateName($name);

        $this->name = $name;

        $this->isActive = false;

        $this->accessLevel = 'public';

        $this->dataLoadOptions = self::DATA_LOAD_DB_AND_FILE;  # By default, set to both
                                                               # database and file as data load options

        $this->authMethod = self::AUTH_METHOD_SSH_KEY;
        $this->sshKeyPassword = '';

        $this->etlCommand = '';
        $this->etlCommandPrefix = 'nohup';
        $this->etlCommandSuffix = '> /dev/null 2>&1 &';

        $this->logFile = '';

        $this->emailFromAddress = '';
        $this->enableErrorEmail   = false;
        $this->enableSummaryEmail = false;

        if ($this->isEmbeddedServer()) {
            $this->enableErrorEmail   = true;
            $this->enableSummaryEmail = true;

            $homepageContactEmail = '';
            // phpcs:disable
            if (isset($homepage_contact_email)) {
                $homepageContactEmail = $homepage_contact_email;
            }
            // phpcs:enable
            $this->emailFromAddress = $homepageContactEmail;
        }

        $this->dbSsl = true;
        $this->dbSslVerify = false;
        $this->caCertFile = '';

        $this->maxZipDownloadFileSize = DataTarget::DEFAULT_MAX_ZIP_DOWNLOAD_FILESIZE;

        # Run settings
        $this->allowOnDemandRun = true;
        $this->allowCronRun     = true;
    }

    /**
     * Sets the server configuration's properties.
     */
    public function set($properties)
    {
        foreach (get_object_vars($this) as $var => $value) {
            switch ($var) {
                # convert true/false property values to boolean
                case 'enableErrorEmail':
                case 'enableSummaryEmail':
                case 'isActive':
                case 'dbSsl':
                case 'dbSslVerify':
                case 'allowOnDemandRun':
                case 'allowCronRun':
                    # NOTE: THESE CHANGES MESS UP OTHER STUFF:
                    # changed value assignment to '' instead of false
                    # because redcap-etl WorkflowConfig evaluated boolean
                    # false as true for some reason. For instance, an error
                    # would be generated for db_ssl_verify if it was unchecked
                    # and set to false. But setting it to '' instead of false
                    # was processed correctly by redcap-etl WorkflowConfig
                    # and no error was generated.
                    if (!array_key_exists($var, $properties)) {
                        $this->$var = false;
                        ###$this->$var = '';
                    } else {
                        if ($properties[$var]) {
                            $this->$var = true;
                        } else {
                            $this->$var = false;
                            ###$this->$var = '';
                        }
                    }
                    break;
                default:
                    if (array_key_exists($var, $properties)) {
                        $this->$var = Filter::sanitizeString($properties[$var]);
                        $this->$var = trim($this->$var);
                    }
                    break;
            }
        }
    }

    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    /**
     * Sets the ServerConfig to the speicifed JSON.
     */
    public function fromJson($json, $defaultAllowOnDemandRun = true, $defaultAllowCronRun = true)
    {
        if (!empty($json)) {
            $object = json_decode($json);
            foreach (get_object_vars($this) as $var => $value) {
                $this->$var = $object->$var;
            }

            # If access level is unset, set it to public
            if (!isset($this->accessLevel)) {
                $this->accessLevel = 'public';
            }

            # Set run settings if missing for backward compatibility
            if (!isset($this->allowOnDemandRun)) {
                $this->allowOnDemandRun = $defaultAllowOnDemandRun;
            }

            if (!isset($this->allowCronRun)) {
                $this->allowCronRun = $defaultAllowCronRun;
            }

            if (!isset($this->dataLoadOptions)) {
                $this->dataLoadOptions = self::DATA_LOAD_DB_AND_FILE;
            }
        }
    }

    public function toJson()
    {
        $json = json_encode($this);
        return $json;
    }


    private function createServerErrorMessageForUser($message)
    {
        $errorMessage = 'Server "' . $this->name . '" has the following configuration error: '
            . $message . '. Please contact your system administrator or use another server.';
        return $errorMessage;
    }


    /**
     * Modifies an ETL configuration based on this server configuration's
     * properties. When running REDCap-ETL using the external module,
     * some of the properties for an ETL configuration are
     * are server-wide properties that are set by an admin (e.g., the from e-mail address).
     * There server-wide properties need to be merged into the user
     * properties.
     *
     * @param Configuration the ETL configuration to modify.
     * @param boolean $isCronJobs if the configuration is being run as a cron job.
     * @param boolean $isWorkflow is the configuration is a workflow.
     */
    public function updateEtlConfig(&$etlConfig, $isCronJob)
    {
        $etlConfig->setProperty(Configuration::CRON_JOB, $isCronJob);

        $etlConfig->setProperty(Configuration::LOG_FILE, $this->logFile);

        $projectId   = $etlConfig->getProjectId();
        $configName  = $etlConfig->getName();
        $configOwner = $etlConfig->getUsername();

        $etlConfig->setProperty(Configuration::PROJECT_ID, $projectId);
        $etlConfig->setProperty(Configuration::CONFIG_NAME, $configName);
        $etlConfig->setProperty(Configuration::CONFIG_OWNER, $configOwner);

        $etlConfig->setProperty(Configuration::EMAIL_FROM_ADDRESS, $this->emailFromAddress);

        # If e-mailing of errors has not been enabled for this server, make sure that
        # the "e-mail errors" property is set to false in the ETL configuration
        if (!$this->getEnableErrorEmail()) {
            $etlConfig->setProperty(Configuration::EMAIL_ERRORS, false);
        }

        # If e-mailing of a summary has not been enabled for this server, make sure that
        # the "e-mail summary" property is set to false in the ETL configuration
        if (!$this->getEnableSummaryEmail()) {
            $etlConfig->setProperty(Configuration::EMAIL_SUMMARY, false);
        }

        $etlConfig->setProperty(Configuration::DB_SSL, $this->getDbSsl());
        $etlConfig->setProperty(Configuration::DB_SSL_VERIFY, $this->getDbSslVerify());
        $etlConfig->setProperty(Configuration::CA_CERT_FILE, $this->getCaCertFile());
    }

    /**
     * Updated properties with the value set by the server. This is used
     * for updating global properties in a workflow.
     */
    public function updateEtlProperties(&$properties)
    {
        $properties[Configuration::LOG_FILE] = $this->logFile;
        $properties[Configuration::EMAIL_FROM_ADDRESS] = $this->emailFromAddress;

        # If e-mailing of errors has not been enabled for this server, make sure that
        # the "e-mail errors" property is set to false in the ETL configuration
        if (!$this->getEnableErrorEmail()) {
            $properties[Configuration::EMAIL_ERRORS] = 0;
        }

        # If e-mailing of a summary has not been enabled for this server, make sure that
        # the "e-mail summary" property is set to false in the ETL configuration
        if (!$this->getEnableSummaryEmail()) {
            $properties[Configuration::EMAIL_SUMMARY] = 0;
        }

        $properties[Configuration::DB_SSL]        = $this->getDbSsl();
        $properties[Configuration::DB_SSL_VERIFY] = $this->getDbSslVerify();
        $properties[Configuration::CA_CERT_FILE]  = $this->getCaCertFile();
    }

    public function canLoadDataToDatabase()
    {
        $canLoadDataToDatabase = false;

        if ($this->isEmbeddedServer()) {
            if (
                $this->dataLoadOptions === self::DATA_LOAD_DB_AND_FILE
                || $this->dataLoadOptions === self::DATA_LOAD_DB_ONLY
            ) {
                $canLoadDataToDatabase = true;
            }
        } else {
            # Non-embedded server can only download data to database
            $canLoadDataToDatabase = true;
        }

        return $canLoadDataToDatabase;
    }

    public function canLoadDataToFiles()
    {
        $canLoadDataToFiles = false;

        if ($this->isEmbeddedServer()) {
            # Currently, only embedded servers are able to download files

            if (
                $this->dataLoadOptions === self::DATA_LOAD_DB_AND_FILE
                || $this->dataLoadOptions === self::DATA_LOAD_FILE_ONLY
            ) {
                $canLoadDataToFiles = true;
            }
        }

        return $canLoadDataToFiles;
    }


    /**
     * Run the ETL process for this server.
     *     If boolean $runWorkflow is true, then $etlConfig is type array.
     *     Otherwise, it is type Configuration.
     *
     * @param mixed $etlConfig the ETL configuration to run.
     * @param boolean $isCronJob indicates if this run is a cron job.
     * @param ModuleLog $moduleLog Logging object for the REDCap external module log.
     *      A method from this object is passed to the REDCap-ETL logger to be used
     *      as a callback when logging is done so that the logger will  also log to
     *      the REDCap external module log.
     * @param boolean $runWorkflow indicates if a workflow is being run.
     */
    public function run(
        $etlConfig,
        $isCronJob = false,
        $moduleLog = null,
        $runWorkflow = false,
        $dataTarget = DataTarget::DB
    ) {
        if (!isset($etlConfig)) {
            $message = 'No ETL configuration specified.';
            throw new \Exception($message);
        }

        if (!$this->getIsActive()) {
            $message = 'Server "' . $this->name . '" is set as inactive.';
            throw new \Exception($message);
        }

        $workflowConfig = null;
        if (!$runWorkflow) {
            $this->updateEtlConfig($etlConfig, $isCronJob);
        } else {
            $workflowConfig = $etlConfig;

            $this->updateEtlConfig($workflowConfig->getGlobalPropertiesConfig(), $isCronJob);

            foreach ($workflowConfig->getTaskConfigs() as $etlConfig) {
                $this->updateEtlConfig($etlConfig, $isCronJob);
            }
        }

        if ($this->isEmbeddedServer()) {
            #-------------------------------------------------
            # Embedded server
            #-------------------------------------------------
            if ($runWorkflow) {
                ### OLD CODE:
                ###$properties = $etlConfig;
                ###$properties[Configuration::PRINT_LOGGING] = false;
                #print "<hr/>SERVER CONFIG PROPERTIES:<br/>\n";
                #print "<pre>\n";
                #print_r($properties);
                #print "</pre>\n";
                ### NEW CODE:
                $properties = $workflowConfig->toArray();
            } else {
                $properties = $etlConfig->getPropertiesArray();
                $properties[Configuration::PRINT_LOGGING] = false;

                #--------------------------------------------------
                # If CSV Zip file, update db_type and db_connection.
                #--------------------------------------------------
                if ($dataTarget === DataTarget::CSV_ZIP) {
                    $properties[Configuration::DB_TYPE] = DataTarget::DBTYPE_CSV;

                    # Create temporary directory to store CSV files in
                    $tempDir = sys_get_temp_dir();
                    # If the directory doesn't end with a separator, add one
                    if (substr($tempDir, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
                        $tempDir .= DIRECTORY_SEPARATOR;
                    }
                    $tempDir .= uniqid('etl-csv-', true) . DIRECTORY_SEPARATOR;
                    $result = mkdir($tempDir, 0700);
                    if ($result === false) {
                        $message = 'Unable to create directory for CSV files.';
                        throw new \Exception($message);
                    }
                    $properties[Configuration::DB_CONNECTION] = DataTarget::DBTYPE_CSV . ':' . $tempDir;
                }
            }

            #print("<pre>\n");
            #print_r($properties);
            #print("</pre>\n");

            $logger = new \IU\REDCapETL\Logger('REDCap-ETL');
            $logId = $logger->getLogId();

            if (isset($moduleLog)) {
                $logger->setLoggingCallback(array($moduleLog, 'logEtlRunMessage'));
            }

            $redCapEtl = null;
            try {
                #$redcapProjectClass = EtlExtRedCapProject::class;
                $redcapProjectClass = null;
                $baseDir = null;

                $redCapEtl = new \IU\REDCapETL\RedCapEtl($logger, $properties, $redcapProjectClass, $baseDir);
                $redCapEtl->run();

                if ($runWorkflow) {
                    // If this is a workflow, reset the logger to the workflow logger.
                    $logger = $redCapEtl->getWorkflowConfig()->getLogger();
                    $output = implode("\n", $logger->getLogArray());
                } else {
                    switch ($dataTarget) {
                        case DataTarget::DB:
                            $output = implode("\n", $logger->getLogArray());
                            break;
                        case DataTarget::CSV_ZIP:
                            $pid = $properties['project_id'];
                            $dataTarget = new DataTarget();
                            $result = $dataTarget->exportEtlCsvZip($tempDir, $pid);

                            #If the filesize is larger than the allowable filesize, then send
                            #a message instead of the zip file.
                            $fileSize = filesize($result);
                            $fileSizeMB = $fileSize / 1024 / 1024;
                            $maxFileSizeMB = $this->getMaxZipDownloadFileSize();

                            if (is_null($maxFileSizeMB) || ($maxFileSizeMB === '')) {
                                $maxFileSizeMB = $dataTarget::DEFAULT_MAX_ZIP_DOWNLOAD_FILESIZE;
                            }

                            if ($fileSizeMB > $maxFileSizeMB) {
                                $message = "CSV zip file size ({$fileSizeMB} MB) exceeds" .
                                   " max of {$maxFileSizeMB} MB. Cannot download.";
                                throw new \Exception($message);
                            } else {
                                $output = $result;
                            }

                            break;
                    }
                }
            } catch (\Exception $exception) {
                $logger->logException($exception);
                $logger->log('Processing failed.');
                $output = "ERROR: " . $exception->getMessage();
            }
        } else {
            #-----------------------------------------------
            # Remote Server
            #-----------------------------------------------

            #----------------------------------------
            # Get email logging information
            #----------------------------------------
            $configName  = $etlConfig->getProperty(Configuration::CONFIG_NAME);
            $projectId   = $etlConfig->getProperty(Configuration::PROJECT_ID);
            $emailErrors = $etlConfig->getProperty(Configuration::EMAIL_ERRORS);
            $fromEmail   = $etlConfig->getProperty(Configuration::EMAIL_FROM_ADDRESS);
            $toEmails    = $etlConfig->getProperty(Configuration::EMAIL_TO_LIST);
            $subject     = $etlConfig->getProperty(Configuration::EMAIL_SUBJECT);

            #-------------------------------------------------
            # Connect to remote server
            #-------------------------------------------------
            $ssh = null;
            try {
                $ssh = $this->getRemoteConnection();
            } catch (\Exception $sshException) {
                $message = 'ERROR: Connection to remote REDCap-ETL server "' . $this->name . '"'
                    . ' failed for REDCap-ETL configuration "'
                    . $configName . '" for project ID ' . $projectId . ': '
                    . $sshException->getMessage();

                if ($emailErrors) {
                    # Try to e-mail error to REDCap-ETL user(s)
                    try {
                        \REDCap::email($toEmails, $fromEmail, $subject, $message);
                    } catch (\Exception $exception) {
                        ; # Tried to send e-mai, but it failed
                    }
                }

                # Try to mail error to REDCap Admin
                if (!empty($this->emailFromAddress)) {
                    try {
                        $emailSubject = 'Remote REDCap-ETL server connection error';
                        \REDCap::email($this->emailFromAddress, $fromEmail, $emailSubject, $message);
                    } catch (\Exception $exception) {
                        ; # Tried to send e-mai, but it failed
                    }
                }

                throw $sshException; // rethrow exception
            }

            #------------------------------------------------
            # Copy configuration file and transformation
            # rules file (if any) to the server.
            #------------------------------------------------
            if ($runWorkflow) {
                ## OLD CODE: $propertiesJson = Configuration::getRedCapEtlJsonProperties($runWorkflow, $etlConfig);
                $propertiesJson = $workflowConfig->toJson();
            } else {
                $propertiesJson = $etlConfig->getRedCapEtlJsonProperties($runWorkflow);
            }

            $fileNameSuffix = uniqid('', true);
            $configFileName = 'etl_config_' . $fileNameSuffix . '.json';
            $configFilePath = $this->configDir . '/' . $configFileName;

            $sftpResults = false;
            $putException = null;
            try {
                $sftpResult = $ssh->put($configFilePath, $propertiesJson);
            } catch (\Exception $exception) {
                $sftpResults = false;
                $putException = $exception;
            }

            if (!$sftpResult) {
                $message = 'Copy of REDCap-ETL configuration "' . $configName
                    . '" for project ID ' . $projectId . ' to server "'
                    . $this->name . '" failed.'
                    ;

                if (isset($putException)) {
                    $message .= ' Error: ' . $putException->getMessage();
                }

                $error = error_get_last();
                if (isset($error) && is_array($error) && array_key_exists('message', $error)) {
                    $message .= " Error: " . $error['message'];
                }

                # If set in configuration, e-mail users that there was an error
                if ($etlConfig->getProperty(Configuration::EMAIL_ERRORS) === true) {
                    # Try to e-mail error to REDCap-ETL user(s)
                    try {
                        \REDCap::email($toEmails, $fromEmail, $subject, $message);
                    } catch (\Exception $exception) {
                        ; # Tried to send e-mai, but it failed; message will be logged by code below
                    }
                }

                # Try to e-mail error tp REDCap admin
                if (!empty($this->emailFromAddress)) {
                    try {
                        # Note: if subject is too long, it gets removed
                        $emailSubject = 'Remote REDCap-ETL server ETL config copy error';
                        \REDCap::email($this->emailFromAddress, $fromEmail, $emailSubject, $message);
                    } catch (\Exception $exception) {
                        ; # Tried to send e-mai, but it failed
                    }
                }

                throw new \Exception($message);
            }

            #$ssh->setTimeout(1);

            $command = $this->etlCommandPrefix . ' ' . $this->etlCommand . ' '
                . $configFilePath . ' ' . $this->etlCommandSuffix;

            #\REDCap::logEvent('REDCap-ETL run command: '.$command);

            $ssh->setTimeout(1.0);  # to prevent blocking

            $execOutput = $ssh->exec($command);

            $output = 'Your job has been submitted to server "' . $this->getName() . '".' . "\n";
            #\REDCap::logEvent('REDCap-ETL run output: '.$output);
        }  // End else not embedded server

        return $output;
    }

    /**
     * Returns an ssh/ftp connection for the server represented by this class.
     *
     * @return SFTP ssh/sftp connection.
     */
    public function getRemoteConnection()
    {
        $ssh = null;

        if (empty($this->serverAddress)) {
            $message = $this->createServerErrorMessageForUser('no server address specified');
            throw new \Exception($message);
        }

        if ($this->authMethod == self::AUTH_METHOD_PASSWORD) {
            $ssh = new SFTP($this->serverAddress);  # SFTP class provides SSH functionality also
            $ssh->login($this->username, $this->password);
        } elseif ($this->authMethod == self::AUTH_METHOD_SSH_KEY) {
            $keyFile = $this->getSshKeyFile();

            if (empty($keyFile)) {
                $message = $this->createServerErrorMessageForUser('no SSH key file was specified');
                throw new \Exception($message);
            }

            $keyFileContents = file_get_contents($keyFile);

            if ($keyFileContents === false) {
                $message = $this->createServerErrorMessageForUser('SSH key file could not be accessed');
                throw new \Exception($message);
            }

            $key = PublicKeyLoader::load($keyFileContents, $this->sshKeyPassword);
            $ssh = new SFTP($this->serverAddress);
            $ssh->login($this->username, $key);
        } else {
            $message = $this->createServerErrorMessageForUser('unrecognized authentication menthod');
            throw new \Exception($message);
        }
        return $ssh;
    }

    public function test()
    {
        $testOutput = '';
        try {
            if ($this->isEmbeddedServer()) {
                $testOutput = 'REDCap-ETL ' . \IU\REDCapETL\Version::RELEASE_NUMBER . ' found.';
            } else {
                $ssh = $this->getRemoteConnection();

                if ($this->getAuthMethod() == ServerConfig::AUTH_METHOD_SSH_KEY) {
                     $authMethod = "SSH key";
                } else {
                     $authMethod = "password";
                }

                $output = $ssh->exec('hostname');

                if (!$output) {
                    $err = $ssh->getLastError();
                    $testOutput = "ERROR: ssh command failed. Server: '{$this->serverAddress}'."
                        . " Username: '{$this->username}' . Authentication method: {$authMethod}."
                        ;
                } else {
                    $testOutput = "SUCCESS:\noutput of hostname command:\n"
                        . $output . "\n";
                }
            }
        } catch (\Exception $exception) {
            $testOutput = 'ERROR: ' . $exception->getMessage();
        }
        return $testOutput;
    }

    public function validate()
    {
        self::validateName($this->name);
    }

    public static function validateName($name)
    {
        $matches = array();
        if (empty($name)) {
            throw new \Exception('No server configuration name specified.');
        } elseif (!is_string($name)) {
            throw new \Exception('Server configuration name is not a string; has type: ' . gettype($name) . '.');
        } elseif (preg_match('/([^a-zA-Z0-9_\- .])/', $name, $matches) === 1) {
            if (strcasecmp($name, self::EMBEDDED_SERVER_NAME) !== 0) {
                $errorMessage = 'Invalid character in server configuration name: ' . $matches[0];
                throw new \Exception($errorMessage);
            }
        }
        return true;
    }

    /**
     * Indicates if the server is an embedded server.
     *
     * @return boolean true if the server is an embedded server, and false if the server
     *     is a remote server.
     */
    public function isEmbeddedServer()
    {
        $isEmbedded = false;
        if (strcasecmp($this->name, ServerConfig::EMBEDDED_SERVER_NAME) === 0) {
            $isEmbedded = true;
        }
        return $isEmbedded;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    public function getDataLoadOptions()
    {
        return $this->dataLoadOptions;
    }

    public function getServerAddress()
    {
        return $this->serverAddress;
    }

    public function getAuthMethod()
    {
        return $this->authMethod;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSshKeyFile()
    {
        return $this->sshKeyFile;
    }

    public function getSshKeyPassword()
    {
        return $this->sshKeyPassword;
    }

    public function getConfigDir()
    {
        return $this->configDir;
    }


    public function getEtlCommand()
    {
        return $this->etlCommand;
    }

    public function getEtlCommandPrefix()
    {
        return $this->etlCommandPrefix;
    }

    public function getEtlCommandSuffix()
    {
        return $this->etlCommandSuffix;
    }

    public function getLogFile()
    {
        return $this->logFile;
    }

    public function getEmailFromAddress()
    {
        return $this->emailFromAddress;
    }

    public function getEnableErrorEmail()
    {
        return $this->enableErrorEmail;
    }

    public function getEnableSummaryEmail()
    {
        return $this->enableSummaryEmail;
    }

    public function getDbSsl()
    {
        return $this->dbSsl;
    }

    public function getDbSslVerify()
    {
        return $this->dbSslVerify;
    }

    public function getCaCertFile()
    {
        return $this->caCertFile;
    }

    public function getAccessLevel()
    {
        return $this->accessLevel;
    }

    public function setAccessLevel($accessLevel)
    {
        $this->accessLevel = $accessLevel;
    }

    public function isPublic()
    {
        $isPublic = $this->accessLevel === self::ACCESS_LEVEL_PUBLIC;
        return $isPublic;
    }

    public function isPrivate()
    {
        $isPrivate = $this->accessLevel === self::ACCESS_LEVEL_PRIVATE;
        return $isPrivate;
    }

    public function getMaxZipDownloadFileSize()
    {
        return $this->maxZipDownloadFileSize;
    }

    public function setMaxZipDownloadFileSize($maxZipDownloadFileSize)
    {
        $this->maxZipDownloadFileSize = $maxZipDownloadFileSize;
    }

    public function getAllowOnDemandRun()
    {
        return $this->allowOnDemandRun;
    }

    public function getAllowCronRun()
    {
        return $this->allowCronRun;
    }
}

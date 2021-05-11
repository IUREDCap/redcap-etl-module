<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SCP;
use phpseclib\Net\SFTP;
use phpseclib\Net\SSH2;

class ServerConfig implements \JsonSerializable
{
    const EMBEDDED_SERVER_NAME = '(embedded server)';
    
    const AUTH_METHOD_SSH_KEY  = 0;
    const AUTH_METHOD_PASSWORD = 1;

    const ACCESS_LEVELS = array('admin','private','public');
    
    private $name;
    
    /** @var boolean indicates if the server is active or not; inactive servers
                     don't show up as choices for users. */
    private $isActive;
    
    private $accessLevel; #who is allowed to run the server

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


    public function __construct($name)
    {
        self::validateName($name);
        
        $this->name = $name;
        
        $this->isActive = false;

        $this->accessLevel = 'public';

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
        
            // phpcs:disable
            $homepageContactEmail = $homepage_contact_email;
            // phpcs:enable
            $this->emailFromAddress = $homepageContactEmail;
        }
        
        $this->dbSsl = true;
        $this->dbSslVerify = false;
        $this->caCertFile = '';
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
                    if (!array_key_exists($var, $properties)) {
                        $this->$var = false;
                    } else {
                        if ($properties[$var]) {
                            $this->$var = true;
                        } else {
                            $this->$var = false;
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

    public function fromJson($json)
    {
        if (!empty($json)) {
            $object = json_decode($json);
            foreach (get_object_vars($this) as $var => $value) {
                $this->$var = $object->$var;
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
     * properties.
     *
     * @param Configuration the ETL configuration to modify.
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
     * Run the ETL process for this server.
     *
     * @param Configuration $etlConfig the ETL configuration to run.
     * @param boolean $isCronJob indicates if this run is a cron job.
     */
    public function run($etlConfig, $isCronJob = false, $moduleLog = null)
    {
        if (!isset($etlConfig)) {
            $message = 'No ETL configuration specified.';
            throw new \Exception($message);
        }
        
        if (!$this->getIsActive()) {
            $message = 'Server "' . $this->name . '" is set as inactive.';
            throw new \Exception($message);
        }

        $this->updateEtlConfig($etlConfig, $isCronJob);
        
        if ($this->isEmbeddedServer()) {
            #-------------------------------------------------
            # Embedded server
            #-------------------------------------------------
            $properties = $etlConfig->getPropertiesArray();
            $properties[Configuration::PRINT_LOGGING] = false;
            
            # Set a syntactically valid API token to avoid getting an error,
            # which would not be valid in this case, since the REDCap Project class
            # being used here does not need an API token
            #$properties[Configuration::DATA_SOURCE_API_TOKEN] = '12345678901234567890123456789012';
            
            $logger = new \IU\REDCapETL\Logger('REDCap-ETL');
            $logId = $logger->getLogId();

            if (isset($moduleLog)) {
                $logger->setLoggingCallback(array($moduleLog, 'logEtlRunMessage'));
            }

            try {
                #$redcapProjectClass = EtlExtRedCapProject::class;
                $redcapProjectClass = null;
                $redCapEtl = new \IU\REDCapETL\RedCapEtl($logger, $properties, null, $redcapProjectClass);
                $redCapEtl->run();
            } catch (\Exception $exception) {
                $logger->logException($exception);
                $logger->log('Processing failed.');
            }

            $output = implode("\n", $logger->getLogArray());
        } else {
            #-------------------------------------------------
            # Remote server
            #-------------------------------------------------

            if (empty($this->serverAddress)) {
                $message = $this->createServerErrorMessageForUser('no server address specified');
                throw new \Exception($message);
            }
            
            if ($this->authMethod == self::AUTH_METHOD_PASSWORD) {
                $ssh = new SSH2($this->serverAddress);
                $ssh->login($username, $this->password);
            } elseif ($this->authMethod == self::AUTH_METHOD_SSH_KEY) {
                $keyFile = $this->getSshKeyFile();
                
                if (empty($keyFile)) {
                    $message = $this->createServerErrorMessageForUser('no SSH key file was specified');
                    throw new \Exception($message);
                }
                
                #\REDCap::logEvent('REDCap-ETL run current user: '.get_current_user());
                            
                $key = new RSA();
                $key->setPassword($this->sshKeyPassword);
                
                $keyFileContents = file_get_contents($keyFile);
                
                if ($keyFileContents === false) {
                    $message = $this->createServerErrorMessageForUser('SSH key file could not be accessed');
                    throw new \Exception($message);
                }
                $key->loadKey($keyFileContents);
                $ssh = new SSH2($this->serverAddress);
                $ssh->login($this->username, $key);
            } else {
                $message = $this->createServerErrorMessageForUser('unrecognized authentication menthod');
                throw new \Exception($message);
            }
                    
            #------------------------------------------------
            # Copy configuration file and transformation
            # rules file (if any) to the server.
            #------------------------------------------------
            $fileNameSuffix = uniqid('', true);
            $scp = new SCP($ssh);
            
            $propertiesJson = $etlConfig->getRedCapEtlJsonProperties();
            $configFileName = 'etl_config_' . $fileNameSuffix . '.json';
            $configFilePath = $this->configDir . '/' . $configFileName;

            $scpResult = $scp->put($configFilePath, $propertiesJson);
            if (!$scpResult) {
                $message = 'Copy of ETL configuration to server failed.'
                    . ' Please contact your system administrator for assistance.';
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
    
    public function test()
    {
        $testOutput = '';
        try {
            if ($this->isEmbeddedServer()) {
                $testOutput = 'REDCap-ETL ' . \IU\REDCapETL\Version::RELEASE_NUMBER . ' found.';
            } else {
                $serverAddress = $this->getServerAddress();
                if (empty($serverAddress)) {
                    throw new \Exception('No server address found.');
                }
                        
                $username = $this->getUsername();
                if ($this->getAuthMethod() == ServerConfig::AUTH_METHOD_SSH_KEY) {
                    $keyFile = $this->getSshKeyFile();
                    if (empty($keyFile)) {
                        throw new \Exception('SSH key file cound not be found.');
                    }
                                
                    $keyPassword = $this->getSshKeyPassword();
                                    
                    $key = new RSA();
                    
                    #if (!empty($keyPassword)) {
                        $key->setPassword($keyPassword);
                    #}
                
                    $keyFileContents = file_get_contents($keyFile);
                    if ($keyFileContents === false) {
                        throw new \Exception('SSH key file could not be accessed.');
                    }
                    $key->loadKey($keyFileContents);

                    $ssh = new SSH2($serverAddress);
                    $ssh->login($username, $key);
                } else {
                    $password = $this->getPassword();
                    
                    $ssh = new SSH2($serverAddress);
                    $ssh->login($username, $password);
                }

                $output = $ssh->exec('hostname');
                if (!$output) {
                    $testOutput = "ERROR: ssh command failed.";
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
    
     /**
     * Run the ETL process for a workflow.
     *
     */
    public function runWorkflow($workflow, $isCronJob = false, $moduleLog = null)
    {
        if (!isset($workflow)) {
            $message = 'No Workflow specified.';
            throw new \Exception($message);
        }
        
        if (!$this->getIsActive()) {
            $message = 'Server "' . $this->name . '" is set as inactive.';
            throw new \Exception($message);
        }

        if ($this->isEmbeddedServer()) {
            #-------------------------------------------------
            # Embedded server
            #-------------------------------------------------
            #$properties = $etlConfig->getPropertiesArray();
            #$properties[Configuration::PRINT_LOGGING] = false;
            
            # Set a syntactically valid API token to avoid getting an error,
            # which would not be valid in this case, since the REDCap Project class
            # being used here does not need an API token
            #$properties[Configuration::DATA_SOURCE_API_TOKEN] = '12345678901234567890123456789012';
            
            $logger = new \IU\REDCapETL\Logger('REDCap-ETL');
            $logId = $logger->getLogId();

            if (isset($moduleLog)) {
                $logger->setLoggingCallback(array($moduleLog, 'logEtlRunMessage'));
            }

            try {
                #$redcapProjectClass = EtlExtRedCapProject::class;
                $redcapProjectClass = null;
                $redCapEtl = new \IU\REDCapETL\RedCapEtl($logger, $workflow, null, $redcapProjectClass);
                #$redCapEtl->run();
            } catch (\Exception $exception) {
                $logger->logException($exception);
                $logger->log('Processing failed.');
            }

            $output = implode("\n", $logger->getLogArray());
        }  else {
            #-------------------------------------------------
            # Remote server
            #-------------------------------------------------

            if (empty($this->serverAddress)) {
                $message = $this->createServerErrorMessageForUser('no server address specified');
                throw new \Exception($message);
            }
            
            if ($this->authMethod == self::AUTH_METHOD_PASSWORD) {
                $ssh = new SSH2($this->serverAddress);
                $ssh->login($username, $this->password);
            } elseif ($this->authMethod == self::AUTH_METHOD_SSH_KEY) {
                $keyFile = $this->getSshKeyFile();
                
                if (empty($keyFile)) {
                    $message = $this->createServerErrorMessageForUser('no SSH key file was specified');
                    throw new \Exception($message);
                }
                
                #\REDCap::logEvent('REDCap-ETL run current user: '.get_current_user());
                            
                $key = new RSA();
                $key->setPassword($this->sshKeyPassword);
                
                $keyFileContents = file_get_contents($keyFile);
                
                if ($keyFileContents === false) {
                    $message = $this->createServerErrorMessageForUser('SSH key file could not be accessed');
                    throw new \Exception($message);
                }
                $key->loadKey($keyFileContents);
                $ssh = new SSH2($this->serverAddress);
                $ssh->login($this->username, $key);
            } else {
                $message = $this->createServerErrorMessageForUser('unrecognized authentication menthod');
                throw new \Exception($message);
            }
                    
            #------------------------------------------------
            # Copy configuration file and transformation
            # rules file (if any) to the server.
            #------------------------------------------------
            $fileNameSuffix = uniqid('', true);
            $scp = new SCP($ssh);
            
            $propertiesJson = $etlConfig->getRedCapEtlJsonProperties();
            $configFileName = 'etl_config_' . $fileNameSuffix . '.json';
            $configFilePath = $this->configDir . '/' . $configFileName;

            $scpResult = $scp->put($configFilePath, $propertiesJson);
            if (!$scpResult) {
                $message = 'Copy of ETL configuration to server failed.'
                    . ' Please contact your system administrator for assistance.';
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
    
}

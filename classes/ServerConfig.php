<?php

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
    
    private $name;
    
    /** @var boolean indicates if the server is active or not; inactive servers
                     don't show up as choices for users. */
    private $isActive;
    
    private $serverAddress; # address of REDCap-ETL server
    private $authMethod;
    private $username;
    private $password;
    private $sshKeyFile;
    private $sshKeyPassword;

    private $configDir;
    private $etlCommand;  # full path of command to run on REDCap-ETL server

    private $logFile;
    
    private $emailFromAddres;
    private $enableErrorEmail;
    private $enableSummaryEmail;


    public function __construct($name)
    {
        self::validateName($name);
        
        $this->name = $name;
        
        $this->isActive = false;
        
        $this->authMethod = self::AUTH_METHOD_SSH_KEY;
        $this->sshKeyPassword = '';

        $this->logFile = '';
            
        $this->emailFromAddres = '';
        $this->enableErrorEmail   = false;
        $this->enableSummaryEmail = false;
    }

    public function set($properties)
    {
        foreach (get_object_vars($this) as $var => $value) {
            if (array_key_exists($var, $properties)) {
                switch ($var) {
                    # convert true/false property values to boolean
                    case 'enableErrorEmail':
                    case 'enableSummaryEmail':
                    case 'isActive':
                        if ($properties[$var]) {
                            $properties[$var] = true;
                        } else {
                            $properties[$var] = false;
                        }
                        break;
                }
                $this->$var = $properties[$var];
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
        $errorMessage = 'Server "'.$this->name.'" has the following configuration error: '.$message
            .'. Please contact your system administrator or use another server.';
        return $errorMessage;
    }
    
    
    /**
     * Modifies an ETL configuration based on this server configuration's
     * properties.
     *
     * @param Configuration the ETL configuration to modify.
     */
    private function updateEtlConfig($etlConfig)
    {
        $etlConfig->setProperty(Configuration::LOG_FILE, $this->logFile);
        
        $etlConfig->setProperty(Configuration::EMAIL_FROM_ADDRESS, $this->emailFromAddres);
        
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
    }
    
    /**
     * Run the ETL process for this server.
     *
     * @param Configuration $etlConfig the ETL configuration to run.
     */
    public function run($etlConfig)
    {
        if (!isset($etlConfig)) {
            $message = 'No ETL configuration specified.';
            throw new \Exception($message);
        }
        
        $this->updateEtlConfig($etlConfig);
        
        if (!$this->isActive) {
            $message = 'Server "'.$this->name.'" have been inactivated.';
            throw new \Exception($message);
        }
        
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
        
        /*
        $rulesFileName = '';
        $rulesFilePath = '';

        if (!$etlConfig->usesAutoGeneratedRules()) {
            $rulesFileName = 'etl_rules_'.$fileNameSuffix.'.txt';
            $rulesFilePath = $this->configDir.'/'.$rulesFileName;
            $rulesText = $etlConfig->getTransformationRulesText();

            $scpResult = $scp->put($rulesFilePath, $rulesText);
            if (!$scpResult) {
                throw new \Exception('Copy of transformation rules file to "'.$rulesFilePath.'" failed.');
            }
        }
        */
        
        #$propertiesText = $etlConfig->getRedCapEtlPropertiesText($rulesFileName);
        $propertiesJson = $etlConfig->getRedCapEtlJsonProperties();
        #$configFileName = 'etl_config_'.$fileNameSuffix.'.ini';
        $configFileName = 'etl_config_'.$fileNameSuffix.'.json';
        $configFilePath = $this->configDir.'/'.$configFileName;
        #$scpResult = $scp->put($configFilePath, $propertiesText);
        $scpResult = $scp->put($configFilePath, $propertiesJson);
        if (!$scpResult) {
            $message = 'Copy of ETL configuration to server failed.'
                . ' Please contact your system administrator for assistance.';
            throw new \Exception($message);
        }
        
        #$ssh->setTimeout(1);
        #$command = $this->getEtlCommand() . ' ' . $configFileName . ' ' . $rulesFileName;
        $command = $this->getEtlCommand() . ' ' . $configFilePath;
        \REDCap::logEvent('REDCap-ETL run command: '.$command);

        #$ssh->setTimeout(0.1);  # to prevent blocking
        $output = $ssh->exec($command);
        \REDCap::logEvent('REDCap-ETL run output: '.$output);
        
        return $output;
    }
    
    public function test()
    {
        \REDCap::logEvent('REDCap-ETL server config test.');
        
        $testOutput = '';
        try {
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
                    .$output."\n";
            }
        } catch (\Exception $exception) {
            $testOutput = 'ERROR: '.$exception->getMessage();
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
            throw new \Exception('Server configuration name is not a string; has type: '.gettype($name).'.');
        } elseif (preg_match('/([&<>";@\*])/', $name, $matches) === 1) {
            $errorMessage = 'Invalid character in server configuration name: '.$matches[0];
            throw new \Exception($errorMessage);
        }
        return true;
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
}

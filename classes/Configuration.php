<?php

namespace IU\RedCapEtlModule;

class Configuration implements \JsonSerializable
{
    #--------------------------------------------------
    # Note: for this code to work, the properties
    # here need to match those in REDCap-ETL's
    # ConfigProperties class.
    #--------------------------------------------------
    const REDCAP_API_URL = 'redcap_api_url';
    const DATA_SOURCE_API_TOKEN = 'data_source_api_token';
    const SSL_VERIFY = 'ssl_verify';

    const TRANSFORM_RULES_FILE   = 'transform_rules_file';
    const TRANSFORM_RULES_TEXT   = 'transform_rules_text';
    const TRANSFORM_RULES_SOURCE = 'transform_rules_source';

    const CONFIG_API_TOKEN = 'config_api_token';
    const CONFIG_NAME      = 'config_name';
    const CONFIG_OWNER     = 'config_owner';
    
    const CRON_JOB         = 'cron_job';
    
    const DB_LOGGING         = 'db_logging';
    const DB_LOG_TABLE       = 'db_log_table';
    const DB_EVENT_LOG_TABLE = 'db_event_log_table';
    
    const DB_HOST = 'db_host';
    const DB_NAME = 'db_name';
    const DB_USERNAME = 'db_username';
    const DB_PASSWORD = 'db_password';

    const DB_CONNECTION = 'db_connection';
    
    const BATCH_SIZE = 'batch_size';

    const PRINT_LOGGING = 'print_logging';
    const PROJECT_ID = 'project_id';
    
    const LOG_FILE  = 'log_file';
    
    const EMAIL_ERRORS        = 'email_errors';
    const EMAIL_SUMMARY       = 'email_summary';
    const EMAIL_FROM_ADDRESS  = 'email_from_address';
    const EMAIL_SUBJECT       = 'email_subject';
    const EMAIL_TO_LIST       = 'email_to_list';

    const CRON_SERVER   = 'cron_server';
    const CRON_SCHEDULE = 'cron_schedule';
    
    private $name;
    private $username;
    private $projectId;
    private $properties;

    public function __construct($name, $username = USERID, $projectId = PROJECT_ID)
    {
        self::validateName($name);
        
        $this->name      = $name;
        $this->username  = $username;
        $this->projectId = $projectId;

        $this->properties = array();
        foreach (self::getPropertyNames() as $name) {
                $this->properties[$name] = '';
        }

        # Set non-blank defaults
        $this->properties[self::REDCAP_API_URL] = APP_PATH_WEBROOT_FULL.'api/';
        $this->properties[self::SSL_VERIFY]     = true;
        
        $this->properties[self::BATCH_SIZE] = 100;
        $this->properties[self::TRANSFORM_RULES_SOURCE] = '1';
        
        $this->properties[self::DB_LOGGING]         = true;
        $this->properties[self::DB_LOG_TABLE]       = \IU\REDCapETL\Configuration::DEFAULT_DB_LOG_TABLE;
        $this->properties[self::DB_EVENT_LOG_TABLE] = \IU\REDCapETL\Configuration::DEFAULT_DB_EVENT_LOG_TABLE;
        
        $this->properties[self::EMAIL_ERRORS]  = false;
        $this->properties[self::EMAIL_SUMMARY] = false;
    }


    /**
     * Validate the configuration.
     */
    public function validate()
    {
        self::validateName($this->name);
        
        $redcapApiUrl =  $this->getProperty(self::REDCAP_API_URL);
        self::validateRedcapApiUrl($redcapApiUrl);
        
        $apiToken =  $this->getProperty(self::DATA_SOURCE_API_TOKEN);
        self::validateApiToken($apiToken);
        
        $batchSize = $this->getProperty(self::BATCH_SIZE);
        self::validateBatchSize($batchSize);
    }
    
    
    /**
     * Validates a configuration name.
     *
     * @param string $name the configuration name to check.
     * @return boolean returns true if the configuration name is valid, or throws an
     *     exception if not.
     */
    public static function validateName($name)
    {
        $matches = array();
        if (empty($name)) {
            throw new \Exception('No configuration name specified.');
        } elseif (!is_string($name)) {
            throw new \Exception('Configuration name is not a string; has type: '.gettype($name).'.');
        } elseif (preg_match('/([&<>";@\*\n\r\t\\\\])/', $name, $matches) === 1) {
            $errorMessage = 'Invalid character in configuration name: '.$matches[0];
            throw new \Exception($errorMessage);
        }
        return true;
    }
    
    public static function validateRedcapApiUrl($url)
    {
        if (!empty($url)) {
            if (!Filter::isUrl($url)) {
                $message = 'The REDCap API URL specified "'.$url.'" is not a valid URL.';
                throw new \Exception($message);
            }
        }
        return true;
    }
    
    
    public static function validateApiToken($apiToken)
    {
        if (!empty($apiToken)) {
            if (!ctype_xdigit($apiToken)) {   # ctype_xdigit - check token for hexidecimal
                $message = 'The REDCap API token has an invalid format.'
                    .' It should only contain numbers and the letters A, B, C, D, E and F.';
                throw new \Exception($message);
            } elseif (strlen($apiToken) != 32) { # check token for correct length
                $message = 'The REDCap API token has an invalid format.'
                    .' It has a length of '.strlen($apiToken).' characters, but should have a length of'
                    .' 32.';
                throw new \Exception($message);
            } // @codeCoverageIgnore
        }
        return true;
    }
    
    public static function validateBatchSize($batchSize)
    {
        if (ctype_digit($batchSize) && intval($batchSize) > 0) {
            ; // OK
        } else {
            $message = 'The batch size "'.$batchSize.'" needs to be a positive integer.';
            throw new \Exception($message);
        }
        return true;
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

    public function set($properties)
    {
        #------------------------------------------------
        # Validate values
        #------------------------------------------------
        /*
        foreach (self::getPropertyNames() as $name) {
            if (array_key_exists($name, $properties)) {
                $value = $properties[$name];
                switch ($name) {
                    case Configuration::REDCAP_API_URL:
                        if (!empty($value) && filter_var($value, FILTER_VALIDATE_URL) === false) {
                            $message = 'Invalid REDCap API URL.';
                            throw new \Exception($message);
                        }
                        break;
                }
            }
        }
        */

        #------------------------------------------------
        # Set values
        #------------------------------------------------
        foreach (self::getPropertyNames() as $name) {
            if (array_key_exists($name, $properties)) {
                $this->properties[$name] = $properties[$name];
            } elseif ($name === self::DB_LOGGING) {
                $this->properties[$name] = false;
            } elseif ($name === self::EMAIL_ERRORS) {
                $this->properties[$name] = false;
            } elseif ($name === self::EMAIL_SUMMARY) {
                $this->properties[$name] = false;
            } elseif ($name === self::SSL_VERIFY) {
                $this->properties[$name] = false;
            }
            
            # else {
            #    $this->properties[$name] = '';
            #}
        }
        
        $dbHost = $properties[self::DB_HOST];
        $dbName = $properties[self::DB_NAME];
        
        $dbUsername = $properties[self::DB_USERNAME];
        $dbPassword = $properties[self::DB_PASSWORD];
        
        $this->properties[self::DB_CONNECTION] = 'MySQL:'.$dbHost.':'.$dbUsername.':'.$dbPassword.':'.$dbName;
    }

    public function getProperty($propertyName)
    {
        $property = $this->properties[$propertyName];
        return $property;
    }

    public function setProperty($propertyName, $value)
    {
        $this->properties[$propertyName] = $value;
    }

    public function getProperties()
    {
        return $this->properties;
    }
    
    /**
     * Returns properties array for use in running ETL.
     */
    public function getPropertiesArray()
    {
        $properties = $this->properties;
        
        #---------------------------------------
        # Remove properties that aren't used
        # by REDCap-ETL
        #---------------------------------------
        unset($properties[self::DB_HOST]);
        unset($properties[self::DB_NAME]);
        unset($properties[self::DB_USERNAME]);
        unset($properties[self::DB_PASSWORD]);
        
        unset($properties[self::CRON_SERVER]);
        unset($properties[self::CRON_SCHEDULE]);
        
        if (is_bool($properties[self::SSL_VERIFY])) {
            if ($properties[self::SSL_VERIFY]) {
                $properties[self::SSL_VERIFY] = 'true';
            } else {
                $properties[self::SSL_VERIFY] = 'false';
            }
        }
        
        return $properties;
    }

    
    /**
     * Gets configuration properties in JSON, formatted for use
     * by REDCap-ETL.
     */
    public function getRedCapEtlJsonProperties()
    {
        $properties = $this->properties;
       
        #---------------------------------------
        # Remove properties that aren't used
        # by REDCap-ETL
        #---------------------------------------
        unset($properties[self::DB_HOST]);
        unset($properties[self::DB_NAME]);
        unset($properties[self::DB_USERNAME]);
        unset($properties[self::DB_PASSWORD]);
        
        unset($properties[self::CRON_SERVER]);
        unset($properties[self::CRON_SCHEDULE]);
        
        if (is_bool($properties[self::SSL_VERIFY])) {
            if ($properties[self::SSL_VERIFY]) {
                $properties[self::SSL_VERIFY] = 'true';
            } else {
                $properties[self::SSL_VERIFY] = 'false';
            }
        }
        
        # Convert the transformation rules from text to
        # an array of strings
        if (array_key_exists(self::TRANSFORM_RULES_TEXT, $properties)) {
            $rulesText = $properties[self::TRANSFORM_RULES_TEXT];
            $rules = preg_split("/\r\n|\n|\r/", $rulesText);
            $properties[self::TRANSFORM_RULES_TEXT] = $rules;
        }

        $jsonProperties = json_encode($properties, JSON_PRETTY_PRINT);
        
        return $jsonProperties;
    }
    
    
#    /**
#     * Gets a REDCap-ETL compatible text version (for inclusion in a file) or
#     * the configuration properties.
#     */
#    public function getRedCapEtlPropertiesText($transformationRulesFile = null)
#    {
#        $text = '';
#        $properties = $this->getRedCapEtlProperties();
#
#        if (!empty($transformationRulesFile)) {
#            $properties[self::TRANSFORM_RULES_SOURCE] = 2;
#            $properties[self::TRANSFORM_RULES_FILE]   = $transformationRulesFile;
#        }
#
#        foreach ($properties as $property => $value) {
#            $text .= "${property}=${value}\n";
#        }
#
#        return $text;
#    }
    
    public function usesAutoGeneratedRules()
    {
        return $this->properties[self::TRANSFORM_RULES_SOURCE] == 3;
    }
    
    public function getTransformationRulesText()
    {
        return $this->properties[self::TRANSFORM_RULES_TEXT];
    }

    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        self::validateName($name);
        $this->name = $name;
    }
    
    public function getProjectId()
    {
        return $this->projectId;
    }
    
    public function getUsername()
    {
        return $this->username;
    }

    public function getApiUrl()
    {
        return $this->properties[self::REDCAP_API_URL];
    }

    public function setApiUrl($value)
    {
        $this->properties[self::REDCAP_API_URL] = $value;
    }


    public static function getPropertyNames()
    {
        $reflection = new \ReflectionClass(self::class);
        $properyNames = $reflection->getConstants();
        return $properyNames;
    }
}

/*
$names = ['', 'test', 123, '<script>&27;</script>', '&34;', 'test-1 234', 'a"test', 'test\'s 1'];

foreach ($names as $name) {

    try {
        if (Configuration::validateName($name)) {
            print 'Name "'.$name.'" is '."valid\n";
        }
    } catch (\Exception $exception) {
        print "Error: ".$exception->getMessage()."\n";
    }
}
*/

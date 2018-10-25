<?php

namespace IU\RedCapEtlModule;

class Configuration implements \JsonSerializable
{
    const REDCAP_API_URL = 'redcap_api_url';
    const DATA_SOURCE_API_TOKEN = 'data_source_api_token';
    const SSL_VERIFY = 'ssl_verify';

    const TRANSFORM_RULES_FILE   = 'transform_rules_file';
    const TRANSFORM_RULES_TEXT   = 'transform_rules_text';
    const TRANSFORM_RULES_SOURCE = 'transform_rules_source';

    const CONFIG_API_TOKEN = 'config_api_token';

    const DB_HOST = 'db_host';
    const DB_NAME = 'db_name';
    const DB_USERNAME = 'db_username';
    const DB_PASSWORD = 'db_password';

    const DB_CONNECTION = 'db_connection';
    
    const BATCH_SIZE = 'batch_size';

    const EMAIL_FROM_ADDRESS  = 'email_from_address';
    const EMAIL_SUBJECT       = 'email_subject';
    const EMAIL_TO_LIST       = 'email_to_list';

    const CRON_SERVER   = 'cron_server';
    const CRON_SCHEDULE = 'cron_schedule';
    
    private $name;
    private $properties;

    public function __construct($name)
    {
        $this->name = $name;

        $this->properties = array();
        foreach (self::getPropertyNames() as $name) {
                $this->properties[$name] = '';
        }

        # Set non-blank defaults
        $this->properties[self::REDCAP_API_URL]    = APP_PATH_WEBROOT_FULL.'api/';
        $this->properties[self::SSL_VERIFY]        = true;
        $this->properties[self::BATCH_SIZE] = 100;
        $this->properties[self::TRANSFORM_RULES_SOURCE] = '1';

        if (!empty(PROJECT_ID)) {
            $redCapDb = new RedCapDb();
            $apiToken = $redCapDb->getApiToken(USERID, PROJECT_ID);
            if (!empty(api_token)) {
                $this->properties[self::DATA_SOURCE_API_TOKEN] = $apiToken;
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

    public function set($properties)
    {
        #------------------------------------------------
        # Validate values
        #------------------------------------------------
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

        #------------------------------------------------
        # Set values
        #------------------------------------------------
        foreach (self::getPropertyNames() as $name) {
            if (array_key_exists($name, $properties)) {
                $this->properties[$name] = $properties[$name];
            } else {
                $this->properties[$name] = '';
            }
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
    
    public function getRedCapEtlProperties()
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
                
        unset($properties[self::TRANSFORM_RULES_TEXT]);
        
        unset($properties[self::CRON_SERVER]);
        unset($properties[self::CRON_SCHEDULE]);
                     
        return $properties;
    }
    
    /**
     * Gets a REDCap-ETL compatible text version (for inclusion in a file) or
     * the configuration properties.
     */
    public function getRedCapEtlPropertiesText($transformationRulesFile = null)
    {
        $text = '';
        $properties = $this->getRedCapEtlProperties();
        
        if (!empty($transformationRulesFile)) {
            $properties[self::TRANSFORM_RULES_SOURCE] = 2;
            $properties[self::TRANSFORM_RULES_FILE]   = $transformationRulesFile;
        }
        
        foreach ($properties as $property => $value) {
            $text .= "${property}=${value}\n";
        }

        return $text;
    }
    
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
        $this->name = $name;
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

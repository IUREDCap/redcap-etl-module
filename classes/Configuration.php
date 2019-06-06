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

    const DATA_EXPORT_RIGHT = 'data_export_right';
    
    const API_TOKEN_USERNAME = 'api_token_username';
    
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
    
    const DB_SSL        = 'db_ssl';
    const DB_SSL_VERIFY = 'db_ssl_verify';
    const CA_CERT_FILE  = 'ca_cert_file';
    
    const BATCH_SIZE = 'batch_size';

    const TABLE_PREFIX = 'table_prefix';
    
    const LABEL_VIEW_SUFFIX = 'label_view_suffix';
    
    const POST_PROCESSING_SQL = 'post_processing_sql';
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
         
        $this->properties[self::API_TOKEN_USERNAME] = '';

        $this->properties[self::DATA_EXPORT_RIGHT] = 0;
        
        $this->properties[self::DATA_SOURCE_API_TOKEN] = '';
        
        $this->properties[self::BATCH_SIZE] = 100;
        $this->properties[self::TRANSFORM_RULES_SOURCE] = '1';
        
        $this->properties[self::TABLE_PREFIX] = '';
        
        $this->properties[self::LABEL_VIEW_SUFFIX] = \IU\REDCapETL\Configuration::DEFAULT_LABEL_VIEW_SUFFIX;
                
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
        
        $tablePrefix = $this->getProperty(self::TABLE_PREFIX);
        self::validateTablePrefix($tablePrefix);
        
        $labelViewSuffix = $this->getProperty(self::LABEL_VIEW_SUFFIX);
        self::validateLabelViewSuffix($labelViewSuffix);
                
        $emailToList = $this->getProperty(self::EMAIL_TO_LIST);
        self::validateEmailToList($emailToList);
    }
    
    
    /**
     * Validates a configuration name.
     *
     * @param string $name the configuration name to check.
     *
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
        } elseif (preg_match('/([^a-zA-Z0-9_\- .])/', $name, $matches) === 1) {
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
 
     
    public static function validateTablePrefix($tablePrefix)
    {
        $matches = array();
        if (empty($tablePrefix)) {
            ; // OK, it's optional
        } elseif (preg_match('/^[_$a-zA-Z][_$a-zA-Z0-9]*$/', $tablePrefix, $matches) === 1) {
            ; // OK
        } else {
             $errorMessage = 'Invalid table name prefix "'.$tablePrefix.'". Table name prefixes need to start with'
                 .' a letter (a-z or A-Z), underscore, or dollar sign, that is followed by zero or more'
                 .' of the same characters and/or digits (0-9)';
            throw new \Exception($errorMessage);
        }
        return true;
    }

     
    public static function validateLabelViewSuffix($suffix)
    {
        $matches = array();
        if (empty($suffix)) {
            ; // OK, it's optional; will default to '_label_view'
        } elseif (preg_match('/^[_$a-zA-Z][_a-zA-Z0-9]*$/', $suffix, $matches) === 1) {
            ; // OK
        } else {
             $errorMessage = 'Invalid label view suffix "'.$suffix.'". Label view suffixes need to contain'
                 .' only letters (a-z or A-Z), underscores, or digits (0-9)';
            throw new \Exception($errorMessage);
        }
        return true;
    }
           
    public static function validateEmailToList($emailToList)
    {
        if (isset($emailToList)) {
            $emailToList = trim($emailToList);
            
            if (!empty($emailToList)) {
                $emails = preg_split('/[\s]*[\s,][\s]*/', $emailToList);
                $invalidEmails = array();
        
                foreach ($emails as $email) {
                    if (!Filter::isEmail($email)) {
                        array_push($invalidEmails, $email);
                    }
                }
        
                if (count($invalidEmails) === 1) {
                    throw new \Exception('The following to e-mail is invalid: '.$invalidEmails[0]);
                } elseif (count($invalidEmails) > 1) {
                    $message = 'The following to e-mails are invalid: '.$invalidEmails[0];
                    for ($i = 1; $i < count($invalidEmails); $i++) {
                        $message .= ', '.$invalidEmails[$i];
                    }
                    throw new \Exception($message);
                }
            }
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
        $flags = [self::DB_LOGGING, self::EMAIL_ERRORS, self::EMAIL_SUMMARY, self::SSL_VERIFY];
        
        #------------------------------------------------
        # Set values
        #------------------------------------------------
        foreach (self::getPropertyNames() as $name) {
            if (array_key_exists($name, $properties)) {
                if (in_array($name, $flags)) {
                    $value = $properties[$name];
                    if ($value === true || $value === 'true' || $value === 'on') {
                        $this->properties[$name] = true;
                    } else {
                        $this->properties[$name] = false;
                    }
                } else {
                    $this->properties[$name] = $properties[$name];
                }
            } else {
                if (in_array($name, $flags)) {
                    $this->properties[$name] = false;
                }
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
        
        unset($properties[self::API_TOKEN_USERNAME]);
                
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

        unset($properties[self::API_TOKEN_USERNAME]);

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
        
        # Convert the post-processing SQL from text to
        # an array of strings
        if (array_key_exists(self::POST_PROCESSING_SQL, $properties)) {
            $sqlText = $properties[self::POST_PROCESSING_SQL];
            if (!isset($sqlText) || empty(trim($sqlText))) {
                unset($properties[self::POST_PROCESSING_SQL]);
            } else {
                $sql = preg_split("/\r\n|\n|\r/", $sqlText);
                $properties[self::POST_PROCESSING_SQL] = $sql;
            }
        }

        $jsonProperties = json_encode($properties, JSON_PRETTY_PRINT);
        
        return $jsonProperties;
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
    
    public function getDataExportRight()
    {
        return $this->properties[self::DATA_EXPORT_RIGHT];
    }
    
    public function setDataExportRight($dataExportRight)
    {
        $this->properties[self::DATA_EXPORT_RIGHT] = $dataExportRight;
    }
    
    public static function getPropertyNames()
    {
        $reflection = new \ReflectionClass(self::class);
        $properyNames = $reflection->getConstants();
        return $properyNames;
    }
}

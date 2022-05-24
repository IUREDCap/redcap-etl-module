<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

class Configuration implements \JsonSerializable
{
    #--------------------------------------------------
    # Note: for this code to work, the properties
    # here need to match those in REDCap-ETL's
    # ConfigProperties class.
    #--------------------------------------------------
    public const REDCAP_API_URL = 'redcap_api_url';
    public const DATA_SOURCE_API_TOKEN = 'data_source_api_token';
    public const SSL_VERIFY = 'ssl_verify';

    public const DATA_EXPORT_RIGHT = 'data_export_right';

    public const API_TOKEN_USERNAME = 'api_token_username';

    public const EXTRACT_FILTER_LOGIC = 'extract_filter_logic';

    public const TRANSFORM_RULES_FILE   = 'transform_rules_file';
    public const TRANSFORM_RULES_TEXT   = 'transform_rules_text';
    public const TRANSFORM_RULES_SOURCE = 'transform_rules_source';

    public const CONFIG_API_TOKEN = 'config_api_token';
    public const CONFIG_NAME      = 'config_name';
    public const CONFIG_OWNER     = 'config_owner';

    public const CRON_JOB         = 'cron_job';

    public const DB_LOGGING         = 'db_logging';
    public const DB_LOG_TABLE       = 'db_log_table';
    public const DB_EVENT_LOG_TABLE = 'db_event_log_table';

    # External module specific property names that are not in REDCap-ETL.
    # The values for these properties are combined to form REDCap-ETL's
    # DB_CONNECTION property.
    public const DB_TYPE = 'db_type';
    public const DB_HOST = 'db_host';
    public const DB_PORT = 'db_port';
    public const DB_NAME   = 'db_name';
    public const DB_SCHEMA = 'db_schema';
    public const DB_USERNAME = 'db_username';
    public const DB_PASSWORD = 'db_password';

    public const DB_CONNECTION = 'db_connection';

    public const DB_SSL        = 'db_ssl';
    public const DB_SSL_VERIFY = 'db_ssl_verify';

    public const DB_PRIMARY_KEYS = 'db_primary_keys';
    public const DB_FOREIGN_KEYS = 'db_foreign_keys';

    public const CA_CERT_FILE  = 'ca_cert_file';

    public const IGNORE_EMPTY_INCOMPLETE_FORMS = 'ignore_empty_incomplete_forms';

    public const BATCH_SIZE = 'batch_size';

    public const TABLE_PREFIX = 'table_prefix';

    public const LABEL_VIEW_SUFFIX = 'label_view_suffix';

    public const POST_PROCESSING_SQL = 'post_processing_sql';
    public const PRE_PROCESSING_SQL  = 'pre_processing_sql';
    public const PRINT_LOGGING = 'print_logging';
    public const PROJECT_ID = 'project_id';



    public const LOG_FILE  = 'log_file';

    public const EMAIL_ERRORS        = 'email_errors';
    public const EMAIL_SUMMARY       = 'email_summary';
    public const EMAIL_FROM_ADDRESS  = 'email_from_address';
    public const EMAIL_SUBJECT       = 'email_subject';
    public const EMAIL_TO_LIST       = 'email_to_list';

    public const CRON_SERVER   = 'cron_server';
    public const CRON_SCHEDULE = 'cron_schedule';

    public const AUTOGEN_INCLUDE_COMPLETE_FIELDS = 'autogen_include_complete_fields';
    public const AUTOGEN_INCLUDE_DAG_FIELDS = 'autogen_include_dag_fields';
    public const AUTOGEN_INCLUDE_FILE_FIELDS = 'autogen_include_file_fields';
    public const AUTOGEN_INCLUDE_SURVEY_FIELDS = 'autogen_include_survey_fields';
    public const AUTOGEN_REMOVE_NOTES_FIELDS = 'autogen_remove_notes_fields';
    public const AUTOGEN_REMOVE_IDENTIFIER_FIELDS = 'autogen_remove_identifier_fields';
    public const AUTOGEN_COMBINE_NON_REPEATING_FIELDS = 'autogen_combine_non_repeating_fields';
    public const AUTOGEN_NON_REPEATING_FIELDS_TABLE = 'autogen_non_repeating_fields_table';

    public const DATA_TARGET = 'data_target';

    private $name;
    private $username;
    private $projectId;

    private $properties; // map from property names to property values
    private $booleanUserProperties;  // array of boolean property names that are
                                    // set in the external module user interface

    public function __construct($name, $username = USERID, $projectId = null)
    {
        if (!isset($projectId)) {
            $projectId = RedCapEtlModule::getProjectIdConstant();
        }

        $this->booleanUserProperties = [
            self::DB_LOGGING,
            self::DB_PRIMARY_KEYS,
            self::DB_FOREIGN_KEYS,
            self::EMAIL_ERRORS,
            self::EMAIL_SUMMARY,
            self::IGNORE_EMPTY_INCOMPLETE_FORMS,
            self::SSL_VERIFY,
            self::AUTOGEN_INCLUDE_COMPLETE_FIELDS,
            self::AUTOGEN_INCLUDE_DAG_FIELDS,
            self::AUTOGEN_INCLUDE_FILE_FIELDS,
            self::AUTOGEN_INCLUDE_SURVEY_FIELDS,
            self::AUTOGEN_REMOVE_NOTES_FIELDS,
            self::AUTOGEN_REMOVE_IDENTIFIER_FIELDS,
            self::AUTOGEN_COMBINE_NON_REPEATING_FIELDS
        ];

        self::validateName($name);

        $this->name      = $name;
        $this->username  = $username;
        $this->projectId = $projectId;

        $this->properties = array();
        foreach (self::getPropertyNames() as $name) {
                $this->properties[$name] = '';
        }

        #------------------------------------------------
        # Set non-blank defaults
        #------------------------------------------------
        $this->properties[self::REDCAP_API_URL] = RedCapEtlModule::getRedCapApiUrl();
        $this->properties[self::SSL_VERIFY]     = true;

        $this->properties[self::API_TOKEN_USERNAME] = '';

        $this->properties[self::DATA_EXPORT_RIGHT] = 0;

        $this->properties[self::DATA_SOURCE_API_TOKEN] = '';

        $this->properties[self::BATCH_SIZE] = 100;
        $this->properties[self::TRANSFORM_RULES_SOURCE] = '1';

        $this->properties[self::TABLE_PREFIX] = '';

        $this->properties[self::IGNORE_EMPTY_INCOMPLETE_FORMS] = false;

        $this->properties[self::LABEL_VIEW_SUFFIX] = \IU\REDCapETL\TaskConfig::DEFAULT_LABEL_VIEW_SUFFIX;

        $this->properties[self::DB_LOGGING]         = true;
        $this->properties[self::DB_LOG_TABLE]       = \IU\REDCapETL\TaskConfig::DEFAULT_DB_LOG_TABLE;
        $this->properties[self::DB_EVENT_LOG_TABLE] = \IU\REDCapETL\TaskConfig::DEFAULT_DB_EVENT_LOG_TABLE;

        $this->properties[self::EMAIL_ERRORS]  = false;
        $this->properties[self::EMAIL_SUMMARY] = false;

        $this->properties[self::DB_TYPE] = \IU\REDCapETL\Database\DbConnectionFactory::DBTYPE_MYSQL;

        $this->properties[self::DB_PRIMARY_KEYS] = true;
        $this->properties[self::DB_FOREIGN_KEYS] = true;

        $this->properties[self::AUTOGEN_INCLUDE_COMPLETE_FIELDS] = false;
        $this->properties[self::AUTOGEN_INCLUDE_DAG_FIELDS] = false;
        $this->properties[self::AUTOGEN_INCLUDE_FILE_FIELDS] = false;
        $this->properties[self::AUTOGEN_INCLUDE_SURVEY_FIELDS] = false;
        $this->properties[self::AUTOGEN_REMOVE_NOTES_FIELDS] = false;
        $this->properties[self::AUTOGEN_REMOVE_IDENTIFIER_FIELDS] = false;
        $this->properties[self::AUTOGEN_COMBINE_NON_REPEATING_FIELDS] = false;
        $this->properties[self::AUTOGEN_NON_REPEATING_FIELDS_TABLE] = '';
    }

    /**
     * Validation for case where a user is trying to run or schedule a configuration.
     * Users may save incomplete configurations, but they should be prevented from
     * trying to run or schedule one.
     *
     * @param boolean checkDatabaseConnection indicates if the database connection should be checked (if the user
     *     is downloading the results as a CSV zip file, this check is unnecessary).
     */
    public function validateForRunning($serverName, $checkDatabaseConnection = true)
    {
        $isWorkflowGlobalProperties = false;
        $this->validate($isWorkflowGlobalProperties);

        if (empty($this->getProperty(self::API_TOKEN_USERNAME))) {
            throw new \Exception('No API token specified in configuration.');
        }

        $rulesSource = $this->getProperty(self::TRANSFORM_RULES_SOURCE);
        if ($rulesSource != \IU\REDCapETL\TaskConfig::TRANSFORM_RULES_DEFAULT) {
            # If the rules source is not (dynamic) auto-generation, make sure that rules have beem specified
            if (empty($this->getProperty(self::TRANSFORM_RULES_TEXT))) {
                throw new \Exception('No transformation rules were specified in configuration.');
            }
        }

        if ($checkDatabaseConnection) {
            if (empty($this->getProperty(self::DB_TYPE))) {
                throw new \Exception('No database type was specified in configuration.');
            } else {
                $dbType = $this->getProperty(self::DB_TYPE);
                if ($dbType === \IU\REDCapETL\Database\DbConnectionFactory::DBTYPE_MYSQL) {
                    ; // OK
                } elseif ($dbType === \IU\REDCapETL\Database\DbConnectionFactory::DBTYPE_SQLSERVER) {
                    # If running on embedded server, check that the correct extensions exist
                    if ($serverName === ServerConfig::EMBEDDED_SERVER_NAME) {
                        if (!extension_loaded('sqlsrv') || !extension_loaded('pdo_sqlsrv')) {
                            $message = 'The extensions for running SQL Server (sqlsrv and/or pdo_sqlsrv)'
                                . ' have not been enabled.';
                            throw new \Exception($message);
                        }
                    }
                } elseif ($dbType === \IU\REDCapETL\Database\DbConnectionFactory::DBTYPE_POSTGRESQL) {
                    # If running on embedded server, check that the correct extensions exist
                    if ($serverName === ServerConfig::EMBEDDED_SERVER_NAME) {
                        if (!extension_loaded('pgsql') || !extension_loaded('pdo_pgsql')) {
                            $message = 'The extensions for running PostgreSQL (pgsql and/or pdo_pgsql)'
                                . ' have not been enabled.';
                            throw new \Exception($message);
                        }
                    }
                } else {
                    throw new \Exception('Unrecognized database type "' . $dbType . '" specified.');
                }
            }

            if (empty($this->getProperty(self::DB_HOST))) {
                throw new \Exception('No database host was specified in configuration.');
            }

            if (empty($this->getProperty(self::DB_NAME))) {
                throw new \Exception('No database name was specified in configuration.');
            }

            if (empty($this->getProperty(self::DB_USERNAME))) {
                throw new \Exception('No database username was specified in configuration.');
            }

            if (empty($this->getProperty(self::DB_PASSWORD))) {
                throw new \Exception('No database password was specified in configuration.');
            }
        }

        if ($this->getProperty(self::DB_FOREIGN_KEYS) && !$this->getProperty(self::DB_PRIMARY_KEYS)) {
            throw new \Exception(
                'Database foreign keys specified without database primary keys being specified.'
            );
        }

        if ($this->getProperty(self::EMAIL_ERRORS) || $this->getProperty(self::EMAIL_SUMMARY)) {
            if (empty($this->getProperty(self::EMAIL_TO_LIST))) {
                throw new \Exception(
                    'E-mailing of errors and/or summary specified in configuration,'
                    . ' but no e-mail to list address was provided.'
                );
            }
        }
    }

    /**
     * Validate the configuration.
     */
    public function validate($isWorkflowGlobalProperties = false)
    {
        self::validateName($this->name);

        $redcapApiUrl =  $this->getProperty(self::REDCAP_API_URL);
        self::validateRedcapApiUrl($redcapApiUrl);

        $apiToken =  $this->getProperty(self::DATA_SOURCE_API_TOKEN);
        self::validateApiToken($apiToken);

        $batchSize = $this->getProperty(self::BATCH_SIZE);
        if (!$isWorkflowGlobalProperties || ($isWorkflowGlobalProperties && !empty($batchSize))) {
            self::validateBatchSize($batchSize);
        }

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
            throw new \Exception('Configuration name is not a string; has type: ' . gettype($name) . '.');
        } elseif (preg_match('/([^a-zA-Z0-9_\- .])/', $name, $matches) === 1) {
            $errorMessage = 'Invalid character in configuration name: ' . $matches[0];
            throw new \Exception($errorMessage);
        }
        return true;
    }

    public static function validateRedcapApiUrl($url)
    {
        if (!empty($url)) {
            if (!Filter::isUrl($url)) {
                $message = 'The REDCap API URL specified "' . $url . '" is not a valid URL.';
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
                    . ' It should only contain numbers and the letters A, B, C, D, E and F.';
                throw new \Exception($message);
            } elseif (strlen($apiToken) != 32) { # check token for correct length
                $message = 'The REDCap API token has an invalid format.'
                    . ' It has a length of ' . strlen($apiToken)
                    . ' characters, but should have a length of' . ' 32.';
                throw new \Exception($message);
            } // @codeCoverageIgnore
        }
        return true;
    }

    public static function validateBatchSize($batchSize)
    {
        if (preg_match('/^[1-9][0-9]*$/', $batchSize) === 1) {
            ; // OK
        } else {
            $message = 'The batch size "' . $batchSize . '" needs to be a positive integer.';
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
            $errorMessage = 'Invalid table name prefix "' . $tablePrefix . '". '
                . 'Table name prefixes need to start with'
                . ' a letter (a-z or A-Z), underscore, or dollar sign, that is followed by zero or more'
                . ' of the same characters and/or digits (0-9)';
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
             $errorMessage = 'Invalid label view suffix "' . $suffix . '". Label view suffixes need to contain'
                 . ' only letters (a-z or A-Z), underscores, or digits (0-9)';
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
                    throw new \Exception('The following to e-mail is invalid: ' . $invalidEmails[0]);
                } elseif (count($invalidEmails) > 1) {
                    $message = 'The following to e-mails are invalid: ' . $invalidEmails[0];
                    for ($i = 1; $i < count($invalidEmails); $i++) {
                        $message .= ', ' . $invalidEmails[$i];
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

    public function set($properties, $isWorkflow = false)
    {
        #------------------------------------------------
        # Set values
        #------------------------------------------------
        foreach (self::getPropertyNames() as $name) {
            if (array_key_exists($name, $properties)) {
                if (in_array($name, $this->booleanUserProperties)) {
                    #------------------------------------------
                    # If this is a flag (boolean) property
                    #------------------------------------------
                    $value = $properties[$name];
                    if ($value === true || $value === 'true' || $value === 'on') {
                        $this->properties[$name] = true;
                    } else {
                        if ($isWorkflow) {
                            if ($value === false || $value === 'false' || $value === 'off') {
                                $this->properties[$name] = false;
                            } else {
                                $this->properties[$name] = null;
                            }
                        } else {
                            $this->properties[$name] = false;
                        }
                    }
                } else {
                    # If this is a non-boolean property
                    @$testMode = file_exists(__DIR__ . '/../test-config.ini');
                    if ($name === Configuration::REDCAP_API_URL && (!isset($testMode) || !$testMode)) {
                        #---------------------------------------------------------------
                        # If this is the REDCap API URL property, set it to the API URL
                        # for the current server, unless test mode is being used, so
                        # that this module can be migrated or cloned to a new server
                        #--------------------------------------------------------------
                        $this->properties[$name] = RedCapEtlModule::getRedCapApiUrl();
                    } elseif (is_array($properties[$name])) {
                        $this->properties[$name] = Filter::stripTagsArray($properties[$name]);
                    } else {
                        $this->properties[$name] = Filter::stripTags($properties[$name]);
                    }
                }
            } else { // if (!$isWorkflow) {
                if (in_array($name, $this->booleanUserProperties)) {
                    $this->properties[$name] = false;
                }
            }
        }

        $dbType = null;
        if (array_key_exists(self::DB_TYPE, $properties)) {
            $dbType = $properties[self::DB_TYPE];
        }

        if (empty($dbType) && !$isWorkflow) {
            # Originally, this property didn't exists, because the only database
            # type was MySQL, so older data will not have this, and it needs
            # to be set to the default value (MySQL).
            # (Don't assign the default value to the workflow global parameter
            # because it will override the db type for the task.)
            $dbType = \IU\REDCapETL\Database\DbConnectionFactory::DBTYPE_MYSQL;
        }

        if (!array_key_exists(self::DB_SCHEMA, $properties)) {
            $properties[self::DB_SCHEMA] = '';
        }

        $dbHost   = $properties[self::DB_HOST];
        $dbPort   = $properties[self::DB_PORT];
        $dbName   = $properties[self::DB_NAME];
        $dbSchema = $properties[self::DB_SCHEMA];

        $dbUsername = $properties[self::DB_USERNAME];
        $dbPassword = $properties[self::DB_PASSWORD];

        #--------------------------------------------------------------------------------------------------
        # Set the database connection string
        # Note: the MySQL server port number, which defaults to 3306, can be included in dbHost value also
        #--------------------------------------------------------------------------------------------------
        $dbConnectionValues = [$dbType, $dbHost, $dbUsername, $dbPassword, $dbName];

        if ($dbType == \IU\REDCapETL\Database\DbConnectionFactory::DBTYPE_POSTGRESQL && !empty($dbSchema)) {
            array_push($dbConnectionValues, $dbSchema);
        }

        if (!empty($dbPort)) {
            array_push($dbConnectionValues, $dbPort);
        }

        if (!$isWorkflow || ($isWorkflow && $dbType)) {
            $dbConnection = \IU\REDCapETL\Database\DbConnection::createConnectionString($dbConnectionValues);
            $this->properties[self::DB_CONNECTION] = $dbConnection;
        }
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

    public function setProperties($properties)
    {
        $this->properties = $properties;
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
        unset($properties[self::DB_TYPE]);
        unset($properties[self::DB_HOST]);
        unset($properties[self::DB_PORT]);
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
    public function getRedCapEtlJsonProperties($runWorkflow = false, $workflowProperties = null)
    {
        if ($runWorkflow) {
            if ($workflowProperties) {
                $properties = $workflowProperties;
            } else {
                $msg = 'When running workflow on remote server, ';
                $msg .= 'no workflow properties were specified when retrieving json properties.';
                throw new \Exception($msg);
            }
        } else {
            $properties = $this->properties;
        }

        #---------------------------------------
        # Remove properties that aren't used
        # by REDCap-ETL
        #---------------------------------------
        unset($properties[self::DB_TYPE]);
        unset($properties[self::DB_HOST]);
        unset($properties[self::DB_PORT]);
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

        if (is_bool($properties[self::IGNORE_EMPTY_INCOMPLETE_FORMS])) {
            if ($properties[self::IGNORE_EMPTY_INCOMPLETE_FORMS]) {
                $properties[self::IGNORE_EMPTY_INCOMPLETE_FORMS] = 'true';
            } else {
                $properties[self::IGNORE_EMPTY_INCOMPLETE_FORMS] = 'false';
            }
        }

        # Convert the transformation rules from text to
        # an array of strings
        if (array_key_exists(self::TRANSFORM_RULES_TEXT, $properties)) {
            $rulesText = $properties[self::TRANSFORM_RULES_TEXT];
            $rules = preg_split("/\r\n|\n|\r/", $rulesText);
            $properties[self::TRANSFORM_RULES_TEXT] = $rules;
        }

        # Convert the pre-processing SQL from text to
        # an array of strings
        if (array_key_exists(self::PRE_PROCESSING_SQL, $properties)) {
            $sqlText = $properties[self::PRE_PROCESSING_SQL];
            if (!isset($sqlText) || empty(trim($sqlText))) {
                unset($properties[self::PRE_PROCESSING_SQL]);
            } else {
                $sql = preg_split("/\r\n|\n|\r/", $sqlText);
                $properties[self::PRE_PROCESSING_SQL] = $sql;
            }
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

    public function getGlobalProperties($initialize = false)
    {
        $properties = $this->properties;

        #---------------------------------------
        # Remove properties that aren't used
        # in Global Properties
        #---------------------------------------
        unset($properties[self::REDCAP_API_URL]);
        unset($properties[self::SSL_VERIFY]);
        unset($properties[self::DATA_EXPORT_RIGHT]);
        unset($properties[self::DATA_SOURCE_API_TOKEN]);
        unset($properties[self::API_TOKEN_USERNAME]);
        unset($properties[self::TRANSFORM_RULES_FILE]);
        unset($properties[self::TRANSFORM_RULES_TEXT]);
        unset($properties[self::TRANSFORM_RULES_SOURCE]);
        unset($properties[self::CONFIG_NAME]);
        unset($properties[self::CONFIG_API_TOKEN]);
        unset($properties[self::CONFIG_OWNER]);
        unset($properties[self::AUTOGEN_INCLUDE_COMPLETE_FIELDS]);
        unset($properties[self::AUTOGEN_INCLUDE_DAG_FIELDS]);
        unset($properties[self::AUTOGEN_INCLUDE_FILE_FIELDS]);
        unset($properties[self::AUTOGEN_INCLUDE_SURVEY_FIELDS]);
        unset($properties[self::AUTOGEN_REMOVE_IDENTIFIER_FIELDS]);
        unset($properties[self::AUTOGEN_REMOVE_NOTES_FIELDS]);
        unset($properties[self::AUTOGEN_COMBINE_NON_REPEATING_FIELDS]);
        unset($properties[self::AUTOGEN_NON_REPEATING_FIELDS_TABLE]);
        unset($properties[self::PROJECT_ID]);
        unset($properties[self::EMAIL_FROM_ADDRESS]);
        unset($properties[self::EXTRACT_FILTER_LOGIC]);
        unset($properties[self::LOG_FILE]);
        unset($properties[self::CA_CERT_FILE]);
        unset($properties[self::DB_SSL]);
        unset($properties[self::DB_SSL_VERIFY]);
        unset($properties[self::PRINT_LOGGING]);


        if ($initialize) {
            #---------------------------------------
            # Remove all default values
            #---------------------------------------
            $properties[self::CRON_JOB] = null;

            $properties[self::DB_TYPE]       = null;
            $properties[self::DB_HOST]       = null;
            $properties[self::DB_PORT]       = null;
            $properties[self::DB_NAME]       = null;
            $properties[self::DB_SCHEMA]     = null;
            $properties[self::DB_USERNAME]   = null;
            $properties[self::DB_PASSWORD]   = null;
            $properties[self::DB_CONNECTION] = null;

            $properties[self::BATCH_SIZE]                    = null;
            $properties[self::IGNORE_EMPTY_INCOMPLETE_FORMS] = null;
            $properties[self::TABLE_PREFIX]                  = null;
            $properties[self::LABEL_VIEW_SUFFIX]             = null;

            $properties[self::DB_PRIMARY_KEYS]    = null;
            $properties[self::DB_FOREIGN_KEYS]    = null;
            $properties[self::DB_LOGGING]         = null;
            $properties[self::DB_LOG_TABLE]       = null;
            $properties[self::DB_EVENT_LOG_TABLE] = null;

            $properties[self::EMAIL_ERRORS]  = null;
            $properties[self::EMAIL_SUMMARY] = null;
            $properties[self::EMAIL_SUBJECT] = null;
            $properties[self::EMAIL_TO_LIST] = null;

            $properties[self::POST_PROCESSING_SQL]  = null;
            $properties[self::PRE_PROCESSING_SQL]   = null;
            $properties[self::CRON_SERVER]          = null;
            $properties[self::CRON_SCHEDULE]        = null;
        }

        return $properties;
    }
}

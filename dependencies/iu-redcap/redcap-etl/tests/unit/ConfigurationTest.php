<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the Logger class.
 */
class ConfigurationTest extends TestCase
{
    private $properties;
    
    public function setUp()
    {
        $this->properties = [
            ConfigProperties::REDCAP_API_URL => 'http://localhost/redcap/api',
            ConfigProperties::DATA_SOURCE_API_TOKEN => '11111111112222222222333333333344',
            ConfigProperties::TRANSFORM_RULES_SOURCE => '3',
            ConfigProperties::DB_CONNECTION => 'CSV:./'
        ];
    }

    public function testConstructor()
    {

        $propertiesTemplate = array('transform_rules_source' => '3');

        // Bad .json file
        $logger = new Logger('test-app');

        $propertiesFile = 'fake.json';
        SystemFunctions::setOverrideFileGetContents(true);
        SystemFunctions::setFileGetContentsResult(false);

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'The JSON configuration file "'.$propertiesFile.
            '" could not be read.';
        try {
            $config = new Configuration($logger, $propertiesFile);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }
        SystemFunctions::setOverrideFileGetContents(false);

        $this->assertTrue(
            $exceptionCaught,
            'Constructor Bad .json exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'Constructor Bad .json exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'Constructor Bad .json exception message check'
        );


        // No API URL
        $properties = $propertiesTemplate;

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'No "redcap_api_url" property was defined.';
        try {
            $config = new Configuration($logger, $properties);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'Constructor No redcap_api_url property exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'Constructor No redcap_api_url property exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'Constructor No redcap_api_url property exception message check'
        );

        $propertiesTemplate['redcap_api_url'] = 'https://foo.edu';


        // ssl_verify set to unrecognized value
        $badSSLVerify = 'foo';
        $properties = $propertiesTemplate;
        $properties['ssl_verify'] = $badSSLVerify;

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'Unrecognized value "'.$badSSLVerify.
            '" for ssl_verify property; a true or false value should be specified.';
        try {
            $config = new Configuration($logger, $properties);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'Constructor Bad ssl_verify property exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'Constructor Bad ssl_verify property exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'Constructor Bad ssl_verify property exception message check'
        );

        // extracted_record_count_check unrecognized value
        $badExtractedRecordCountCheck = 'foo';
        $properties = $propertiesTemplate;
        $properties['extracted_record_count_check'] =
            $badExtractedRecordCountCheck;

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'Unrecognized value "'.$badExtractedRecordCountCheck.
            '" for extracted_record_count_check property; a true or false value should be specified.';
        try {
            $config = new Configuration($logger, $properties);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'Constructor Bad extracted_records_count_check property exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'Constructor Bad extracted_records_count_check property exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'Constructor Bad extracted_records_count_check property exception message check'
        );

        // No data API token property
        $properties = $propertiesTemplate;

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'No data source API token was found.';
        try {
            $config = new Configuration($logger, $properties);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'Constructor No data_source_api_token property exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'Constructor No data_source_api_token property exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'Constructor No data_source_api_token property exception message check'
        );

        $propertiesTemplate['data_source_api_token'] = 3;


        // Batch size property < 1
        $badBatchSize = -1;
        $properties = $propertiesTemplate;
        $properties['batch_size'] = $badBatchSize;

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'Invalid batch_size property. This property must be an integer greater than 0.';
        try {
            $config = new Configuration($logger, $properties);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'Constructor batch_size < 0 exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'Constructor batch_size < 0 exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'Constructor No batch_size < 0 exception message check'
        );


        // Batch size not int or string
        $badBatchSize = null;
        $properties = $propertiesTemplate;
        $properties['batch_size'] = $badBatchSize;

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'Invalid batch_size property. This property must be an integer greater than 0.';
        try {
            $config = new Configuration($logger, $properties);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'Constructor batch_size is not int or string exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'Constructor batch_size is not int or string exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'Constructor No batch_size is not int or string exception message check'
        );


        // Bad table_prefix char
        $badTablePrefix = '+!-';
        $properties = $propertiesTemplate;
        $properties['table_prefix'] = $badTablePrefix;

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage =
            'Invalid table_prefix property. This property may only contain letters, numbers, and underscores.';
        try {
            $config = new Configuration($logger, $properties);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'Constructor Bad table_prefix char exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'Constructor Bad table_prefix char exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'Constructor Bad table_prefix char exception message check'
        );


        // Bad label_view_suffix char
        $badLabelViewSuffix = '+!-';
        $properties = $propertiesTemplate;
        $properties['label_view_suffix'] = $badLabelViewSuffix;

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage =
            'Invalid label_view_suffix property. This property may only contain letters, numbers, and underscores.';
        try {
            $config = new Configuration($logger, $properties);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'Constructor Bad label_view_suffix char exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'Constructor Bad label_view_suffix char exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'Constructor Bad label_view_suffix char exception message check'
        );


        // No db_connection property
        $properties = $propertiesTemplate;

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'No database connection was specified in the configuration.';
        try {
            $config = new Configuration($logger, $properties);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'Constructor No db_connection property exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'Constructor No db_connection property exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'Constructor No db_connection property exception message check'
        );


        // db_connection property empty
        $properties = $propertiesTemplate;
        $properties['db_connection'] = '';

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'No database connection was specified in the configuration.';
        try {
            $config = new Configuration($logger, $properties);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'Constructor db_connection property is empty exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'Constructor db_connection property is empty exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'Constructor db_connection property is empty exception message check'
        );

        $propertiesTemplate['db_connection'] = 'CSV:/tmp';

        // For various assertions that don't require an error to be caught
        $properties = $propertiesTemplate;

        $useWebScriptLogFile = true;
        $webScriptLogFile = 'web-script-log-file';
        $expectedWebScriptLogFile =
            realpath(__DIR__.'/../../src').'/'.$webScriptLogFile;
        $properties['web_script_log_file'] = $webScriptLogFile;

        $sslVerify = 'true';
        $expectedSslVerify = true;
        $properties['ssl_verify'] = $sslVerify;

        $extractedRecordCountcheck = 'true';
        $expectedExtractedRecordCountCheck = true;
        $properties['extracted_record_count_check'] =
            $extractedRecordCountcheck;

        $expectedLogProjectApiToken = null;

        $expectedTablePrefix = 'tableprefix';
        $properties['table_prefix'] = $expectedTablePrefix;

        $config =
            new Configuration($logger, $properties);


        $sslVerify = $config->getSslVerify();
        $this->assertEquals(
            $expectedSslVerify,
            $sslVerify,
            'Constructor ssl_verify set'
        );

        $extractedRecordCountcheck = $config->getExtractedRecordCountCheck();
        $this->assertEquals(
            $expectedExtractedRecordCountCheck,
            $extractedRecordCountcheck,
            'Constructor extracted_record_count_check set'
        );

        $tablePrefix = $config->getTablePrefix();
        $this->assertEquals(
            $expectedTablePrefix,
            $tablePrefix,
            'Constructor table_prefix set'
        );
    }

    public function testConfig()
    {
        $propertiesFile = __DIR__.'/../data/config-testconfiguration.ini';
        $logger = new Logger('test-app');

        $config = new Configuration($logger, $propertiesFile);
        $this->assertNotNull($config, 'config not null check');

        $retrievedLogger = $config->getLogger();
        $this->assertEquals($logger, $retrievedLogger, 'Logger check');

        $this->assertEquals(
            $logger->getApp(),
            $config->getApp(),
            'GetApp check'
        );

        $expectedDataSourceApiToken = '1111111122222222333333334444444';
        $dataSourceApiToken = $config->getDataSourceApiToken();
        $this->assertEquals(
            $expectedDataSourceApiToken,
            $dataSourceApiToken,
            'DataSourceApiToken check'
        );

        $expectedTransformRulesSource = '3';
        $transformRulesSource = $config->getTransformRulesSource();
        $this->assertEquals(
            $expectedTransformRulesSource,
            $transformRulesSource,
            'TransformRulesSource check'
        );

        $expectedTransformationRules = 'TEST RULES';
        $config->setTransformationRules($expectedTransformationRules);
        $transformationRules = $config->getTransformationRules();
        $this->assertEquals(
            $expectedTransformationRules,
            $transformationRules,
            'TransformationRules check'
        );

        $expectedBatchSize = '10';
        $config->setBatchSize($expectedBatchSize);
        $batchSize = $config->getBatchSize();
        $this->assertEquals($expectedBatchSize, $batchSize, 'BatchSize check');

        $expectedCaCertFile = 'test_cacert_file_path';
        $caCertFile = $config->getCaCertFile();
        $this->assertEquals(
            $expectedCaCertFile,
            $caCertFile,
            'CaCertFile check'
        );

        $expectedCalcFieldIgnorePattern = '/^0$/';
        $calcFieldIgnorePattern = $config->getCalcFieldIgnorePattern();
        $this->assertEquals(
            $expectedCalcFieldIgnorePattern,
            $calcFieldIgnorePattern,
            'CalcFieldIgnorePattern check'
        );

        $expectedEmailFromAddress = 'foo@bar.com';
        $emailFromAddress = $config->getEmailFromAddress();
        $this->assertEquals(
            $expectedEmailFromAddress,
            $emailFromAddress,
            'EmailFromAddress check'
        );

        $expectedEmailSubject = 'email subject';
        $emailSubject = $config->getEmailSubject();
        $this->assertEquals(
            $expectedEmailSubject,
            $emailSubject,
            'EmailSubject check'
        );

        $expectedEmailToList = 'bang@bucks.net,what@my.com';
        $emailToList = $config->getEmailToList();
        $this->assertEquals(
            $expectedEmailToList,
            $emailToList,
            'EmailToList check'
        );

        $expectedExtractedRecordCountCheck = false;
        $extractedRecordCountCheck = $config->getExtractedRecordCountCheck();
        $this->assertEquals(
            $expectedExtractedRecordCountCheck,
            $extractedRecordCountCheck,
            'ExtractedRecordCountCheck check'
        );

        $expectedFieldType = Schema\FieldType::VARCHAR;
        $expectedFieldSize = 123;

        $generatedInstanceType = $config->getGeneratedInstanceType();
        $this->assertEquals(
            $expectedFieldType,
            $generatedInstanceType->getType(),
            'GeneratedInstanceType type check'
        );
        $this->assertEquals(
            $expectedFieldSize,
            $generatedInstanceType->getSize(),
            'GeneratedInstanceType size check'
        );

        $generatedKeyType = $config->getGeneratedKeyType();
        $this->assertEquals(
            $expectedFieldType,
            $generatedKeyType->getType(),
            'GeneratedKeyType type check'
        );
        $this->assertEquals(
            $expectedFieldSize,
            $generatedKeyType->getSize(),
            'GeneratedKeyType size check'
        );

        $generatedLabelType = $config->getGeneratedLabelType();
        $this->assertEquals(
            $expectedFieldType,
            $generatedLabelType->getType(),
            'GeneratedLabelType type check'
        );
        $this->assertEquals(
            $expectedFieldSize,
            $generatedLabelType->getSize(),
            'GeneratedLabelType size check'
        );

        $generatedNameType = $config->getGeneratedNameType();
        $this->assertEquals(
            $expectedFieldType,
            $generatedNameType->getType(),
            'GeneratedNameType type check'
        );
        $this->assertEquals(
            $expectedFieldSize,
            $generatedNameType->getSize(),
            'GeneratedNameType size check'
        );

        $generatedRecordIdType = $config->getGeneratedRecordIdType();
        $this->assertEquals(
            $expectedFieldType,
            $generatedRecordIdType->getType(),
            'GeneratedRecordIdType type check'
        );
        $this->assertEquals(
            $expectedFieldSize,
            $generatedRecordIdType->getSize(),
            'GeneratedRecordIdType size check'
        );

        $generatedSuffixType = $config->getGeneratedSuffixType();
        $this->assertEquals(
            $expectedFieldType,
            $generatedSuffixType->getType(),
            'GeneratedSuffixType type check'
        );
        $this->assertEquals(
            $expectedFieldSize,
            $generatedSuffixType->getSize(),
            'GeneratedSuffixType size check'
        );

        $expectedLabelViewSuffix = 'testlabelviewsuffix';
        $labelViewSuffix = $config->getLabelViewSuffix();
        $this->assertEquals(
            $expectedLabelViewSuffix,
            $labelViewSuffix,
            'LabelViewSuffix check'
        );

        $expectedLogFile = '/tmp/logfile';
        $logFile = $config->getLogFile();
        $this->assertEquals($expectedLogFile, $logFile, 'LogFile check');

        $expectedCreateLookupTable = true;
        $createLookupTable = $config->getCreateLookupTable();
        $this->assertEquals(
            $expectedCreateLookupTable,
            $createLookupTable,
            'CreateLookupTable check'
        );

        $expectedLookupTableName = 'test_name';
        $lookupTableName = $config->getLookupTableName();
        $this->assertEquals(
            $expectedLookupTableName,
            $lookupTableName,
            'LookupTableName check'
        );

        $expectedPostProcessingSqlFile = '/tmp/postsql';
        $postProcessingSqlFile = $config->getPostProcessingSqlFile();
        $this->assertEquals(
            $expectedPostProcessingSqlFile,
            $postProcessingSqlFile,
            'PostProcessingSqlFile check'
        );

        $expectedProjectId = 7;
        $config->setProjectId($expectedProjectId);
        $projectId = $config->getProjectId();
        $this->assertEquals($expectedProjectId, $projectId, 'ProjectId check');

        $expectedREDCapApiUrl = 'https://redcap.someplace.edu/api/';
        $redcapApiUrl = $config->getREDCapApiUrl();
        $this->assertEquals(
            $expectedREDCapApiUrl,
            $redcapApiUrl,
            'REDCapApiUrl check'
        );

        $expectedSslVerify = true;
        $sslVerify = $config->getSslVerify();
        $this->assertEquals($expectedSslVerify, $sslVerify, 'SslVerify check');

        $expectedTablePrefix = '';
        $tablePrefix = $config->getTablePrefix();
        $this->assertEquals(
            $expectedTablePrefix,
            $tablePrefix,
            'TablePrefix check'
        );

        $expectedTimeLimit= 3600;
        $timeLimit = $config->getTimeLimit();
        $this->assertEquals(
            $expectedTimeLimit,
            $timeLimit,
            'Time limit check'
        );

        $expectedTimezone = 'America/Indiana/Indianapolis';
        $timezone = $config->getTimezone();
        $this->assertEquals($expectedTimezone, $timezone, 'Timezone check');
    }

    public function testConfig3()
    {
        $propertiesFile = __DIR__.'/../data/config-test3.ini';
        $logger = new Logger('test-app');

        $config = new Configuration($logger, $propertiesFile);
        $this->assertNotNull($config, 'config not null check');

        # db log table
        $expectedDbLogTable = 'my_etl_log';
        $dbLogTable = $config->getDbLogTable();
        $this->assertEquals($expectedDbLogTable, $dbLogTable, 'Db log table check');

        # db event log table
        $expectedDbEventLogTable = 'my_etl_event_log';
        $dbEventLogTable = $config->getDbEventLogTable();
        $this->assertEquals($expectedDbEventLogTable, $dbEventLogTable, 'Db event log table check');
    }
    

    public function testNullPropertiesFile()
    {
        $propertiesFile = null;
        $logger = new Logger('test-app');

        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $propertiesFile);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Exception caught');

        $expectedCode = EtlException::INPUT_ERROR;
        $this->assertEquals($expectedCode, $exception->getCode(), 'Exception code check');
    }

    public function testNonExistentPropertiesFile()
    {
        $propertiesFile = __DIR__.'/../data/non-existent-config-file.ini';
        $logger = new Logger('test-app');

        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $propertiesFile);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Exception caught');

        $expectedCode = EtlException::INPUT_ERROR;
        $this->assertEquals($expectedCode, $exception->getCode(), 'Exception code check');
    }

    public function testProperties()
    {

        // Invalid property
        // Property not defined
        // Property defined in file

        $propertiesFile = __DIR__.'/../data/config-test.ini';
        $logger = new Logger('test-app');
        $config = new Configuration($logger, $propertiesFile);

        $property = 'invalid-property';
        $expectedInfo = 'invalid property';
        $info = $config->getPropertyInfo($property);
        $this->assertEquals(
            $expectedInfo,
            $info,
            'GetProperyInfo invalid test'
        );

        $property = 'allowed_servers';
        $expectedInfo = 'undefined';
        $info = $config->getPropertyInfo($property);
        $this->assertEquals(
            $expectedInfo,
            $info,
            'GetProperyInfo undefined test'
        );

        $property = 'transform_rules_source';
        $expectedInfo = '3 - defined in file: '.
            __DIR__.'/../data/config-test.ini';
        $info = $config->getPropertyInfo($property);
        $this->assertEquals(
            $expectedInfo,
            $info,
            'GetProperyInfo in file test'
        );

        $expectedGetProperty = '3';
        $getProperty = $config->getProperty($property);
        $this->assertEquals(
            $expectedGetProperty,
            $getProperty,
            'GetProperty check'
        );

        $dbSsl = $config->getDbSsl();
        $this->assertTrue($dbSsl, 'DB SSL set to true');
        
        $dbSslVerify = $config->getDbSslVerify();
        $this->assertFalse($dbSslVerify, 'DB SSL verify set to false');
        
        # db log table
        $expectedDbLogTable = Configuration::DEFAULT_DB_LOG_TABLE;
        $dbLogTable = $config->getDbLogTable();
        $this->assertEquals($expectedDbLogTable, $dbLogTable, 'Db log table check');

        # db event log table
        $expectedDbEventLogTable = Configuration::DEFAULT_DB_EVENT_LOG_TABLE;
        $dbEventLogTable = $config->getDbEventLogTable();
        $this->assertEquals($expectedDbEventLogTable, $dbEventLogTable, 'Db event log table check');
        
        #---------------------------------------
        # Property defined in array
        #---------------------------------------
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $configMock = $reflection->newInstanceWithoutConstructor();

        $expectedProperties = array(
            'redcap_api_url' => 'https://redcap.someplace.edu/api/',
            'transform_rules_source' => '2');
        $configMock->setProperties($expectedProperties);
        $properties = $configMock->getProperties();
        $this->assertEquals(
            $expectedProperties,
            $properties,
            'Get Properties check'
        );

        $property = 'transform_rules_source';
        $expectedInfo = '2 - defined in array argument';
        $info = $configMock->getPropertyInfo($property);
        $this->assertEquals(
            $expectedInfo,
            $info,
            'GetProperyInfo in array test'
        );
    }

    public function testProcessTransformationRules()
    {
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $method = $reflection->getMethod('processTransformationRules');
        $method->setAccessible(true);
        $configMock = $reflection->newInstanceWithoutConstructor();

        // Source: _TEXT
        // Property TRANSFORM_RULES_TEXT exists
        $expectedRules = 'non-empty';
        $properties = array(
            ConfigProperties::TRANSFORM_RULES_SOURCE =>
            Configuration::TRANSFORM_RULES_TEXT,
            ConfigProperties::TRANSFORM_RULES_TEXT =>
            $expectedRules);
        $method->invokeArgs($configMock, array($properties));
        $rules = $configMock->getTransformationRules();
        $this->assertEquals(
            $expectedRules,
            $rules,
            'ProcessTransformationRules property check'
        );

        // Source: _TEXT
        // Property TRANSFORM_RULES_TEXT is empty
        $expectedRules = '';
        $properties = array(
            ConfigProperties::TRANSFORM_RULES_SOURCE =>
            Configuration::TRANSFORM_RULES_TEXT,
            ConfigProperties::TRANSFORM_RULES_TEXT =>
            $expectedRules);

        $exceptionCaught = false;
        $expectedCode = EtlException::FILE_ERROR;
        $expectedMessage = 'No transformation rules were entered.';
        try {
            $method->invokeArgs($configMock, array($properties));
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'ProcessTransformationRules TEXT empty exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'ProcessTransformationRules TEXT empty exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'ProcessTransformationRules TEXT empty exception message check'
        );

        // Source: _TEXT
        // Property TRANSFORM_RULES_TEXT is missing
        $properties = array(
            ConfigProperties::TRANSFORM_RULES_SOURCE =>
            Configuration::TRANSFORM_RULES_TEXT);

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'No transformation rules text was defined.';
        try {
            $method->invokeArgs($configMock, array($properties));
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'ProcessTransformationRules TEXT no property exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'ProcessTransformationRules TEXT no property exception code check'
        );
        $this->assertEquals(
            $expectedMessage,
            $exception->getMessage(),
            'ProcessTransformationRules TEXT no property exception message check'
        );

        // Source: _FILE
        // Local File.
        $expectedRules = "RULES PLACEHOLDER\n";
        $file = __DIR__.'/../data/rules-test.txt';

        $properties = array(
            ConfigProperties::TRANSFORM_RULES_SOURCE =>
            Configuration::TRANSFORM_RULES_FILE,
            ConfigProperties::TRANSFORM_RULES_FILE => $file);
        $method->invokeArgs($configMock, array($properties));
        $rules = $configMock->getTransformationRules();
        $this->assertEquals(
            $expectedRules,
            $rules,
            'ProcessTransformationRules local file check'
        );

        // Source: Unknown
        $properties = array(
            ConfigProperties::TRANSFORM_RULES_SOURCE => 'foo'
            );

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'Unrecognized transformation rules rouce.';
        try {
            $method->invokeArgs($configMock, array($properties));
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'ProcessTransformationRules UNKNOWN exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'ProcessTransformationRules UNKNOWN exception code check'
        );
        $this->assertEquals(
            substr($expectedMessage, 0, 15),
            substr($exception->getMessage(), 0, 15),
            'ProcessTransformationRules UNKNOWN exception message check'
        );
    }


    public function testProcessTransformationRulesDefault()
    {
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $method = $reflection->getMethod('processTransformationRules');
        $method->setAccessible(true);
        $configMock = $reflection->newInstanceWithoutConstructor();

        // Source: _DEFAULT
        $expectedRules = '';

        $properties = array(
            ConfigProperties::TRANSFORM_RULES_SOURCE =>
            Configuration::TRANSFORM_RULES_DEFAULT
        );
        $method->invokeArgs($configMock, array($properties));
        $rules = $configMock->getTransformationRules();
        $this->assertEquals(
            $expectedRules,
            $rules,
            'ProcessTransformationRules DEFAULT check'
        );
    }


    public function testProcessFile()
    {
        $logger = new Logger('configuration-test');
        $configuration = new Configuration($logger, $this->properties);
        
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $configMock = $reflection->newInstanceWithoutConstructor();

        // File is null
        // Relative path
        // Properties file is empty
        // fileShouldExist is true
        // Result: Absolute path includes directory of Configuration.php
        //         file with an empty string added to it, so just the dir.
        // NOTE: __DIR__ returns the abs path of the dir of _this_ code file.
        $file = null;
        $expectedFile = realpath(__DIR__.'/../../src/');
        $realFile = $configuration->processFile($file, true);
        $this->assertEquals($expectedFile, $realFile, 'ProcessFile null check');

        // Non-existing directory
        // Relative path
        // Properties file is not empty
        // fileShouldExist is false
        // Result: Should throw exception because directory doesn't exist
        $method = $reflection->getMethod('setPropertiesFile');
        $method->setAccessible(true);
        $method->invokeArgs(
            $configMock,
            array(__DIR__.'/ConfigurationTest.php')
        );

        $file = 'foo/bar.log';

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'Directory for file ';
        try {
            $configMock->processFile($file, false);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'ProcessFile bad dir exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'ProcessFile bad dir exception code check'
        );
        $this->assertEquals(
            substr($expectedMessage, 0, 15),
            substr($exception->getMessage(), 0, 15),
            'ProcessFile bad dir exception message check'
        );


        // File doesn't exist
        // Absolute path
        // Properties file is irrelevant
        // fileShouldExist is true
        // Result: Should throw exception because file doesn't exist
        $file = '/foo/bar.log';

        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'File "/foo/bar.log" ';
        try {
            $configMock->processFile($file, true);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'ProcessFile bad file exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'ProcessFile bad file exception code check'
        );
        $this->assertEquals(
            substr($expectedMessage, 0, 15),
            substr($exception->getMessage(), 0, 15),
            'ProcessFile bad file exception message check'
        );


        // File doesn't exist, but directory does
        // Absolute path
        // Properties file is irrelevant
        // fileShouldExist is false
        $expectedFile = realpath(__DIR__.'/../../src/').'foo.log';
        $realFile = $configMock->processFile($expectedFile, false);
        $this->assertEquals(
            $expectedFile,
            $realFile,
            'ProcessFile abs !exist check'
        );
    }

    public function testProcessDirectory()
    {
        $logger = new Logger('configuration-test');
        $configuration = new Configuration($logger, $this->properties);

        // Null argument
        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage =
            'Null path specified as argument to processDirectory';
        try {
            $configuration->processDirectory(null);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'ProcessDirectory null Exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'ProcessDirectory null exception code check'
        );
        $this->assertEquals(
            substr($expectedMessage, 0, 15),
            substr($exception->getMessage(), 0, 15),
            'ProcessDirectory null exception message check'
        );

        // Non-string argument
        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'Non-string path specified as argument';
        try {
            $configuration->processDirectory(array('foo'));
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'ProcessDirectory non-string Exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'ProcessDirectory non-string exception code check'
        );
        $this->assertEquals(
            substr($expectedMessage, 0, 15),
            substr($exception->getMessage(), 0, 15),
            'ProcessDirectory non-string exception message check'
        );

        // Absolute path argument
        $expectedRealDir = '/tmp';
        $realDir = $configuration->processDirectory($expectedRealDir);

        $this->assertEquals(
            $expectedRealDir,
            $realDir,
            'ProcessDirectory absolute'
        );

        // Relative path argument, no properties file
        // NOTE: Because PHPUnit runs the test from the 'tests/unit'
        //       directory, the __DIR__ variable will include tests/unit
        //       already.
        $path = '../tests/unit/Database';
        $expectedRealDir = __DIR__.'/Database';
        $realDir = $configuration->processDirectory($path);

        $this->assertEquals(
            $expectedRealDir,
            $realDir,
            'ProcessDirectory relative, no properties'
        );

        // Relative path argument, no properties file, dir not found
        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'Directory';
        try {
            $configuration->processDirectory('foo');
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue(
            $exceptionCaught,
            'ProcessDirectory relative not found Exception caught'
        );
        $this->assertEquals(
            $expectedCode,
            $exception->getCode(),
            'ProcessDirectory relative not found exception code check'
        );
        $this->assertEquals(
            substr($expectedMessage, 9, 0),
            substr($exception->getMessage(), 9, 0),
            'ProcessDirectory relative not found exception message check'
        );

        // Relative path argument, using properties file
        // NOTE: Because PHPUnit runs the test from the 'tests/unit'
        //       directory, the __DIR__ variable will include tests/unit
        //       already. Additionally, because we've specified the
        //       properties file using __DIR_, too, then REDCapEtl will check
        //       for relative paths to the same directory as the properties
        //       file, effectively adding 'test/unit' to the relative path.
        /*
        $method = $reflection->getMethod('setPropertiesFile');
        $method->setAccessible(true);
        $method->invokeArgs($configMock, array(__DIR__.'/ConfigurationTest.php'));

        $path = 'Database';
        $expectedRealDir = __DIR__.'/Database';
        $realDir = $configMock->processDirectory($path);
        $this->assertEquals(
            $expectedRealDir,
            $realDir,
            'ProcessDirectory relative, properties file'
        );
        */
    }

    public function testIsValidEmail()
    {
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $configMock = $reflection->newInstanceWithoutConstructor();

        $validEmail = 'foo@bar.com';
        $invalidEmail = 'foo-bar-bang';

        $isValidEmail = $configMock->isValidEmail($validEmail);
        $this->assertTrue($isValidEmail, 'IsValidEmail true check');

        $isValidEmail = $configMock->isValidEmail($invalidEmail);
        $this->assertFalse($isValidEmail, 'IsValidEmail false check');
    }

    public function testDbConnection()
    {
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $configMock = $reflection->newInstanceWithoutConstructor();

        $expectedMySqlConnectionInfo = array('foo','bar','bang');
        $expectedDbConnection =
            implode(
                ':',
                array_merge(
                    array('MySQL'),
                    $expectedMySqlConnectionInfo
                )
            );
        $configMock->SetDbConnection($expectedDbConnection);

        $dbConnection = $configMock->GetDbConnection();
        $this->assertEquals(
            $expectedDbConnection,
            $dbConnection,
            'GetDbConnection check'
        );

        $mySqlConnectionInfo = $configMock->getMySqlConnectionInfo();
        $this->assertEquals(
            $expectedMySqlConnectionInfo,
            $mySqlConnectionInfo,
            'GetMySqlConnectionInfo check'
        );
    }

    public function testDbConfigError()
    {
        $propertiesFile = __DIR__.'/../data/config-test2.ini';
        $logger = new Logger('test-app');
        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $propertiesFile);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Db config error check');
    }

    public function testEmailLoggingErrors()
    {
        $properties = [
            'redcap_api_url' => 'https://someplace.edu/api/',
            'config_api_token' => '',
            'data_source_api_token' => '11111111112222222222333333333344',
            'transform_rules_source' => '3',
            'db_connection' => 'CSV:.',
            'email_errors' => 'true'
        ];

        $logger = new Logger('test-app');

        #------------------------------------------------------------------------
        # Test email_errors set without email_from_address or email_to_address
        #------------------------------------------------------------------------
        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $properties);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'E-mail errors specified without e-mail addresses');

        #------------------------------------------------------------------------
        # Test email_errors set without email_to_address
        #------------------------------------------------------------------------
        $properties['email_from_address'] = 'tester@someplace.edu';
        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $properties);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'E-mail errors specified without to e-mail address');

        #------------------------------------------------------------------------
        # Test email_errors set without email_from_address
        #------------------------------------------------------------------------
        unset($properties['email_from_address']);
        $properties['email_to_address'] = 'tester@someplace.edu';
        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $properties);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'E-mail errors specified without from e-mail address');

        #------------------------------------------------------------------------
        # Test email_summary set without email_from_address or email_to_address
        #------------------------------------------------------------------------
        unset($properties['email_errors']);
        unset($properties['email_from_address']);
        unset($properties['email_to_address']);
        $properties['email_summary'] = 'true';
        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $properties);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'E-mail summary specified without e-mail addresses');

        #------------------------------------------------------------------------
        # Test email_summary set without email_to_address
        #------------------------------------------------------------------------
        $properties['email_from_address'] = 'tester@someplace.edu';
        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $properties);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'E-mail summary specified without to e-mail address');

        #------------------------------------------------------------------------
        # Test email_summary set without email_from_address
        #------------------------------------------------------------------------
        unset($properties['email_from_address']);
        $properties['email_to_address'] = 'tester@someplace.edu';
        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $properties);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'E-mail summary specified without from e-mail address');
    }
    
    public function testOverrideProperties()
    {
        $properties = ['test1' => 'value1', 'test2' => 'val2'];
        $propertyOverrides = ['test2' => 'value2'];
        
        $expectedResult = ['test1' => 'value1', 'test2' => 'value2'];
        
        $properties = Configuration::overrideProperties($properties, $propertyOverrides);
        
        $this->assertEquals($expectedResult, $properties, 'Override test');
    }
}

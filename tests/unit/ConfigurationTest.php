<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

define('USERID', 'testuser');
define('PROJECT_ID', 123);
define('APP_PATH_WEBROOT_FULL', 'http://localhost/redcap/');

class ConfigurationTest extends TestCase
{
    public function testCreate()
    {
        $config = new Configuration('test');
        $this->assertNotNull($config, 'Object creation test');

        $this->assertEquals('test', $config->getName(), 'Configuration name test');
    }

    public function testNameValidation()
    {
        $result = Configuration::validateName('test');
        $this->assertTrue($result, 'Config name "test" test.');

        $result = Configuration::validateName('Test Configuration');
        $this->assertTrue($result, 'Config name "Test Configuration" test.');

        $exceptionCaught = false;
        try {
            $result = Configuration::validateName('<script>alert(123);</script>');
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Config name script exception)');

        $exceptionCaught = false;
        try {
            $result = Configuration::validateName('%3CIMG');
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Config % exception)');

        $exceptionCaught = false;
        try {
            $result = Configuration::validateName('`ls -l`');
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Config backtick exception)');

        $exceptionCaught = false;
        try {
            $result = Configuration::validateName('&amp');
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Config & exception)');


        $exceptionCaught = false;
        try {
            $result = Configuration::validateName('amp;');
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Config ; exception)');
    }


    public function testValidateForRunning()
    {
        $configuration = new Configuration('test');

        $properties = array();
        $properties[Configuration::REDCAP_API_URL] = '';
        $properties[Configuration::API_TOKEN_USERNAME] = 'test_user';
        $properties[Configuration::TRANSFORM_RULES_TEXT] = 'TABLE,root,root_id,ROOT';
        $properties[Configuration::BATCH_SIZE] = '100';
        $properties[Configuration::DB_TYPE] = 'MySQL';
        $properties[Configuration::DB_HOST] = 'localhost';
        $properties[Configuration::DB_PORT] = '';
        $properties[Configuration::DB_NAME] = 'redcap_etl';
        $properties[Configuration::DB_USERNAME] = 'etl_user';
        $properties[Configuration::DB_PASSWORD] = 'EtlUserPassword';

        #------------------------------------------
        # API token username
        #------------------------------------------
        $originalValue = $properties[Configuration::API_TOKEN_USERNAME];
        $properties[Configuration::API_TOKEN_USERNAME] = '';
        $configuration->set($properties);
        $exceptionCaught = false;
        try {
            $configuration->validateForRunning();
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'API token exception check');
        $this->assertMatchesRegularExpression('/No API token specified/', $message, 'API Token message check');
        $properties[Configuration::API_TOKEN_USERNAME] = $originalValue;
    }


    public function testValidateRedcapApiUrl()
    {
        $exceptionCaught = false;
        try {
            $result = Configuration::validateRedcapApiUrl("This is not a URL.");
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Invalid URL exception caught');
    }


    public function testValidateApiToken()
    {
        # Invalid characters
        $exceptionCaught = false;
        try {
            $result = Configuration::validateApiToken("incorrect");
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Invalid characters caught');
        $this->assertStringContainsString('should only contain', $message, 'Invalid characters message');

        # Invalid length
        $exceptionCaught = false;
        try {
            $result = Configuration::validateApiToken("123456789012345678901234567890");
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Invalid length caught');
        $this->assertStringContainsString('length of', $message, 'Invalid length message');
    }


    public function testValidateBatchSize()
    {
        # Negative batch size
        $exceptionCaught = false;
        try {
            $result = Configuration::validateBatchSize("-10");
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Invalid batch size caught');
        $this->assertStringContainsString('positive integer', $message, 'Invalid batch size message');
    }

    public function testDatabaseProperties()
    {
        $preSql  = 'DROP TABLE test;';
        $postSql = 'CREATE TABLE test (i int);';
        $properties = [
            Configuration::DB_TYPE => \IU\REDCapETL\Database\DbConnectionFactory::DBTYPE_MYSQL,
            Configuration::DB_HOST => 'localhost',
            Configuration::DB_PORT => '',
            Configuration::DB_NAME => 'test',
            Configuration::DB_USERNAME => 'etl_user',
            Configuration::DB_PASSWORD => 'etl_password',
            Configuration::PRE_PROCESSING_SQL  => $preSql,
            Configuration::POST_PROCESSING_SQL => $postSql
        ];

        $configuration = new Configuration('test');
        $configuration->set($properties);

        $dbConnection = $configuration->getProperty(Configuration::DB_CONNECTION);
        $this->assertEquals('MySQL:localhost:etl_user:etl_password:test', $dbConnection, 'DB connection test');

        $configPreSql = $configuration->getProperty(Configuration::PRE_PROCESSING_SQL, 'Pre-Processing SQL test');
        $this->assertEquals($preSql, $configPreSql, 'Pre-SQL test');

        $configPostSql = $configuration->getProperty(Configuration::POST_PROCESSING_SQL, 'Post-Processing SQL test');
        $this->assertEquals($postSql, $configPostSql, 'Post-SQL test');
    }
}

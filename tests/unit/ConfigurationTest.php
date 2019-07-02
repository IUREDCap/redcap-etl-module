<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

define('USERID', 'testuser');
define('PROJECT_ID', 123);
define('APP_PATH_WEBROOT_FULL', '/var/www/html/redcap/');

class ConfigurationTest extends TestCase
{

    public function setup()
    {
    }

    public function testCreate()
    {
        $config = new Configuration('test');
        $this->assertNotNull($config, 'Object creation test');
        
        $this->assertEquals('test', $config->getName(), 'Configuration name test');
    }
    
    public function testValidation()
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
}

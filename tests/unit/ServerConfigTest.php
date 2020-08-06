<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class ServerConfigTest extends TestCase
{
    public function testCreate()
    {
        $serverConfig = new ServerConfig('test');
        $this->assertNotNull($serverConfig, 'Object creation test');
    }

    public function testIsActive()
    {
        $serverConfig = new ServerConfig('test');

        # isActive should have a default setting of false
        $isActive = $serverConfig->getIsActive();
        $this->assertFalse($isActive, 'Default isActive test');

        $serverConfig->setIsActive(true);
        $isActive = $serverConfig->getIsActive();
        $this->assertTrue($isActive, 'isActive set to true test');
    }

    public function testNameValidation()
    {
        $serverConfig = new ServerConfig('test');

        $valid = $serverConfig->validateName('local');
        $this->assertTrue($valid, 'Valid name test');

        $exceptionCaught = false;
        try {
            $valid = $serverConfig->validateName([123]);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Invalid type test');

        $exceptionCaught = false;
        try {
            $valid = $serverConfig->validateName('<script>alert(123);</script>');
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Invalid character test');
    }

    public function testRun()
    {
        $serverConfig = new ServerConfig('test');

        $etlConfig = null;
        $exceptionCaught = false;
        try {
            $serverConfig->run($etlConfig);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Unset ETL config exception test');

        $etlConfigMock = $this->createMock(Configuration::class);
        $exceptionCaught = false;
        try {
            $serverConfig->run($etlConfigMock);
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Inactive server exception test');
    }

    public function testAccessLevel()
    {
        $serverConfig = new ServerConfig('test');

        # accessLevel should have a default setting of public
        $accessLevel = $serverConfig->getAccessLevel();
        $expected = 'public';
        $this->assertEquals($expected, $accessLevel, 'Default accessLevel test');

        # should be able to assign an valid access level
        $newAccessLevel = 'admin';
        $serverConfig->setAccessLevel($newAccessLevel);
        $accessLevel = $serverConfig->getAccessLevel();
        $this->assertEquals($newAccessLevel, $accessLevel, 'Assign accessLevel test');
    }
}

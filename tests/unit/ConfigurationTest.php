<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{

    public function setup()
    {
        define('USERID', 'testuser');
        define('PROJECT_ID', 123);
        define('APP_PATH_WEBROOT_FULL', '/var/www/html/redcap/');
    }

    public function testCreate()
    {
        $config = new Configuration('test');
        $this->assertNotNull($config, 'Object creation test');
        
        $this->assertEquals('test', $config->getName(), 'Configuration name test');
    }
}

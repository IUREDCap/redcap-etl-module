<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class UserInfoTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $username = "user";
        $userInfo = new UserInfo($username);
        $this->assertNotNull($userInfo, 'Object creation test');
        
        $this->assertEquals($username, $userInfo->getUsername(), 'Username test');
        
        $configName = 'config1';
        $this->assertFalse($userInfo->hasConfigName($configName), 'Initial config name check');

        $userInfo->addConfigName($configName);
        $this->assertTrue($userInfo->hasConfigName($configName), 'Has config name check');
        
        $userInfo->removeConfigName($configName);
        $this->assertFalse($userInfo->hasConfigName($configName), 'Remove config name check');
    }
}

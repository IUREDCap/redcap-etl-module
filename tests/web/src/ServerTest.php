<?php
#-------------------------------------------------------
# Copyright (C) 2020 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule\WebTests;

use PHPUnit\Framework\TestCase;

use Behat\Mink\Mink;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver;

class ServerTest extends TestCase
{
    private static $testConfig;
    private static $mink;
    private static $session;

    public static function setUpBeforeClass(): void
    {
        self::$testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $baseUrl = self::$testConfig->getRedCap()['base_url'];

        self::$mink = new Mink(array(
            'browser' => new Session(new ChromeDriver('http://localhost:9222', null, $baseUrl))
        ));

        self::$session = self::$mink->getSession('browser');

        $cookieName  = 'code-coverage-id';
        $cookieValue = 'web-test';

        self::$session->visit($baseUrl);
        self::$session->setCookie($cookieName, $cookieValue);
    }

    public static function tearDownAfterClass(): void
    {
        self::$mink->stopSessions();
    }

    public function testServerWithPasswordAuthentication()
    {
        $serverName = 'password_authentication';

        $username = self::$testConfig->getUser()['username'];

        $serverConfig = self::$testConfig->getServerConfig($serverName);

        if (!isset($serverConfig)
            || !is_array($serverConfig)
            || !array_key_exists('active', $serverConfig)
            || $serverConfig['active'] != 1
        ) {
            $this->markTestSkipped('Incompete "' . $serverName . '" server configuration');
        }

        $this->assertNotNull($serverConfig);

        # print_r($serverConfig);

        # Access the REDCap-ETL admin interface
        Util::logInAsAdminAndAccessRedCapEtl(self::$session);
        $page = self::$session->getPage();
        $text = $page->getText();

        # Go to the ETL Servers page
        $page->clickLink('ETL Servers');
        $text = $page->getText();
        $this->assertMatchesRegularExpression("/Server Name/", $text); 

        EtlServersPage::deleteServer(self::$session, $serverName);
        EtlServersPage::addServer(self::$session, $serverName);
        EtlServersPage::followServer(self::$session, $serverName);
        EtlServerConfigPage::configureServer(self::$session, $serverName);
        EtlServersPage::followServer(self::$session, $serverName);

        #-------------------------------------------------
        # Test the ETL server connection
        #-------------------------------------------------
        $page = self::$session->getPage();
        $page->pressButton('Test Server Connection');
        $testOutput = $page->findById("testOutput")->getValue();
        $this->assertMatchesRegularExpression("/SUCCESS/", $testOutput); 
        $this->assertMatchesRegularExpression("/output of hostname command:/", $testOutput); 

        Util::logout(self::$session);   # logout as admin

        $this->runEtlOnRemoteServer($serverName);
    }

    public function testServerWithSshKeyAuthentication()
    {
        $serverName = 'ssh_key_authentication';

        $username = self::$testConfig->getUser()['username'];
        $testProjectTitle = self::$testConfig->getUser()['test_project_title'];

        $serverConfig = self::$testConfig->getServerConfig($serverName);

        if (!isset($serverConfig)
            || !is_array($serverConfig)
            || !array_key_exists('active', $serverConfig)
            || $serverConfig['active'] != 1
        ) {
            $this->markTestSkipped('Incompete "' . $serverName . '" server configuration');
        }

        $this->assertNotNull($serverConfig);

        # print_r($serverConfig);

        # Access the REDCap-ETL admin interface
        Util::logInAsAdminAndAccessRedCapEtl(self::$session);
        $page = self::$session->getPage();
        $text = $page->getText();

        # Go to the ETL Servers page
        $page->clickLink('ETL Servers');
        $text = $page->getText();
        $this->assertMatchesRegularExpression("/Server Name/", $text); 

        EtlServersPage::deleteServer(self::$session, $serverName);
        EtlServersPage::addServer(self::$session, $serverName);
        EtlServersPage::followServer(self::$session, $serverName);
        EtlServerConfigPage::configureServer(self::$session, $serverName);
        EtlServersPage::followServer(self::$session, $serverName);

        #-------------------------------------------------
        # Test the ETL server connection
        #-------------------------------------------------
        $page = self::$session->getPage();
        $page->pressButton('Test Server Connection');
        sleep(4);
        $testOutput = $page->findById("testOutput")->getValue();
        $this->assertMatchesRegularExpression("/SUCCESS/", $testOutput); 
        $this->assertMatchesRegularExpression("/output of hostname command:/", $testOutput); 

        Util::logout(self::$session);

        $this->runEtlOnRemoteServer($serverName);
    }

    public function runEtlOnRemoteServer($serverName)
    {

        Util::logInAsUserAndAccessRedCapEtlForTestProject(self::$session);
        $page = self::$session->getPage();

        # Need to create configuration
        $page->clickLink('ETL Configurations');
        $configName = 'remote-server-test';
        EtlConfigsPage::deleteConfigurationIfExists(self::$session, $configName);
        EtlConfigsPage::addConfiguration(self::$session, $configName);

        EtlConfigsPage::followConfiguration(self::$session, $configName);

        ConfigurePage::configureConfiguration(self::$session, 'behat');


        $page->clickLink('Run');
        $text = $page->getText();
        $this->assertMatchesRegularExpression("/Configuration:/", $text); 
        $this->assertMatchesRegularExpression("/Run Now/", $text); 

        RunPage::runConfiguration(self::$session, $configName, $serverName);
        sleep(4);
        $text = $page->getText();
        $this->assertMatchesRegularExpression("/Your job has been submitted to server/", $text); 

        Util::logout(self::$session);
    }
}

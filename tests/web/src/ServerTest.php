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
        EtlServersPage::configureServer(self::$session, $serverName);
        EtlServersPage::followServer(self::$session, $serverName);

        #-------------------------------------------------
        # Test the ETL server connection
        #-------------------------------------------------
        $page = self::$session->getPage();
        $page->pressButton('Test Server Connection');
        $testOutput = $page->findById("testOutput")->getValue();
        $this->assertMatchesRegularExpression("/SUCCESS/", $testOutput); 
        $this->assertMatchesRegularExpression("/output of hostname command:/", $testOutput); 

        Util::logout(self::$session);
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
        EtlServersPage::configureServer(self::$session, $serverName);
        EtlServersPage::followServer(self::$session, $serverName);

        #-------------------------------------------------
        # Test the ETL server connection
        #-------------------------------------------------
        $page = self::$session->getPage();
        $page->pressButton('Test Server Connection');
        $testOutput = $page->findById("testOutput")->getValue();
        $this->assertMatchesRegularExpression("/SUCCESS/", $testOutput); 
        $this->assertMatchesRegularExpression("/output of hostname command:/", $testOutput); 

        Util::logout(self::$session);
    }
}

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

/**
 * PHPUnit-based web test for ETL servers.
 */
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

        Util::logInAsAdminAndAccessRedCapEtl(self::$session);

        $this->configureEtlServer($serverName);

        $this->checkEtlServer();

        Util::logout(self::$session);   # logout as admin

        $this->runEtlOnRemoteServer($serverName, 'REDCap-ETL PHPUnit web test 1/2');
    }

    public function testServerWithSshKeyAuthentication()
    {
        $serverName = 'ssh_key_authentication';

        $username = self::$testConfig->getUser()['username'];

        Util::logInAsAdminAndAccessRedCapEtl(self::$session);

        $this->configureEtlServer($serverName);

        $this->checkEtlServer();

        Util::logout(self::$session);

        $this->runEtlOnRemoteServer($serverName, 'REDCap-ETL PHPUnit web test 2/2');
    }

    public function configureEtlServer($serverName)
    {
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
        $page = self::$session->getPage();
        $text = $page->getText();

        # Go to the ETL Servers page
        $page->clickLink('ETL Servers');
        $text = $page->getText();
        $this->assertMatchesRegularExpression("/Server Name/", $text); 

        # print "\nSERVER NAME: {$serverName}\n\n";

        EtlServersPage::deleteServer(self::$session, $serverName);
        $page = self::$session->getPage();
        $text = $page->getText();
        # print $text . "\n";
        # print "-------------------------------------------------------------------\n";

        EtlServersPage::addServer(self::$session, $serverName);
        EtlServersPage::followServer(self::$session, $serverName);

        $page = self::$session->getPage();
        $text = $page->getText();
        # print $text . "\n";

        EtlServerConfigPage::configureServer(self::$session, $serverName);

        EtlServersPage::followServer(self::$session, $serverName);
    }

    /**
     * Checks the ETL server connection using the test functionality built into the web page.
     * This method assumes that the current page is the configuration page for the ETL
     * configuration to be tested.
     */
    public function checkEtlServer()
    {
        $page = self::$session->getPage();
        #print "\nPAGE:\n";
        #print_r($page);
        $page->pressButton('Test Server Connection');
        sleep(6);
        $testOutput = $page->findById("testOutput")->getValue();
        $this->assertMatchesRegularExpression("/SUCCESS/", $testOutput); 
        $this->assertMatchesRegularExpression("/output of hostname command:/", $testOutput); 
    }

    public function runEtlOnRemoteServer($serverName, $emailSubject)
    {

        Util::logInAsUserAndAccessRedCapEtlForTestProject(self::$session);
        $page = self::$session->getPage();

        # Need to create configuration
        $page->clickLink('ETL Configurations');
        $configName = 'remote-server-test';
        EtlConfigsPage::deleteConfigurationIfExists(self::$session, $configName);
        EtlConfigsPage::addConfiguration(self::$session, $configName);

        EtlConfigsPage::followConfiguration(self::$session, $configName);

        ConfigurePage::configureConfiguration(self::$session, 'behat', $emailSubject);

        #$page->clickLink('Run');
        $page->clickLink('ETL Configurations');
        # Find the table row where the first element matches the configuration name,
        # and then get the 2nd column element (the 'Run' icon') and click it
        $element = $page->find("xpath", "//tr/td[text()='".$configName."']/following-sibling::td[2]");
        $element->click();

        $text = $page->getText();
        $this->assertMatchesRegularExpression("/ETL Configuration/", $text); 
        $this->assertMatchesRegularExpression("/Run/", $text); 

        try {
            RunPage::runConfiguration(self::$session, $configName, $serverName);
            sleep(6);
            $text = $page->getText();
            $this->assertMatchesRegularExpression("/Your job has been submitted to server/", $text); 
        } catch (\Exception $exception) {
            print "Error running configuration: " . $exception->getMessage() . "\n";
            $trace = htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES);
            print "\n" . $trace . "\n";
            sleep(20);
        }

        Util::logout(self::$session);
    }
}

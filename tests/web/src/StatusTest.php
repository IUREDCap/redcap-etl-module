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

class StatusTest extends TestCase
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


    public function testUserSetup()
    {
        $username = self::$testConfig->getUser()['username'];
        $testProjectTitle = self::$testConfig->getUser()['test_project_title'];

        Util::loginAsUser(self::$session);
        $page = self::$session->getPage();
        $text = $page->getText();

        # Check that the test user is set up
        $this->assertMatchesRegularExpression("/Logged in as {$username}/", $text); 

        # Make sure you are on the REDCap "My Projects" page
        $page->clickLink('My Projects');

        # Check that test REDCap ETL project is set up correctly
        Util::selectTestProject(self::$session);
        $page = self::$session->getPage();
        $page->clickLink('REDCap-ETL');
        $text = $page->getText();
        #$this->assertMatchesRegularExpression("/ETL Configurations/", $text); 
        $this->assertMatchesRegularExpression("/ETL Tasks/", $text); 

        // Test logout
        $page->clickLink('Log out');
        $text = $page->getText();
        $this->assertTrue((strpos($text, $username) === false), 'Page does not contain username');
    }

    public function testAdminSetup()
    {
        Util::loginAsAdmin(self::$session);
        $page = self::$session->getPage();
        $page->clickLink('Control Center');

        $link = $page->findLink('REDCap-ETL');
        $this->assertNotNull($link, 'REDCap-ETL admin link not null check');
        $page->clickLink('REDCap-ETL');

        $text = $page->getText();
        $this->assertMatchesRegularExpression("/REDCap-ETL Admin/", $text);

        $page->clickLink("ETL Servers");
        $text = $page->getText();
        $this->assertMatchesRegularExpression("/\(embedded server\)(\s)*public/", $text);
    }
}

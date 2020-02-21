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

class UserTest extends TestCase
{
    private static $testConfig;
    private static $mink;
    private static $session;

    public static function setUpBeforeClass()
    {
        self::$testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $baseUrl = self::$testConfig->getRedCap()['base_url'];

        self::$mink = new Mink(array(
            'browser' => new Session(new ChromeDriver('http://localhost:9222', null, $baseUrl))
        ));

        self::$session = self::$mink->getSession('browser');
    }

    public function testLoginAndLogout()
    {
        Util::loginAsUser(self::$session);
        $page = self::$session->getPage();
        $text = $page->getText();

        $username = self::$testConfig->getUser()['username'];
        $this->assertRegExp("/Logged in as {$username}/", $text); 

        $page->clickLink('Log out');
        $text = $page->getText();
        $this->assertTrue((strpos($text, $username) === false), 'Page does not contain username');
    }
}

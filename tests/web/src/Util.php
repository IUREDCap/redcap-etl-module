<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule\WebTests;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Utility class that has helpful methods.
 */
class Util
{
    /**
     * Logs in to REDCap as the test user.
     *
     * @param Behat\Mink\Session $session the testing session.
     */
    public static function loginAsUser($session)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $baseUrl  = $testConfig->getRedCap()['base_url'];
        $username = $testConfig->getUser()['username'];
        $password = $testConfig->getUser()['password'];

        $session->visit($baseUrl);

        $page = $session->getPage();

        # Search for text "Logged in as user {$username}"

        $page->fillField('username', $username);
        $page->fillField('password', $password);
        $page->pressButton('login_btn');
    }
    
    /**
     * Logs in as the (non-admin) test user and accesses REDCap-ETL for the
     * test project.
     */
    public static function logInAsUserAndAccessRedCapEtlForTestProject($session)
    {
        self::logInAsUser($session);
        $page = $session->getPage();
        $page->clickLink('My Projects');
        self::selectTestProject($session);
        $page->clickLink('REDCap-ETL');
    }

    /**
     * Logs in to REDCap as the admin.
     */
    public static function loginAsAdmin($session)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $baseUrl  = $testConfig->getRedCap()['base_url'];
        $username = $testConfig->getAdmin()['username'];
        $password = $testConfig->getAdmin()['password'];

        $session->visit($baseUrl);

        $page = $session->getPage();

        $page->fillField('username', $username);
        $page->fillField('password', $password);
        $page->pressButton('login_btn');
    }

    /**
     * Logs out of REDCap.
     */
    public static function logOut($session)
    {
        $page = $session->getPage();
        $page->clickLink('Log out');
    }


    /**
     * Logs in to REDCap as the admin and accesses the REDCap-ETL admin interface.
     */
    public static function logInAsAdminAndAccessRedCapEtl($session)
    {
        self::loginAsAdmin($session);

        $page = $session->getPage();
        $page->clickLink('Control Center');
        $page->clickLink('REDCap-ETL');
    }

    /**
     * Checks for module page tabs.
     *
     * @param array $tabs array of strings that are tab names
     * @param boolean $shouldFind if true, checks that tabs exist, if false
     *     checks that tabs do not exist.
     */
    public static function checkTabs($session, $tabs, $shouldFind = true)
    {
        $page = $session->getPage();
        $element = $page->find('css', '#sub-nav');

        foreach ($tabs as $tab) {
            $link = $element->findLink($tab);
            if (empty($link)) {
                if ($shouldFind) {
                    throw new \Exception("Tab {$tab} not found.");
                }
            } else {
                if (!$shouldFind) {
                    throw new \Exception("Tab {$tab} found.");
                }                
            }
        }
    }
    
    /**
     * Checks that the specified table headers exist on the current page.
     *
     * @param array $headers array of strings that are table headers.
     */
    public static function checkTableHeaders($session, $headers)
    {
        $page = $session->getPage();
        $elements = $page->findAll('css', 'th');
        
        $headersMap = array();
        if (!empty($elements)) {
            foreach ($elements as $element) {
                $headersMap[$element->getText()] = 1;
            }
        }

        foreach ($headers as $header) {
            if (!array_key_exists($header, $headersMap)) {
                throw new \Exception("Table header \"{$header}\" not found.");
            }
        }
    }
            
    public static function accessTestProjectRedCapEtl($session)
    {
        self::loginAsUser($session);
        self::selectTestProject($session);
        $page = $session->getPage();
        $page->clickLink('REDCap-ETL');
    }

    public static function selectTestProject($session)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $testProjectTitle = $testConfig->getUser()['test_project_title'];

        $page = $session->getPage();

        $page->clickLink($testProjectTitle);
    }

    public static function deleteConfiguration($session, $configName)
    {
        $this->loginAsUser($session);
        $page = $session->getPage();
        // to be completed...
    }

    public static function createProject($session, $projectTitle)
    {
        self::loginAsUser($session);
        $page = $session->getPage();

        $page->clickLink('New Project');
    }

    public static function selectUserFromSelect($session, $select)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $baseUrl  = $testConfig->getRedCap()['base_url'];
        $username = $testConfig->getUser()['username'];

        $page = $session->getPage();
        $page->selectFieldOption($select, $username);
    }


    public static function mailinator($session, $emailPrefix)
    {
        $session->visit("https://www.mailinator.com");
        $page = $session->getPage();
        $page->fillField('inboxfield', $emailPrefix);
        $page->pressButton('Go!');
    }
}

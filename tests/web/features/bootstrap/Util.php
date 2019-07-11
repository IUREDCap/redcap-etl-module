<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Defines application features from the specific context.
 */
class Util
{
    const CONFIG_FILE = __DIR__.'/../../config.ini';

    public static function loginAsUser($session)
    {
        $testConfig = new TestConfig(self::CONFIG_FILE);
        $baseUrl  = $testConfig->getRedCap()['base_url'];
        $username = $testConfig->getUser()['username'];
        $password = $testConfig->getUser()['password'];

        $session->visit($baseUrl);

        $page = $session->getPage();

        $page->fillField('username', $username);
        $page->fillField('password', $password);
        $page->pressButton('login_btn');
    }

    public static function loginAsAdmin($session)
    {
        $testConfig = new TestConfig(self::CONFIG_FILE);
        $baseUrl  = $testConfig->getRedCap()['base_url'];
        $username = $testConfig->getAdmin()['username'];
        $password = $testConfig->getAdmin()['password'];

        $session->visit($baseUrl);

        $page = $session->getPage();

        $page->fillField('username', $username);
        $page->fillField('password', $password);
        $page->pressButton('login_btn');
    }


    public static function accessAdminInterface($session)
    {
        $testConfig = new TestConfig(self::CONFIG_FILE);
        $baseUrl  = $testConfig->getRedCap()['base_url'];
        $username = $testConfig->getAdmin()['username'];
        $password = $testConfig->getAdmin()['password'];

        $session->visit($baseUrl);

        $page = $session->getPage();

        $page->fillField('username', $username);
        $page->fillField('password', $password);
        $page->pressButton('login_btn');

        $page->clickLink('Control Center');
        $page->clickLink('REDCap-ETL');
    }


    public static function selectTestProject($session)
    {
        $testConfig = new TestConfig(self::CONFIG_FILE);
        $baseUrl  = $testConfig->getRedCap()['base_url'];
        $testProjectTitle = $testConfig->getUser()['test_project_title'];

        $page = $session->getPage();

        $page->clickLink($testProjectTitle);
    }

    public static function addUserAsAdmin($session, $username, $firstName, $lastName, $password, $email)
    {
        self::loginAsAdmin($session);
        $page = $session->getPage();

        $page->clickLink('Control Center');
        $page->clickLink('Add Users (Table-based Only)');
        // ...
    }

    public static function createProject($session, $projectTitle)
    {
        self::loginAsUser($session);
        $page = $session->getPage();

        $page->clickLink('New Project');
    }

    public static function selectUserFromSelect($session, $select)
    {
        $testConfig = new TestConfig(self::CONFIG_FILE);
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

    public static function copyServer($session, $serverName, $copyToServerName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the server name, and then get the
        # 4th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$serverName."']/following-sibling::td[3]");
        $element->click();

        # Handle confirmation dialog
        $page->fillField("copy-to-server-name", $copyToServerName);
        $page->pressButton("Copy server");
    }

    public static function renameServer($session, $serverName, $renameNewServerName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the server name, and then get the
        # 5th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$serverName."']/following-sibling::td[4]");
        $element->click();

        # Handle confirmation dialog
        $page->fillField("rename-new-server-name", $renameNewServerName);
        $page->pressButton("Rename server");
    }


    /**
     * Deletes the specified server from the ETL servers list page
     *
     * @param string $serverName the name of the server to delete.
     */
    public static function deleteServer($session, $serverName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the server name, and then get the
        # 6th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$serverName."']/following-sibling::td[5]");
        $element->click();

        # Handle confirmation dialog
        $page->pressButton("Delete server");
    }
}

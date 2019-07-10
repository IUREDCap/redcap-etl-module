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
    public static function loginAsUser($session)
    {
        $properties = parse_ini_file(__DIR__.'/../../config.ini');
        $baseUrl  = $properties['base_url'];
        $username = $properties['username'];
        $password = $properties['password'];

        $session->visit($baseUrl);

        $page = $session->getPage();

        $page->fillField('username', $username);
        $page->fillField('password', $password);
        $page->pressButton('login_btn');
    }

    public static function loginAsAdmin($session)
    {
        $properties = parse_ini_file(__DIR__.'/../../config.ini');
        $baseUrl  = $properties['base_url'];
        $username = $properties['admin_username'];
        $password = $properties['admin_password'];

        $session->visit($baseUrl);

        $page = $session->getPage();

        $page->fillField('username', $username);
        $page->fillField('password', $password);
        $page->pressButton('login_btn');
    }


    public static function accessAdminInterface($session)
    {
        $properties = parse_ini_file(__DIR__.'/../../config.ini');
        $baseUrl  = $properties['base_url'];
        $username = $properties['admin_username'];
        $password = $properties['admin_password'];

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
        $properties = parse_ini_file(__DIR__.'/../../config.ini');
        $testProjectTitle = $properties['test_project_title'];

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
        $properties = parse_ini_file(__DIR__.'/../../config.ini');
        $username = $properties['username'];

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

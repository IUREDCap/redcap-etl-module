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
 * Class for interacting with the admin "ETL Server Config" page. Methods assume that
 * the current page is an ETL server configuration page.
 */
class EtlServerConfigPage
{
    /**
     * Indicates if the server is active.
     */
    public static function isActive($session)
    {
        $page = $session->getPage();
        $isActive = $page->hasCheckedField('isActive');
        return $isActive;
    }

    public static function getAccessLevel($session)
    {
        $page = $session->getPage();
        $element = $page->findById('accessLevelId');
        $accessLevel = $element->getValue();
        return $accessLevel;
    }

    public static function configureServer($session, $serverName)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);

        $serverConfig = $testConfig->getServerConfig($serverName);

        $page = $session->getPage();

        if ($serverConfig['active'] === 'true' || $serverConfig['active'] === '1') {
            $page->checkField('isActive');
        }

        if ($serverConfig['server_address']) {
            $page->selectFieldOption('accessLevel', $serverConfig['access_level']);
        } else {
            $page->selectFieldOption('accessLevel', 'public');
        }

        $page->fillField('serverAddress', $serverConfig['server_address']);
        if ($serverConfig['auth_method'] === "0") {
            $element = $page->find('xpath', "//*[@id='authMethodSshKey']");
            $element->click();
            $page->fillField('username', $serverConfig['username']);
            $page->fillField('sshKeyFile', $serverConfig['ssh_key_file']);
            $page->fillField('sshKeyPassword', $serverConfig['ssh_key_password']);
        } elseif ($serverConfig['auth_method'] === "1") {
            $element = $page->find('xpath', "//*[@id='authMethodPassword']");
            $element->click();
            $page->fillField('username', $serverConfig['username']);
            $page->fillField('password', $serverConfig['password']);
        }

        $page->fillField('configDir', $serverConfig['configuration_directory']);
        $page->fillField('etlCommand', $serverConfig['etl_command']);

        $page->fillField('emailFromAddress', $serverConfig['email_from_address']);

        if ($serverConfig['enable_error_email'] === 'true' || $serverConfig['enable_error_email'] === '1') {
            $page->checkField('enableErrorEmail');
        }

        if ($serverConfig['enable_summary_email'] === 'true' || $serverConfig['enable_summary_email'] === '1') {
            $page->checkField('enableSummaryEmail');
        }

        #sleep(2);

        #------------------------------
        # Save
        #------------------------------
        $page->pressButton('Save');
    }
}

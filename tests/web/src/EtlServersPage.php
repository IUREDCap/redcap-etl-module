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
 * Class for interacting with the admin "ETL Servers" page.
 */
class EtlServersPage
{
    public static function followServer($session, $serverName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the server name, and then get the
        # 4th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$serverName."']/following-sibling::td[3]");
        $element->click();
    }

    public static function addServer($session, $serverName)
    {
        $page = $session->getPage();
        $page->fillField('server-name', $serverName);
        $page->pressButton('Add Server');
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

        sleep(7);

        #------------------------------
        # Save
        #------------------------------
        $page->pressButton('Save');
    }

    public static function copyServer($session, $serverName, $copyToServerName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the server name, and then get the
        # 5th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$serverName."']/following-sibling::td[4]");
        $element->click();

        # Handle confirmation dialog
        $page->fillField("copy-to-server-name", $copyToServerName);
        $page->pressButton("Copy server");
    }

    public static function renameServer($session, $serverName, $renameNewServerName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the server name, and then get the
        # 6th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$serverName."']/following-sibling::td[5]");
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
        # 7th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$serverName."']/following-sibling::td[6]");

        if (isset($element)) {
            $element->click();

            # Handle confirmation dialog
            $page->pressButton("Delete server");
        }
    }
}

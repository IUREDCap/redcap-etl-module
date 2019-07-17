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
 * Class for interacting with the user "ETL Configurations" page.
 */
class EtlConfigsPage
{
    public static function followConfiguration($session, $configName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the config name, and then get the
        # 3rd column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$configName."']/following-sibling::td[2]");
        $element->click();
    }

    public static function configureConfiguration($session, $configName)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);

        $etlConfig = $testConfig->getEtlConfig($configName);

        $dbHost     = $etlConfig['db_host'];
        $dbName     = $etlConfig['db_name'];
        $dbUser     = $etlConfig['db_user'];
        $dbPassword = $etlConfig['db_password'];

        $page = $session->getPage();

        $page->selectFieldOption('api_token_username', $etlConfig['api_token_username']);

        $page->pressButton('Auto-Generate');

        #------------------------------
        # Load database
        #------------------------------
        $page->fillField('db_host', $dbHost);
        $page->fillField('db_name', $dbName);
        $page->fillField('db_username', $dbUser);
        $page->fillField('db_password', $dbPassword);

        #------------------------------
        # E-mail notifications
        #------------------------------
        if ($etlConfig['email_errors'] === "1" || $etlConfig['email_errors'] === "true") {
            $page->checkField('email_errors');
        }
        if ($etlConfig['email_summary'] === "1" || $etlConfig['email_summary'] === "true") {
            $page->checkField('email_summary');
        }
        $page->fillField('email_subject', $etlConfig['email_subject']);
        $page->fillField('email_to_list', $etlConfig['email_to_list']);

        #------------------------------
        # Save
        #------------------------------
        $page->pressButton('Save');
    }

    public static function copyConfiguration($session, $configName, $copyToConfigName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the config name, and then get the
        # 6th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$configName."']/following-sibling::td[5]");
        $element->click();

        # Handle confirmation dialog
        $page->fillField("copyToConfigName", $copyToConfigName);
        $page->pressButton("Copy config");
    }

    public static function renameConfiguration($session, $configName, $renameNewConfigName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the config name, and then get the
        # 7th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$configName."']/following-sibling::td[6]");
        $element->click();

        # Handle confirmation dialog
        $page->fillField("renameNewConfigName", $renameNewConfigName);
        $page->pressButton("Rename config");
    }


    /**
     * Deletes the specified config from the ETL configs list page
     *
     * @param string $configName the name of the config to delete.
     */
    public static function deleteConfiguration($session, $configName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the config name, and then get the
        # 8th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$configName."']/following-sibling::td[7]");
        $element->click();

        # Handle confirmation dialog
        $page->pressButton("Delete configuration");
    }

    public static function deleteConfigurationIfExists($session, $configName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the config name, and then get the
        # 8th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$configName."']/following-sibling::td[7]");

        if (isset($element)) {
            $element->click();

            # Handle confirmation dialog
            $page->pressButton("Delete configuration");
        }
    }
}

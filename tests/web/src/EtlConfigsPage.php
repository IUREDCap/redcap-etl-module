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
        # 2nd column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$configName."']/following-sibling::td[1]");
        $element->click();
    }


    public static function copyConfiguration($session, $configName, $copyToConfigName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the config name, and then get the
        # 5th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$configName."']/following-sibling::td[4]");
        $element->click();

        # Handle confirmation dialog
        $page->fillField("copyToConfigName", $copyToConfigName);
        $page->pressButton("Copy config");
    }

    public static function renameConfiguration($session, $configName, $renameNewConfigName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the config name, and then get the
        # 6th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$configName."']/following-sibling::td[5]");
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
    public static function deleteConfiguration($session, $configName, $ifExists = false)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the config name, and then get the
        # 7th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$configName."']/following-sibling::td[6]");
        if ($ifExists && !isset($element)) {
            ;
        } else {
            $element->click();

            # Handle confirmation dialog
            $page->pressButton("Delete configuration");
        }
    }

    public static function deleteConfigurationIfExists($session, $configName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the config name, and then get the
        # 7th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$configName."']/following-sibling::td[6]");

        if (isset($element)) {
            $element->click();

            # Handle confirmation dialog
            $page->pressButton("Delete configuration");
        }
    }
}

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
     * Deletes the specified server from the ETL servers list page if it exsits.
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

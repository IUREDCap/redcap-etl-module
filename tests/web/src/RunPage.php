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
 * Class for interacting with the user "Run" page for running an ETL configuration on and ETL server.
 * This class assumes that the test user is logged in and is on the Run page when any method in 
 * the class is called.
 */
class RunPage
{
    /**
     * Runs the specified ETL configuration on the specified ETL server.
     */
    public static function runConfiguration($session, $configName, $etlServer)
    {
        $page = $session->getPage();
        $page->selectFieldOption('configName', $configName);
        $page->selectFieldOption('server', $etlServer);
        $page->pressButton('Run');
    }
}

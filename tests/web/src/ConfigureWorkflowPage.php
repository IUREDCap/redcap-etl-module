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
 * Class for interacting with the user "Configure" page for editing ai workflow configuration.
 */
class ConfigureWorkflowPage
{
    public static function addTaskForTestProject($session)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $testProjectTitle = $testConfig->getUser()['test_project_title'];

        $page = $session->getPage();

        # Need to get the text from the select option that contains the test project title, because
        # the options have the REDCap project ID prepended to them, and the project ID is not known
        # in the code here.
        $element = $page->find('xpath', "//select/option[contains(text(), '{$testProjectTitle}')]");
        $optionText = $element->getText();

        $page->selectFieldOption('newTask', $optionText);
        $page->pressButton("Add Task");
    }

    public static function specifyEtlConfig($session, $taskName, $configName)
    {
        $page = $session->getPage();
                $page = $session->getPage();

        # Find the table row where the first element matches the task name, and then get the
        # 6th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$taskName."']/following-sibling::td[5]");
        $element->click();

        # Handle the ETL Config specification dialog
        $page->selectFieldOption("projectEtlConfigSelect", $configName);
        $page->pressButton("Specify ETL");
    }
}

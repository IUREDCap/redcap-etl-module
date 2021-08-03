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
 * Class for interacting with the user "Configure" page for editing a workflow configuration.
 * All methods assume that the current page is a configure workflow page.
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

    /**
     * @param int $taskNumber the number of the task to rename.
     */
    public static function renameTask($session, $taskNumber, $newTaskName)
    {
        $page = $session->getPage();

        # Check the first column header to see if this table has
        # the sort column (more then 1 task) or not (1 task)
        # so the rename column can be set
        $xpath = "//table[@id='workflowTasks']/thead/tr[1]/th[1]";
        $element = $page->find("xpath", $xpath);
        if ($element->getText() === 'Task Name') {
            $renameColumn = 5;
        } else {
            # There is more than one task, so there is an extra first sorting column
            $renameColumn = 6;
        }

        # Find the table row for the specified task number, and then get the
        # rename element and click it
        $xpath = "//table[@id='workflowTasks']/tbody/tr[{$taskNumber}]/td[{$renameColumn}]";
        $element = $page->find("xpath", $xpath);
        $element->click();

        # Rename the task
        $page->fillField("renameNewTaskName", $newTaskName);
        $page->pressButton("Rename task");
    }

    public static function specifyEtlConfig($session, $taskName, $configName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the task name, and then get the
        # 5th column element to the right and click it
        $element = $page->find("xpath", "//tr/td[text()='".$taskName."']/following-sibling::td[5]");
        $element->click();

        $page = $session->getPage();

        # Handle the ETL Config specification dialog
        $page->selectFieldOption("projectEtlConfigSelect", $configName);
        $page->pressButton("Specify ETL");
    }
}

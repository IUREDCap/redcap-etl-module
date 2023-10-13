<?php
#-------------------------------------------------------
# Copyright (C) 2021 The Trustees of Indiana University
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
        print("HEADER 1 TEXT: " . $element->getText() . "\n");
        print("HEADER 1 HTML: " . $element->getHtml() . "\n");
        if ($element->getText() === 'Task Name') {
            $renameColumn = 5;
        } else {
            # There is more than one task, so there is an extra first sorting column
            $renameColumn = 6;
        }

        print("RENAME COLUMN: " . $renameColumn . "\n");
        sleep(2);

        # Find the table row for the specified task number, and then get the
        # rename element and click it
        $xpath = "//table[@id='workflowTasks']/tbody/tr[{$taskNumber}]/td[{$renameColumn}]/input";
        $element = $page->find("xpath", $xpath);
        print ($element->getHtml() . "\n");
        $element->click();
        print ("Element clicked.\n");
        sleep(2);

        # Rename the task
        $page->fillField("renameNewTaskName", $newTaskName);
        print ("Fields filled.\n");
        sleep(2);

        $page->pressButton("Rename task");
        print ("Button pressed.\n");
    }

    public static function specifyEtlConfig($session, $taskName, $configName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the task name, and then get the
        # 5th column element to the right and click it
        $element = $page->find("xpath", "//tr/td[text()='".$taskName."']/following-sibling::td[5]/input");
        print ("SPECIFY ELEMENT: " . $element->getHtml());
        $element->click();

        $page = $session->getPage();
        sleep(2);

        # Handle the ETL Config specification dialog
        $page->selectFieldOption("projectEtlConfigSelect", $configName);
        sleep(2);
        $page->pressButton("Specify ETL");
    }

    public static function deleteTask($session, $taskName)
    {
        $page = $session->getPage();
        $table = $page->find('css', 'table#workflowTasks');
        $element = $table->find("xpath", "//tr/td[text()='".$taskName."']/following-sibling::td[6]/input");

        if ($element == null) {
            $message = 'Task "' . $taskName .'" not found in workflow for deletion.';
            throw new \Exception($message);
        }

        $element->click();
        sleep(2);
        $page->pressButton("Delete task");
    }

    public static function getTaskNames($session)
    {
        $taskNames = array();

        $page = $session->getPage();
        $taskRows = $page->findAll('css', 'table#workflowTasks tbody tr');

        foreach ($taskRows as $taskRow) {
            $tds = $taskRow->findAll('css', 'td');
            if (count($taskRows) === 1) {
                $taskName = $tds[0]->getText();
            } else {
                $taskName = $tds[1]->getText();
            }
            $taskNames[] = $taskName;
        }
        return $taskNames;
    }

    /**
     * @param string $direction the direction to move the task: 'up' or 'down'.
     */
    public static function moveTask($session, $taskName, $direction)
    {
        $page = $session->getPage();
        $taskRows = $page->findAll('css', 'table#workflowTasks tbody tr');

        if (count($taskRows)  > 1) {
            $taskRow = null;

            # Find the row that contains the task
            foreach ($taskRows as $row) {
                $tds = $row->findAll('css', 'td');
                if ($tds[1]->getText() === $taskName) {
                    $taskRow = $row;
                    break;
                }
            }

            if ($taskRow == null) {
                $message = 'Task "' . $taskName .'" not found in workflow.';
                throw new \Exception($message);
            }

            $fields = $taskRow->findAll('css', 'td');

            $inputs = ($fields[0])->findAll('css', 'input');

            if (strcasecmp($direction, 'up') === 0) {
                ($inputs[1])->click();
            } elseif (strcasecmp($direction, 'down') === 0) {
                ($inputs[0])->click();
            } else {
                throw new \Exception('Unrecognized direction "' . $direction . '" for task move.');
            }
        }
    }
}

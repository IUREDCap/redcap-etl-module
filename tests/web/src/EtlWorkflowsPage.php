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
 * Class for interacting with the user "ETL Workflows" page.
 */
class EtlWorkflowsPage
{
    public static function followWorkflow($session, $workflowName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the workflow name, and then get the
        # 2nd column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$workflowName."']/following-sibling::td[1]");
        $element->click();
    }


    public static function copyWorkflow($session, $workflowName, $copyToWorkflowName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the workflow name, and then get the
        # 5th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$workflowName."']/following-sibling::td[4]");
        $element->click();

        # Handle confirmation dialog
        $page->fillField("copyToWorkflowName", $copyToWorkflowName);
        $page->pressButton("Copy workflow");
    }

    public static function renameWorkflow($session, $workflowName, $renameNewWorkflowName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the workflow name, and then get the
        # 6th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$workflowName."']/following-sibling::td[5]");
        $element->click();

        # Handle confirmation dialog
        $page->fillField("renameNewWorkflowName", $renameNewWorkflowName);
        $page->pressButton("Rename Workflow");
    }


    /**
     * Deletes the specified workflow from the ETL workflows list page
     *
     * @param string $workflowName the name of the workflow to delete.
     */
    public static function deleteWorkflow($session, $workflowName, $ifExists = false)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the workflow name, and then get the
        # 7th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$workflowName."']/following-sibling::td[6]");
        if ($ifExists && !isset($element)) {
            ;
        } else {
            $element->click();

            # Handle confirmation dialog
            $page->pressButton("Delete workflow");
        }
    }

    public static function deleteWorkflowIfExists($session, $workflowName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the workflow name, and then get the
        # 7th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$workflowName."']/following-sibling::td[6]");

        if (isset($element)) {
            $element->click();

            # Handle confirmation dialog
            $page->pressButton("Delete workflow");
        }
    }

    public static function addWorkflow($session, $workflowName)
    {
        $page = $session->getPage();
        $page->fillField('workflowName', $workflowName);
        $page->pressButton("Add");
    }
}

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
 * Class for interacting with the admin "Workflows" page.
 */
class AdminWorkflowsPage
{
    public static function adminWorkflowHasStatus($session, $workflowName, $status)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the workflow name, and then get the
        # 2nd column element and check its value
        $element = $page->find("xpath", "//tr/td[text()='".$workflowName."']/following-sibling::td[1]");
        if (!isset($element)) {
            $message = 'Could not get the status for Workflow "' . $workflowName .'" in the admin interface,'
               . ' because it does not exist.';
            throw new \Exception($message);
        } else {
            $text = $element->getText();
            if ($text !== $status) {
                $message = 'The status for workflow "' . $workflowName .'" in the admin interface is "'
                    . $text .'", which does not match "' . $status .'".'
                    ;
                throw new \Exception($message);
            }
        }
    }

    /**
     * Select configuration of a workflow from the admin page.
     */
    public static function adminConfigureWorkflow($session, $workflowName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the workflow name, and then get the
        # 6th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$workflowName."']/following-sibling::td[3]/a");
        print("ELEMENT OUTER HTML: " . $element->getOuterHtml() . "\n");
        print("ELEMENT HTML: " . $element->getHtml() . "\n");
        if (!isset($element)) {
            $message = 'Workflow "' . $workflow .'" could not be configured in the admin interface,'
               . ' because it does not exist.';
            throw new \Exception($message);
        } else {
            $element->click();
        }
    }

    /**
     * Deletes the specified workflow from Admin Workflows page.
     *
     * @param string $workflowName the name of the workflow to delete.
     */
    public static function adminDeleteWorkflow($session, $workflowName, $ifExists = false)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the workflow name, and then get the
        # 6th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$workflowName."']/following-sibling::td[5]");
        if ($ifExists && !isset($element)) {
            ;
        } else {
            $element->click();

            # Handle confirmation dialog
            $page->pressButton("Delete workflow");
        }
    }

    public static function adminDeleteWorkflowIfExists($session, $workflowName)
    {
        $page = $session->getPage();

        # Find the table row where the first element matches the workflow name, and then get the
        # 6th column element and click it
        $element = $page->find("xpath", "//tr/td[text()='".$workflowName."']/following-sibling::td[5]/input");

        print ($element->getHtml());

        if (isset($element)) {
            $element->click();

            sleep(2);

            # Handle confirmation dialog
            $page->pressButton("Delete workflow");
            print ("DELETED!");
        }
    }
}

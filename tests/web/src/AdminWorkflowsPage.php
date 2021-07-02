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
        $element = $page->find("xpath", "//tr/td[text()='".$workflowName."']/following-sibling::td[5]");

        if (isset($element)) {
            $element->click();

            # Handle confirmation dialog
            $page->pressButton("Delete workflow");
        }
    }
}

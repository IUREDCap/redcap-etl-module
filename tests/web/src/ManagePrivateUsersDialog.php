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
 * Class for interacting with the admin "ETL Server Config" page. Methods assume that
 * the current page is an ETL server configuration page.
 */
class ManagePrivateUsersDialog
{
    public static function deleteTestUser($session)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $username = $testConfig->getUser()['username'];

        sleep(4);

        $page = $session->getPage();

        # Find the table row where the first element matches the username, and then get the
        # 4th column element, which should be the delete button, and press it
        $element = $page->find("xpath", "//tr/td[text()='".$username."']/following-sibling::td[3]/button");

        if ($element !== null) {
            # print "    Element outer HTML: " . $element->getOuterHtml() . "\n";
            $element->press();
        } else {
            print "    Element not set.\n";
        }
    }

    public static function hasTestUser($session)
    {
        $hasTestUser = false;
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $username = $testConfig->getUser()['username'];

        sleep(2);

        $page = $session->getPage();

        $element = $page->find("xpath", "//tr/td[text()='".$username."']");
        if ($element !== null) {
            $hasTestUser = true;
        }

        return $hasTestUser;
    }
}

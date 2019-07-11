<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements SnippetAcceptingContext
{
    private $properties;
    private $timestamp;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->timestamp = date('Y-m-d-H-i-s');
        $this->properties = parse_ini_file(__DIR__.'/../../config.ini');
    }

    /**
     * @BeforeScenario
     */
    public function setUpBeforeScenario()
    {
        $cookieName  = 'code-coverage';
        $cookieValue = 'web-test';
        $this->getSession()->setCookie($cookieName, $cookieValue);
        echo "Cookie '{$cookieName}' set to '{$cookieValue}'\n";

        $this->setMinkParameter('base_url', $this->properties['base_url']);
        echo "Base URL set to: ".$this->properties['base_url'];
    }

    /**
     * @Given I wait
     */
    public function iWait()
    {
        $this->getSession()->wait(10000);
    }

    /**
     * @When /^I print window names$/
     */
    public function iPrintWindowNames()
    {
        $windowName = $this->getSession()->getWindowName();
        $windowNames = $this->getSession()->getWindowNames();
        print "Current window: {$windowName} [".array_search($windowName, $windowNames)."]\n";
        print_r($windowNames);
    }

    /**
     * @When /^print link "([^"]*)"$/
     */
    public function printLink($linkId)
    {
        $session = $this->getSession();

        $page = $session->getPage();
        $link = $page->findLink($linkId);
        print "\n{$linkId}\n";
        print_r($link);
    }

    /**
     * @When /^I go to new window in (\d+) seconds$/
     */
    public function iGoToNewWindow($seconds)
    {
        sleep($seconds);  // Need time for new window to open
        $windowNames = $this->getSession()->getWindowNames();
        $numWindows  = count($windowNames);

        $currentWindowName  = $this->getSession()->getWindowName();
        $currentWindowIndex = array_search($currentWindowName, $windowNames);

        if (isset($currentWindowIndex) && $numWindows > $currentWindowIndex + 1) {
            $this->getSession()->switchToWindow($windowNames[$currentWindowIndex + 1]);
            #$this->getSession()->reset();
        }
    }

    /**
     * @When /^I sleep for (\d+) seconds$/
     */
    public function iSleepForSeconds($seconds)
    {
        sleep($seconds);  // Need time for new window to open
    }

    /**
     * @When /^I go to old window$/
     */
    public function iGoToOldWindow()
    {
        $windowNames = $this->getSession()->getWindowNames();

        $currentWindowName  = $this->getSession()->getWindowName();
        $currentWindowIndex = array_search($currentWindowName, $windowNames);

        if (isset($currentWindowIndex) && $currentWindowIndex > 0) {
            $this->getSession()->switchToWindow($windowNames[$currentWindowIndex - 1]);
            $this->getSession()->restart();
        }
    }


    /**
     * @Given I am logged in as user
     */
    public function iAmLoggedInAsUser()
    {
        $session = $this->getSession();
        Util::loginAsUser($session);
    }

    /**
     * @When /^I log in as admin$/
     */
    public function iLogInAsAdmin()
    {
        $session = $this->getSession();
        Util::loginAsAdmin($session);
    }

    /**
     * @When /^I access the admin interface$/
     */
    public function iAccessTheAdminInterface()
    {
        $session = $this->getSession();
        Util::accessAdminInterface($session);
    }
    /**
     * @When /^I select the test project$/
     */
    public function iSelectTheTestProject()
    {
        $session = $this->getSession();
        Util::selectTestProject($session);
    }

    /**
     * @When /^I select user from "([^"]*)"$/
     */
    public function iSelectUserFromSelect($select)
    {
        $session = $this->getSession();
        Util::selectUserFromSelect($session, $select);
    }

    /**
     * @When /^I delete server "([^"]*)"$/
     */
    public function iDeleteServer($serverName)
    {
        $session = $this->getSession();
        Util::deleteServer($session, $serverName);
    }

    /**
     * @When /^I check mailinator for "([^"]*)"$/
     */
    public function iCheckMailinatorFor($emailPrefix)
    {
        $session = $this->getSession();
        Util::mailinator($session, $emailPrefix);
    }

}

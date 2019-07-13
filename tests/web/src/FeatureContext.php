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
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements SnippetAcceptingContext
{
    const CONFIG_FILE = __DIR__.'/../config.ini';

    private $testConfig;
    private $timestamp;
    private $baseUrl;

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
        $this->testConfig = new TestConfig(self::CONFIG_FILE);
        $this->baseUrl = $this->testConfig->getRedCap()['base_url'];
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

        $this->setMinkParameter('base_url', $this->baseUrl);
        echo "Base URL set to: ".$this->baseUrl;
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
     * @When /^I configure configuration "([^"]*)"$/
     */
    public function iConfigureConfiguration($configName)
    {
        $session = $this->getSession();
        EtlConfigsPage::configureConfiguration($session, $configName);
    }

    /**
     * @When /^I copy configuration "([^"]*)" to "([^"]*)"$/
     */
    public function iCopyConfiguration($configName, $copyToConfigName)
    {
        $session = $this->getSession();
        EtlConfigsPage::copyConfiguration($session, $configName, $copyToConfigName);
    }

    /**
     * @When /^I rename configuration "([^"]*)" to "([^"]*)"$/
     */
    public function iRenameConfiguration($configName, $newConfigName)
    {
        $session = $this->getSession();
        EtlConfigsPage::renameConfiguration($session, $configName, $newConfigName);
    }

    /**
     * @When /^I delete configuration "([^"]*)"$/
     */
    public function iDeleteConfiguration($configName)
    {
        $session = $this->getSession();
        EtlConfigsPage::deleteConfiguration($session, $configName);
    }


    /**
     * @When /^I follow server "([^"]*)"$/
     */
    public function iFollowServer($serverName)
    {
        $session = $this->getSession();
        EtlServersPage::followServer($session, $serverName);
    }

    /**
     * @When /^I configure server "([^"]*)"$/
     */
    public function iConfigureServer($serverName)
    {
        $session = $this->getSession();
        EtlServersPage::configureServer($session, $serverName);
    }

    /**
     * @When /^I copy server "([^"]*)" to "([^"]*)"$/
     */
    public function iCopyServer($serverName, $copyToServerName)
    {
        $session = $this->getSession();
        EtlServersPage::copyServer($session, $serverName, $copyToServerName);
    }

    /**
     * @When /^I rename server "([^"]*)" to "([^"]*)"$/
     */
    public function iRenameServer($serverName, $newServerName)
    {
        $session = $this->getSession();
        EtlServersPage::renameServer($session, $serverName, $newServerName);
    }

    /**
     * @When /^I delete server "([^"]*)"$/
     */
    public function iDeleteServer($serverName)
    {
        $session = $this->getSession();
        EtlServersPage::deleteServer($session, $serverName);
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

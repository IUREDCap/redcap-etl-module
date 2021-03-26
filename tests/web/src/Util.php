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

use WebDriver\Exception\NoAlertOpenError;

/**
 * Utility class that has helpful methods.
 */
class Util
{
    /**
     * Gets a web browser sessions. This can be useful for interacting with
     * a web browser outside of the context of a scenario.
     */
    public static function getSession()
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $baseUrl = $testConfig->getRedCap()['base_url'];

        $driver = new \DMore\ChromeDriver\ChromeDriver('http://localhost:9222', null, $baseUrl);
        $session = new \Behat\Mink\Session($driver);
        $session->start();

        return $session;
    }

    /**
     * Logs in to REDCap as the test user.
     */
    public static function loginAsUser($session)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $baseUrl  = $testConfig->getRedCap()['base_url'];
        $username = $testConfig->getUser()['username'];
        $password = $testConfig->getUser()['password'];

        $session->visit($baseUrl);

        $page = $session->getPage();

        # Search for text "Logged in as user {$username}"

        $page->fillField('username', $username);
        $page->fillField('password', $password);
        $page->pressButton('login_btn');
    }
    
    public static function logInAsUserAndAccessRedCapEtlForTestProject($session)
    {
        self::logInAsUser($session);
        $page = $session->getPage();
        $page->clickLink('My Projects');
        self::selectTestProject($session);
        $page->clickLink('REDCap-ETL');
    }

    /**
     * Logs in to REDCap as the admin.
     */
    public static function loginAsAdmin($session)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $baseUrl  = $testConfig->getRedCap()['base_url'];
        $username = $testConfig->getAdmin()['username'];
        $password = $testConfig->getAdmin()['password'];

        $session->visit($baseUrl);

        $page = $session->getPage();

        $page->fillField('username', $username);
        $page->fillField('password', $password);
        $page->pressButton('login_btn');
    }

    /**
     * Logs out of REDCap.
     */
    public static function logOut($session)
    {
        $page = $session->getPage();
        $page->clickLink('Log out');
    }


    /**
     * Logs in to REDCap as the admin and accesses the REDCap-ETL admin interface.
     */
    public static function logInAsAdminAndAccessRedCapEtl($session)
    {
        self::loginAsAdmin($session);

        $page = $session->getPage();
        $page->clickLink('Control Center');
        $page->clickLink('REDCap-ETL');
    }

    /**
     * Checks for module page tabs.
     *
     * @param array $tabs array of strings that are tab names
     * @param boolean $shouldFind if true, checks that tabs exist, if false
     *     checks that tabs do not exist.
     */
    public static function checkTabs($session, $tabs, $shouldFind = true)
    {
        $page = $session->getPage();
        $element = $page->find('css', '#sub-nav');

        foreach ($tabs as $tab) {
            $link = $element->findLink($tab);
            if (empty($link)) {
                if ($shouldFind) {
                    throw new \Exception("Tab {$tab} not found.");
                }
            } else {
                if (!$shouldFind) {
                    throw new \Exception("Tab {$tab} found.");
                }                
            }
        }
    }
    
    public static function isSelectedTab($session, $tab)
    {
        $page = $session->getPage();
        $element = $page->find('css', '#sub-nav');

        $link = $element->findLink($tab);
        if (empty($link)) {
            throw new \Exception("Tab {$tab} not found.");
        }
        
        if (!$link->getParent()->hasClass('active')) {
            throw new \Exception("Tab {$tab} is not selected.");
        }
    }
    
    
    /**
     * Checks that the specified table headers exist on the current page.
     *
     * @param array $headers array of strings that are table headers.
     */
    public static function checkTableHeaders($session, $headers)
    {
        $page = $session->getPage();
        $elements = $page->findAll('css', 'th');
        
        $headersMap = array();
        if (!empty($elements)) {
            foreach ($elements as $element) {
                $headersMap[$element->getText()] = 1;
            }
        }

        foreach ($headers as $header) {
            if (!array_key_exists($header, $headersMap)) {
                throw new \Exception("Table header \"{$header}\" not found.");
            }
        }
    }
            
    public static function accessTestProjectRedCapEtl($session)
    {
        self::loginAsUser($session);
        self::selectTestProject($session);
        $page = $session->getPage();
        $page->clickLink('REDCap-ETL');
    }

    public static function selectTestProject($session)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $testProjectTitle = $testConfig->getUser()['test_project_title'];

        $page = $session->getPage();

        $page->clickLink($testProjectTitle);
    }

    public static function deleteEtlConfigurationIfExists($session, $configName)
    {
        self::accessTestProjectRedCapEtl($session);
        $page = $session->getPage();
        $page->clickLink('ETL Configurations');
        $ifExists = true;
        EtlConfigsPage::deleteConfiguration($session, $configName, $ifExists);
        self::logOut($session);
    }

    public static function createEtlConfigurationIfNotExists($session, $configName)
    {
        self::accessTestProjectRedCapEtl($session);

        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);

        self::loginAsUser($session);
        $page = $session->getPage();
        $projectLink = $page->find('named', array('link', $testProjectTitle));
        if (isset($projectLink)) {
            print "Project link found\n";
        } else {
            print "Project link NOT found\n";
        }
        // to be completed...
    }


    public static function createProject($session, $projectTitle)
    {
        self::loginAsUser($session);
        $page = $session->getPage();

        $page->clickLink('New Project');
    }

    public static function selectUserFromSelect($session, $select)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $baseUrl  = $testConfig->getRedCap()['base_url'];
        $username = $testConfig->getUser()['username'];

        $page = $session->getPage();
        $page->selectFieldOption($select, $username);
    }


    public static function mailinator($session, $emailPrefix)
    {
        $session->visit("https://www.mailinator.com");
        $page = $session->getPage();
        $page->fillField('inboxfield', $emailPrefix);
        $page->pressButton('Go!');
    }

    public static function runCron($session)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $baseUrl  = $testConfig->getRedCap()['base_url'];
    }

    public static function findTextFollowedByText($session, $textA, $textB)
    {
        $content = $session->getPage()->getContent();

        // Get rid of stuff between script tags
        $content = self::removeContentBetweenTags('script', $content);

        // ...and stuff between style tags
        $content = self::removeContentBetweenTags('style', $content);

        $content = preg_replace('/<[^>]+>/', ' ',$content);

        // Replace line breaks and tabs with a single space character
        $content = preg_replace('/[\n\r\t]+/', ' ',$content);

        $content = preg_replace('/ {2,}/', ' ',$content);

        if (strpos($content,$textA) === false) {
            throw new \Exception(sprintf('"%s" was not found in the page', $textA));
        }

        if ($textB) {
            $seeking = $textA . ' ' . $textB;
            if (strpos($content,$textA . ' ' . $textB) === false) {
                throw new \Exception(sprintf('"%s" was not found in the page', $seeking));
            }
        }
    }

   public static function findSomethingForTheUser($session, $username, $see, $item)
    {
        $page = $session->getPage();
        $throwError = false;

        if ($see === "should") {
            $seeError = "was not";
            if ($item === 'link') {
                if (!$page->findLink($username)) {
                    $throwError = true;
                }
            } elseif ($item === 'remove user checkbox') {
                $checkboxName = 'removeUserCheckbox['.$username.']';
                if (!$page->find('named', array('id_or_name', $checkboxName))) {
                    $throwError = true;
                }
            } else {
                $throwError = true;
            }

        } else if ($see === "should not") {
            $seeError = "was";
            if ($item === 'link') {
                if ($page->findLink($username)) {
                    $throwError = true;
                }
            } elseif ($item === 'remove user checkbox') {
                $checkboxName = 'removeUserCheckbox['.$username.']';
                if ($page->find('named', array('id_or_name', $checkboxName))) {
                    $throwError = true;
                }
            } else {
                $throwError = true;
            }
        } else {
                $throwError = true;
        }

        if ($throwError) {
            throw new \Exception(sprintf('The "%s" "%s" found on the page for "%s"', $item, $seeError, $username));
        }
    }

    public static function chooseAccessLevel($session, $newLevel, $privateLevelButton)
    {
        $page = $session->getPage();
        $accessLevel = $page->findById("accessLevelId");
        $privateUsersExist = false;
   
        # If the current access level is private then check to see if any users were
        # assigned. (If there are users assigned, the word "Remove" will appear next
        # to their usernames.)
        if ($accessLevel->getValue() === 'private' && $newLevel !== 'private') {
            $usersRow = $page->findById("usersRow")->getText();
            $privateUsersExist = strpos($usersRow, 'Remove');
        }

        if ($privateUsersExist) {
            $page = $session->getPage();

            #Change the access level
            $accessLevel->selectOption($newLevel);

            # Handle confirmation dialog
            $page->pressButton($privateLevelButton);

        } else {
            $accessLevel->selectOption($newLevel);
            sleep(2);
        }
    }

    /**
     * @param string $tagName - The name of the tag, eg. 'script', 'style'
     * @param string $content
     *
     * @return string
     */
    private function removeContentBetweenTags($tagName,$content)
    {
        $parts = explode('<' . $tagName, $content);

        $keepers = [];

        // We always want to keep the first part
        $keepers[] = $parts[0];

        foreach ($parts as $part) {
            $subparts = explode('</' . $tagName . '>', $part);
            if (count($subparts) > 1) {
                $keepers[] = $subparts[1];
            }
        }

        return implode('', $keepers);
    }

    public static function selectFormsProject($session)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $testProjectTitle = $testConfig->getUser()['forms_project_title'];

        $page = $session->getPage();

        $page->clickLink($testProjectTitle);
    }

    public static function findThisText($session, $see, $textA)
    {
        $content = $session->getPage()->getContent();

        // Get rid of stuff between script tags
        $content = self::removeContentBetweenTags('script', $content);

        // ...and stuff between style tags
        $content = self::removeContentBetweenTags('style', $content);

        $content = preg_replace('/<[^>]+>/', ' ',$content);

        // Replace line breaks and tabs with a single space character
        $content = preg_replace('/[\n\r\t]+/', ' ',$content);

        $content = preg_replace('/ {2,}/', ' ',$content);

        $seeError = "was not";
        if ($see === "should not") {
            $seeError = "was";
        }

        if ($see === 'should') {
            if (strpos($content,$textA) === false) {
               throw new \Exception(sprintf('"%s" was not found in the page', $textA));
            }
        } elseif ($see === 'should not') {
            if (strpos($content,$textA) === true) {
               throw new \Exception(sprintf('"%s" was found in the page', $textA));
            }
        } else {
            throw new \Exception(sprintf('"%s" option is unrecognized', $see));
        }
    }


    /**
     * Follow a link that goes to a new window.
     *
     * @param string $link the link that goes to a new window.
     *
     * @return string the name of the new window
     */
    public function goToNewWindow($session, $link)
    {
        # Save the current window names
        $windowNames = $session->getWindowNames();

        # Follow the link (which should create a new window name)
        $page = $session->getPage();
        $page->clickLink($link);
        sleep(2); // Give some time for new window to open

        # See what window name was added (this should be the new window)
        $newWindowNames = $session->getWindowNames();
        $windowNamesDiff = array_diff($newWindowNames, $windowNames);
        $newWindowName = array_shift($windowNamesDiff); // There should be only 1 element in the diff

        $session->switchToWindow($newWindowName);

        return $newWindowName;
    }
}

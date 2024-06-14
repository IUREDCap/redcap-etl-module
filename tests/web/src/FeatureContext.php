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

    private static $featureFileName;

    private $previousWindowName;

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

    /** @BeforeFeature */
    public static function setupFeature($scope)
    {
        $feature = $scope->getFeature();
        $filePath = $feature->getFile();
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        self::$featureFileName = $fileName;

        $session = Util::getSession();
        Util::logInAsAdminAndAccessRedCapEtl($session);

        #-------------------------------------------
        # Admin config initialization
        #-------------------------------------------
        print "Initializing admin config\n";
        $page = $session->getPage();
        $page->clickLink('Config');
        # $page->checkField('allowOnDemand');
        # $page->checkField('allowCron');
        for ($day = 0; $day <= 6; $day++) {
            for ($hour = 0; $hour <= 23; $hour++) {
                $page->checkField("allowedCronTimes[{$day}][{$hour}]");
            }
        }
        $page->pressButton('Save');

        #----------------------------------------
        # ETL server configuration
        #----------------------------------------
        print "Initializing etl servers\n";
        #$page->clickLink('ETL Servers');
        $page->clickLink('Servers');
        EtlServersPage::followServer($session, '(embedded server)');
        $page = $session->getPage();
        $page->checkField('isActive');
        $page->selectFieldOption('accessLevel', 'public');

        Util::logout($session);
    }

    /** @AfterFeature */
    public static function teardownFeature($scope)
    {
    }


    /**
     * @BeforeScenario
     */
    public function setUpBeforeScenario()
    {
        echo "Feature file name :'".(self::$featureFileName)."'\n";

        $cookieName  = 'code-coverage-id';
        $cookieValue = 'web-test';

        $session = $this->getSession();
        #print_r($session);

        $this->setMinkParameter('base_url', $this->baseUrl);
        echo "Base URL set to: " . $this->baseUrl . "\n";

        $this->getSession()->visit($this->baseUrl);
        $this->getSession()->setCookie($cookieName, $cookieValue);
        echo "Cookie '{$cookieName}' set to '{$cookieValue}'\n";
    }

    /**
     * @AfterScenario
     */
    public function afterScenario($event)
    {
        $session = $this->getSession();

        $scenario = $event->getScenario();
        $tags = $scenario->getTags();

        if ($scenario->hasTag('modified-help-for-batch-size')) {
            $session->reset();
            Util::logInAsAdminAndAccessRedCapEtl($session);
            $page = $session->getPage();
            $page->clickLink("Help Edit");
            $page->clickLink("Batch Size");
            $page->fillField("customHelp", "");
            $page->selectFieldOption("helpSetting", "Use default text");
            $page->pressButton("Save");
        }

        // $session->reset();
        $session->restart();
    }


    /**
     * @Given /^I wait$/
     */
    public function iWait()
    {
        $this->getSession()->wait(10000);
    }


    /**
     * @Given /^ETL configuration "([^"]*)" does not exist$/
     */
    public function etlConfigurationDoesNotExist($configName)
    {
        $session = $this->getSession();
        Util::deleteEtlConfigurationIfExists($session, $configName);
    }

    /**
     * @Given /^I am logged in as user$/
     */
    public function iAmLoggedInAsUser()
    {
        $session = $this->getSession();
        Util::loginAsUser($session);
    }

    /**
     * @Then /^I go to previous window$/
     */
    public function iGoToPreviousWindow()
    {
        if (!empty($this->previousWindowName)) {
            print "*** SWITCH TO PREVIOUS WINDOW {$this->previousWindowName}\n";
            $this->getSession()->switchToWindow($this->previousWindowName);
            $this->previousWindowName = '';
        }
    }

    /**
     * @Then /^Print element "([^"]*)" text$/
     */
    public function printElementText($css)
    {
        $session = $this->getSession();
        $page = $session->getPage();
        $element = $page->find('css', $css);
        $text = $element->getText();
        print "{$text}\n";
    }

    /**
     * @Then /^Print element "([^"]*)" value$/
     */
    public function printElementValue($css)
    {
        $session = $this->getSession();
        $page = $session->getPage();
        $element = $page->find('css', $css);
        $value = $element->getValue();
        print "{$value}\n";
    }

    /**
     * @Then /^Print element "([^"]*)" html$/
     */
    public function printElementHtml($css)
    {
        $session = $this->getSession();
        $page = $session->getPage();
        $element = $page->find('css', $css);
        $html = $element->getHtml();
        print "{$html}\n";
    }

    /**
     * @Then /^Field "([^"]*)" should contain value "([^"]*)"$/
     */
    public function fieldShouldContainValue($fieldLocator, $value)
    {
        $session = $this->getSession();
        $page = $session->getPage();
        $element = $page->findField($fieldLocator);
        if (!isset($element)) {
            throw new \Exception("Field \"{$css}\" not found.");
        }

        $fieldValue = $element->getValue();

        if (strpos($fieldValue, $value) === false) {
            throw new \Exception("Field \"{$css}\" does not contain value \"{$value}\".");
        }
    }

    /**
    /**
     * @Then /^Print select "([^"]*)" text$/
     */
    public function printSelectText($selectCss)
    {
        $session = $this->getSession();
        $page = $session->getPage();
        $select = $page->find('css', $selectCss);
        if (!empty($select)) {
            #$html = $select->getHtml();
            #print "\n{$html}\n\n";
            $option = $page->find('css', $selectCss." option:selected");
            #$option = $select->find('css', "option:selected");
            #$option = $select->find('xpath', "//option[@selected]");
            if (!empty($option)) {
                $text = $option->getText();
                print "{$text}\n";
            } else {
                print "Selected option not found\n";
            }
        } else {
            print 'Select "'.$selectCss.'" not found'."\n";
        }
    }

    /**
     * @Then /^I should see tabs? ("([^"]*)"(,(\s)*"([^"]*)")*)$/
     */
    public function iShouldSeeTabs($tabs)
    {
        $tabs = explode(',', $tabs);
        for ($i = 0; $i < count($tabs); $i++) {
            # trim standard character plus quotes
            $tabs[$i] = trim($tabs[$i], " \t\n\r\0\x0B\"");
        }

        $session = $this->getSession();
        Util::checkTabs($session, $tabs);
    }
    
    
    /**
     * @Then /^tab ("([^"]*)") should be selected$/
     */
    public function tabShouldBeSelected($tab)
    {
        $tab = trim($tab, " \t\n\r\0\x0B\"");

        $session = $this->getSession();
        Util::isSelectedTab($session, $tab);
    }

    /**
     * @Then /^I should not see tabs? ("([^"]*)"(,(\s)*"([^"]*)")*)$/
     */
    public function iShouldNotSeeTabs($tabs)
    {
        $tabs = explode(',', $tabs);
        for ($i = 0; $i < count($tabs); $i++) {
            # trim standard character plus quotes
            $tabs[$i] = trim($tabs[$i], " \t\n\r\0\x0B\"");
        }

        $session = $this->getSession();
        $shouldFind = false;
        Util::checkTabs($session, $tabs, $shouldFind);
    }


    /**
     * @Then /^I should see table headers ("([^"]*)"(,(\s)*"([^"]*)")*)$/
     */
    public function iShouldSeeTableHeaders($headers)
    {
        $headers = explode(',', $headers);
        for ($i = 0; $i < count($headers); $i++) {
            # trim standard character plus quotes
            $headers[$i] = trim($headers[$i], " \t\n\r\0\x0B\"");
        }

        $session = $this->getSession();
        
        Util::checkTableHeaders($session, $headers);
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
     * @When /^I click on element containing "([^"]*)"$/
     */
    public function iClickOnElementContaining($text)
    {
        $session = $this->getSession();

        $page = $session->getPage();
        $element = $page->find('xpath', "//*[contains(text(), '{$text}')]");
        $element->click();
    }

    /**
     * @When /^I search for user$/
     */
    public function iSearchForUser()
    {
        $user = $this->testConfig->getUser();

        $session = $this->getSession();
        $page = $session->getPage();

        $page->fillField('user-search', $user['username']);

        sleep(4);

        $element = $page->find('xpath', "//*[contains(text(), '".$user['email']."')]");
        $element->click();
    }

    /**
     * @When /^I delete private server access for the test user$/
     */
    public function iDeletePrivateServerAccessForTheTestUser()
    {
        $session = $this->getSession();
        ManagePrivateUsersDialog::deleteTestUser($session);
    }

    /**
     * @Then /^the test user should have private server access$/
     *
     * Assumes that the dialog for managing private server user access is open.
     */
    public function theTestUserShouldHavePrivateServerAccess()
    {
        $session = $this->getSession();
        if (!ManagePrivateUsersDialog::hasTestUser($session)) {
            throw new \Exception("The test user does NOT have access to the private server.");
        }
    }

    /**
     * @Then /^the test user should not have private server access$/
     *
     * Assumes that the dialog for managing private server user access is open.
     */
    public function theTestUserDoesShouldNotHavePrivateServerAccess()
    {
        $session = $this->getSession();
        if (ManagePrivateUsersDialog::hasTestUser($session)) {
            throw new \Exception("The test user has access to the private server.");
        }
    }

    /**
     * @When /^I wait for (\d+) seconds$/
     */
    public function iWaitForSeconds($seconds)
    {
        sleep($seconds);
    }

    /**
     * @When /^I log in as user$/
     */
    public function iLogInAsUser()
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
     * @When /^I log out$/
     */
    public function iLogOut()
    {
        $session = $this->getSession();
        Util::logOut($session);
    }

    /**
     * @When /^I access the admin interface$/
     */
    public function iAccessTheAdminInterface()
    {
        $session = $this->getSession();
        Util::logInAsAdminAndAccessRedCapEtl($session);
    }

    /**
     * @When /^I log in as admin and access REDCap-ETL$/
     */
    public function iLogInAsAdminAndAccessRedCapEtl()
    {
        $session = $this->getSession();
        Util::logInAsAdminAndAccessRedCapEtl($session);
    }

    /**
     * @When /^I log in as user and access REDCap-ETL for test project$/
     */
    public function iLogInAsUserAndAccessRedCapEtlForTestProject()
    {
        $session = $this->getSession();
        Util::logInAsUserAndAccessRedCapEtlForTestProject($session);
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
     * @When /^I select the test project in new window$/
     */
    public function iSelectTheTestProjectInNewWindow()
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $testProjectTitle = $testConfig->getUser()['test_project_title'];

        $session = $this->getSession();

        $this->previousWindowName = $session->getWindowName();
        Util::goToNewWindow($session, $testProjectTitle);
    }

    /**
     * @When /^I follow "([^"]*)" to new window$/
     */
    public function iFollowLinkToNewWindow($link)
    {
        $session = $this->getSession();
        $this->previousWindowName = $session->getWindowName();
        Util::goToNewWindow($session, $link);
    }

    /**
     * @When /^I select user from "([^"]*)"$/
     */
    public function iSelectUserFromSelect($select)
    {
        $session = $this->getSession();
        Util::selectUserFromSelect($session, $select);
    }


    /* Configuration --------------------------------------------------------------- */

    /**
     * @When /^I follow configuration "([^"]*)"$/
     */
    public function iFollowConfiguration($configName)
    {
        $session = $this->getSession();
        EtlConfigsPage::followConfiguration($session, $configName);
    }

    /**
     * @When /^I configure configuration "([^"]*)"$/
     */
    public function iConfigureConfiguration($configName)
    {
        $session = $this->getSession();
        ConfigurePage::configureConfiguration($session, $configName);
    }

    /**
     * @When /^I configure configuration "([^"]*)" with email subject "([^"])"$/
     */
    public function iConfigureConfigurationWithEmailSubject($configName, $emailSubject)
    {
        $session = $this->getSession();
        ConfigurePage::configureConfiguration($session, $configName, $emailSubject);
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
     * @When /^I delete configuration "([^"]*)" if it exists$/
     */
    public function iDeleteConfigurationIfExists($configName)
    {
        $session = $this->getSession();
        EtlConfigsPage::deleteConfigurationIfExists($session, $configName);
    }

    /**
     * @When /^I fill in "([^"]*)" with the user e-mail$/
     *
     * Fills in the specified text field with the user e-mail from the test configuration file.
     */
    public function iFileInWithTheUserEmail($field)
    {
        $session = $this->getSession();
        $page = $session->getPage();
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $userEmail = $testConfig->getUser()['email'];
        $page->fillField($field, $userEmail);
    }


    /* Workflow Configurations --------------------------------------------------------------- */

    /* Workflow Configurations --------------------------------------------------------------- */

    /**
     * @When /^I add task for workflow$/
     */
    public function iAddTaskForWorkflow()
    {
        $session = $this->getSession();
        ConfigureWorkflowPage::addTaskForTestProject($session);
    }

    /**
     * @When /^I move task "([^"]*)" up$/
     */
    public function iMoveTaskUp($taskName)
    {
        $session = $this->getSession();
        ConfigureWorkflowPage::moveTask($session, $taskName, 'up');
    }

    /**
     * @When /^I move task "([^"]*)" down$/
     */
    public function iMoveTaskDown($taskName)
    {
        $session = $this->getSession();
        ConfigureWorkflowPage::moveTask($session, $taskName, 'down');
    }


    /**
     * @When /^I rename task (\d+) to "([^"]*)"$/
     *
     * @param int $taskNumber the task number using one-based indexing, i.e.,
     *     the first task is task number 1.
     */
    public function iRenameTask($taskNumber, $newTaskName)
    {
        $session = $this->getSession();
        ConfigureWorkflowPage::renameTask($session, $taskNumber, $newTaskName);
    }

    /**
     * @When /^I specify etl-configuration "([^"]*)" for task "([^"]*)"$/
     */
    public function iSpecifyConfigForTask($configName, $taskName)
    {
        $session = $this->getSession();
        ConfigureWorkflowPage::specifyEtlConfig($session, $taskName, $configName);
    }

    /**
     * @When /^I delete task "([^"]*)"$/
     */
    public function iDeleteTask($taskName)
    {
        $session = $this->getSession();
        ConfigureWorkflowPage::deleteTask($session, $taskName);
    }



    /* Workflows --------------------------------------------------------------- */

    /**
     * @When /^I follow workflow "([^"]*)"$/
     */
    public function iFollowWorkflow($workflowName)
    {
        $session = $this->getSession();
        EtlWorkflowsPage::followWorkflow($session, $workflowName);
    }

    /**
     * @When /^I copy workflow "([^"]*)" to "([^"]*)"$/
     */
    public function iCopyWorkflow($workflowName, $copyToWorkflowName)
    {
        $session = $this->getSession();
        EtlWorkflowsPage::copyWorkflow($session, $workflowName, $copyToWorkflowName);
    }

    /**
     * @When /^I rename workflow "([^"]*)" to "([^"]*)"$/
     */
    public function iRenameWorkflow($workflowName, $newWorkflowName)
    {
        $session = $this->getSession();
        EtlWorkflowsPage::renameWorkflow($session, $workflowName, $newWorkflowName);
    }

    /**
     * @When /^I delete workflow "([^"]*)"$/
     */
    public function iDeleteWorkflow($workflowName)
    {
        $session = $this->getSession();
        EtlWorkflowsPage::deleteWorkflow($session, $workflowName);
    }

    /**
     * @When /^I delete workflow "([^"]*)" if it exists$/
     */
    public function iDeleteWorkflowIfExists($workflowName)
    {
        $session = $this->getSession();
        EtlWorkflowsPage::deleteWorkflowIfExists($session, $workflowName);
    }


    /* ----------------------------------------------------------------------------- */

    /**
     * @When /^I admin delete workflow "([^"]*)"$/
     */
    public function iAdminDeleteWorkflow($workflowName)
    {
        $session = $this->getSession();
        AdminWorkflowsPage::adminDeleteWorkflow($session, $workflowName);
    }

    /**
     * @When /^I admin delete workflow "([^"]*)" if it exists$/
     */
    public function iAdminDeleteWorkflowIfItExists($workflowName)
    {
        $session = $this->getSession();
        AdminWorkflowsPage::adminDeleteWorkflowIfExists($session, $workflowName);
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
        EtlServerConfigPage::configureServer($session, $serverName);
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
     * @When /^I schedule for next hour$/
     */
    public function iScheduleForNextHour()
    {
        $session = $this->getSession();
        SchedulePage::scheduleForNextHour($session);
    }

    /**
     * @When /^I check test and forms projects access$/
     */
    public function iCheckTestAndFormsProjectsAccess()
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $userConfig = $testConfig->getUser();
        $testProjectTitle  = $userConfig['test_project_title'];
        $formsProjectTitle = $userConfig['forms_project_title'];

        $session = $this->getSession();
        $page = $session->getPage();

        $element = $page->find("xpath", "//tr[contains(td[3],'".$testProjectTitle."')]/td[1]/input[@type='checkbox']");
        $element->click();

        $element = $page->find("xpath", "//tr[contains(td[3],'".$formsProjectTitle."')]/td[1]/input[@type='checkbox']");
        $element->click();
    }

    /**
     * @Then /^Workflow "([^"]*)" should have status "([^"]*)" in admin workflows$/
     *
     * Assumes you are on the admin Workflows page.
     */
    public function adminWorkflowShouldHaveStatus($workflowName, $status)
    {
        $session = $this->getSession();
        AdminWorkflowsPage::adminWorkflowHasStatus($session, $workflowName, $status);
    }

    /**
     * @When /^I configure workflow "([^"]*)" in admin workflows$/
     *
     * Assumes you are on the admin Workflows page.
     */
    public function adminXonfigureWorkflow($workflowName)
    {
        $session = $this->getSession();
        AdminWorkflowsPage::adminConfigureWorkflow($session, $workflowName);
    }

    /**
     * @When /^I check mailinator for "([^"]*)"$/
     */
    public function iCheckMailinatorFor($emailPrefix)
    {
        $session = $this->getSession();
        Util::mailinator($session, $emailPrefix);
    }

    /**
     * @When /^I run the cron process$/
     */
    public function iRunTheCronProcess()
    {

        # WORK IN PROGRESS
        # Need to do 2 things: reset the last cron runtime, so the process will run
        # Access the cron script (can access through http)
        $session = $this->getSession();
        Util::mailinator($session, $emailPrefix);
    }

    /**
     * @Then I should see :textA followed by :textB
     */
    public function iShouldSeeFollowedBy($textA, $textB)
    {
        $session = $this->getSession();
        Util::findTextFollowedByText($session, $textA, $textB);
    }

    /**
     * @When /^I click on the user$/
     */
    public function iClickOnTheUser()
    {
        $user = $this->testConfig->getUser();
        $username = $user['username'];

        $session = $this->getSession();
        $page = $session->getPage();

        $page->clickLink($username);
    }

    /**
     * @When /^I check the box to remove the user$/
     */
    public function iCheckTheBoxToRemoveTheUser()
    {
        $user = $this->testConfig->getUser();
        $username = $user['username'];

        $session = $this->getSession();
        $page = $session->getPage();

        $checkboxName = 'removeUserCheckbox['.$username.']';

        $page->checkField($checkboxName);
    }

    /**
     * @When I choose :textA as the access level
     */
    public function iChooseAsTheAccessLevel($textA)
    {
        $session = $this->getSession();
        Util::chooseAccessLevel($session, $textA, null);
    }


    /**
     * @When I choose :textA as the access level and click :textB
     */
    public function iChooseAsTheAccessLevelAndClick($textA, $textB)
    {
        $session = $this->getSession();
        Util::chooseAccessLevel($session, $textA, $textB);
    }


    /**
     * @Then I :textA see a/an :textB item for the user
     */
    public function iSeeAnItemForTheUser($textA, $textB)
    {
        $user = $this->testConfig->getUser();
        $username = $user['username'];

        $session = $this->getSession();
        Util::findSomethingForTheUser($session, $username, $textA, $textB);
    }

    /**
     * @When /^I select the forms project$/
     */
    public function iSelectTheFormsProject()
    {
        $session = $this->getSession();
        Util::selectFormsProject($session);
    }

    /**
     * @When I specify the auto-gen :arg1 options for :arg2
     */
    public function iSpecifyTheAutoGenOptionsFor($arg1, $arg2)
    {
        $session = $this->getSession();
        ConfigurePage::configureAutoGen($session, $arg2, $arg1);
    }

    /**
     * @Then I :textA see this text :textB
     */
    public function iSeeThisText($textA, $textB)
    {
        $session = $this->getSession();
        Util::findThisText($session, $textA, $textB);
    }

    /**
     * @Then /^I should see tasks? ("([^"]*)"(,(\s)*"([^"]*)")*)$/
     */
    public function iShouldSeeTasks($tasks)
    {
        $tasks = explode(',', $tasks);
        for ($i = 0; $i < count($tasks); $i++) {
            # trim standard character plus quotes
            $tasks[$i] = trim($tasks[$i], " \t\n\r\0\x0B\"");
        }

        $session = $this->getSession();

        $actualTasks = ConfigureWorkflowPage::getTaskNames($session);
        if ($tasks !== $actualTasks) {
            $message = 'Tasks "' . join(", ", $actualTasks) . '" do not match expected tasks "'
                . join(", ", $tasks) . '".';
            throw new \Exception($message);
        }
    }

    /**
     * @When I confirm the popup [nal WIP: was in the process of trying to get this to work]
     */
    #public function iConfirmThePopup()
    #{
    #    $this->getSession()->getDriver()->getWebDriverSession()->accept_alert();
    #}
    #

    #---------------------------------
    # DATABASE CHECKS
    #---------------------------------

    /**
     * @Then /^database table "([^"]*)" should contain (\d+) rows?$/
     */
    public function databaseTableShouldContainRows($tableName, $numRows)
    {
        $db = new Database();

        $actualNumRows = $db->getNumberOfTableRows($tableName);

        if ($actualNumRows != $numRows) {
            $message = 'Database table "' . $tableName . '" has ' . $actualNumRows
                . ' when it was expected to have ' . $numRows . '.';
            throw new \Exception($message);
        }
    }

    /**
     * @Then a downloaded file should be found
     */
    public function aDownloadedFileShouldBeFound()
    {
        $found = Util::findDownloadedFile();
        if (!$found) {
            $message = 'Downloaded CSV file redcap-etl.zip not found.';
            throw new \Exception($message);
        }
    }

}

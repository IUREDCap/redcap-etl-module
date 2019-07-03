<?php

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
     * @When /^I check mailinator for "([^"]*)"$/
     */
    public function iCheckMailinatorFor($emailPrefix)
    {
        $session = $this->getSession();
        Util::mailinator($session, $emailPrefix);
    }

}

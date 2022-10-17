Feature: ETL configuration creation
In order to use REDCap-ETL
As a non-admin user
I need to be able to create ETL configurations

  Background:
    Given I am on "/"
    And I am logged in as user
    And I follow "My Projects"
    And I select the test project
    And I follow "REDCap-ETL"

  Scenario: Create configuration
    When I fill in "configurationName" with "behat-config-test"
    And I press "Add"
    Then I should see "behat-config-test"
    But I should not see "Error:"




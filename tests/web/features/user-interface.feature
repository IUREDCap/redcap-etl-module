Feature: User-Interface
In order to use REDCap-ETL
As a non-admin user
I need to be able to view the REDCap-ETL external module main page

  Scenario: Login to REDCap
    Given I am on "/"
    And I am logged in as user
    When I select the test project
    And I follow "REDCap-ETL"
    Then I should see "ETL Configurations"
    And I should see "Configure"
    And I should see "Run"
    And I should see "Schedule"
    And I should see "User Manual"

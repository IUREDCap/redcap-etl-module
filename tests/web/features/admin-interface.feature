Feature: Admin-Interface
  In order to execute admin actions
  As an admin
  I need to be able to access the REDCap-ETL Admin Page

  @javascript
  Scenario: Access the admin interface
    Given I am on "/"
    When I log in as admin
    And I follow "Control Center"
    And I follow "REDCap-ETL"
    Then I should see "Info"
    And I should see "Config"
    And I should see "Cron Detail"
    And I should see "Users"
    And I should see "ETL Servers"
    And I should see "Help Edit"

  @javascript
  Scenario: Access the admin config page
    Given I am on "/"
    When I access the admin interface
    And I follow "Config"
    Then I should see "Last ETL cron run time"
    And I should see "Sunday"
    And I should see "Monday"
    And I should see "Tuesday"
    And I should see "Wednesday"
    And I should see "Thursday"
    And I should see "Friday"
    And I should see "Saturday"

  @javascript
  Scenario: Access the admin cron detail page
    Given I am on "/"
    When I access the admin interface
    And I follow "Cron Detail"
    Then I should see "Day"
    And I should see "Time"

  @javascript
  Scenario: Access the admin users page
    Given I am on "/"
    When I access the admin interface
    And I follow "Users"
    Then I should see "List"
    And I should see "Search"

  @javascript
  Scenario: Access the admin user search page
    Given I am on "/"
    When I access the admin interface
    And I follow "Users"
    And I follow "Search"
    Then I should see "User:"

  @javascript
  Scenario: Access the admin ETL servers page
    Given I am on "/"
    When I access the admin interface
    And I follow "ETL Servers"
    Then I should see "List"
    And I should see "Configuration"

  @javascript
  Scenario: Access the admin ETL server config page
    Given I am on "/"
    When I access the admin interface
    And I follow "ETL Servers"
    And I follow "Configuration"
    Then I should see "Server:"

  @javascript
  Scenario: Access the admin help edit page
    Given I am on "/"
    When I access the admin interface
    And I follow "Help Edit"
    Then I should see "List"
    And I should see "Edit"
    And I should see "Topic"
    And I should see "Setting"


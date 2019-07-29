#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: User-Interface
In order to use REDCap-ETL
As a non-admin user
I need to be able to create, copy, rename and delete configurations
  and get help for a REDCap-ETL enabled project

  Background:
    Given I am on "/"
    And I am logged in as user
    When I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"

  Scenario: Create configuration
    When I fill in "configurationName" with "behat-config-test"
    And I press "Add"
    Then I should see "behat-config-test"
    But I should not see "Error:"

  Scenario: Configure configuration
    When I follow configuration "behat-config-test"
    And I configure configuration "behat"
    And I press "Save"
    Then I should see "Extract Settings"
    And I should see "Table"

  Scenario: Save and exit configuration
    When I follow configuration "behat-config-test"
    And I press "Save and Exit"
    Then I should see "Configuration Name"
    And I should see "behat-config-test"

  Scenario: Check rules for configuration
    When I follow configuration "behat-config-test"
    And I configure configuration "behat"
    And I press "Check Rules"
    Then I should see "Transformation Rules Check"
    And I should see "Status: valid"
    But I should not see "Error:"

  Scenario: Run configuration
    When I follow "Run"
    And I select "behat-config-test" from "configName"
    And I press "Run"
    Then I should see "Configuration:"
    And I should see "Created table"
    And I should see "Number of record_ids found: 100"
    And I should see "Processing complete."
    But I should not see "Error:"
    
  Scenario: Copy configuration
    When I follow "ETL Configurations"
    And I copy configuration "behat-config-test" to "behat-copy-test"
    Then I should see "behat-config-test"
    And I should see "behat-copy-test"

  Scenario: Rename configuration
    When I follow "ETL Configurations"
    And I rename configuration "behat-copy-test" to "behat-rename-test"
    Then I should see "behat-rename-test"
    But I should not see "behat-copy-test"

  Scenario: Delete renamed configuration
    When I follow "ETL Configurations"
    And I delete configuration "behat-rename-test"
    Then I should not see "behat-rename-test"
    But I should see "behat-config-test"

  Scenario: Delete configuration
    When I follow "ETL Configurations"
    And I delete configuration "behat-config-test"
    Then I should not see "behat-config-test"

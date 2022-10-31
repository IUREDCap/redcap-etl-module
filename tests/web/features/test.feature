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

  Scenario: Delete configuration if it exists
    When I delete configuration "behat-config-test" if it exists
    Then I should not see "behat-config-test"
      
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

  Scenario: Set pre and post-processing SQL
    When I follow configuration "behat-config-test"
    And I fill in "Pre-Processing SQL" with "CREATE TABLE IF NOT EXISTS pre_test (i int);"
    And I fill in "Post-Processing SQL" with "CREATE TABLE IF NOT EXISTS post_test (j int);"
    And I press "Save"
    Then the "Pre-Processing SQL" field should contain "CREATE TABLE IF NOT EXISTS pre_test (i int);"
    Then the "Post-Processing SQL" field should contain "CREATE TABLE IF NOT EXISTS post_test (j int);"

  Scenario: Save and exit configuration
    When I follow configuration "behat-config-test"
    And I press "Save and Exit"
    Then I should see "Configuration Name"
    And I should see "behat-config-test"

  Scenario: Run configuration
    When I follow "Run"
    And I select "task" from "configType"
    And I select "behat-config-test" from "configName"
    And I select "(embedded server)" from "server"
    And I press "Run"
    And I wait for 4 seconds
    Then I should see "ETL Configuration"
    And I should see "Run"
    And I should see "behat-config-test"
    And I should see "Created table"
    And I should see "Number of record_ids found: 100"
    And I should see "Processing complete."
    And database table "enrollment" should contain 100 rows
    And database table "redcap_project_info" should contain 1 row
    But I should not see "Error:"


  Scenario: Delete configuration
    #When I follow "ETL Configurations"
    When I follow "ETL Configurations"
    And I delete configuration "behat-config-test"
    Then I should not see "behat-config-test"

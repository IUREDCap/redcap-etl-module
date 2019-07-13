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
    And I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"

  Scenario: Create configuration
    When I fill in "configurationName" with "behat"
    And I press "Add"
    Then I should see "behat"
    But I should not see "Error:"

  Scenario: Configure configuration
    When I follow "configure-behat"
    And I configure configuration "behat"
    Then I should see "Extract Settings"
    And I should see "Table"

  Scenario: Run configuration
    When I follow "Run"
    And I select "behat" from "configName"
    And I press "Run"
    Then I should see "Configuration:"
    And I should see "Created table"
    And I should see "Number of record_ids found: 100"
    And I should see "Processing complete."
    But I should not see "Error:"
    
  Scenario: Copy configuration
    When I follow "ETL Configurations"
    And I copy configuration "behat" to "behat-test-copy"
    Then I should see "behat"
    And I should see "behat-test-copy"

  Scenario: Rename configuration
    When I follow "ETL Configurations"
    And I rename configuration "behat-test-copy" to "behat-test-rename"
    Then I should see "behat-test-rename"
    But I should not see "behat-test-copy"

  Scenario: Delete renamed configuration
    When I follow "ETL Configurations"
    And I delete configuration "behat-test-rename"
    Then I should not see "behat-test-rename"
    But I should see "behat"

  Scenario: Delete configuration
    When I follow "ETL Configurations"
    And I delete configuration "behat"
    Then I should not see "behat"

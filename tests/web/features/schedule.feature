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

  Scenario: Delete existing schedule configuration (if any)
    When I follow "ETL Configurations"
    And I delete configuration "behat-sched-test" if it exists
    Then I should not see "behat-sched-test"
    And I should not see "Error:"

  Scenario: Create configuration
    When I fill in "configurationName" with "behat-sched-test"
    And I press "Add"
    Then I should see "behat-sched-test"

  Scenario: Configure configuration
    When I follow configuration "behat-sched-test"
    And I configure configuration "behat"
    And I fill in "Table name prefix" with "sched_"
    And I fill in "email_subject" with "REDCap-ETL Module web test 1/3: schedule ETL configuration"
    And I check "email_errors"
    And I check "email_summary"
    And I press "Save"
    Then I should see "Extract Settings"
    And I should see "Table"
    And the "Table name prefix" field should contain "sched_"

  Scenario: Schedule configuration
    When I follow "Schedule"
    And I select "task" from "configType"
    And I select "behat-sched-test" from "configName"
    And I select "(embedded server)" from "server"
    And I schedule for next hour
    And I press "Save"
    And I wait for 4 seconds
    Then I should see "Save"
    And I should see "ETL Server"
    And I should see "(embedded server)"
    

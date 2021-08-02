#-------------------------------------------------------
# Copyright (C) 2021 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: User-Interface
In order to use REDCap-ETL
As a non-admin user
I need to be able to create workflows

  Background:
    Given I am on "/"
    And I am logged in as user
    When I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"

  Scenario: Create config1 for workflow
    When I fill in "configurationName" with "behat-workflow-config1-test"
    And I press "Add"
    Then I should see "behat-workflow-config1-test"
    But I should not see "Error:"

  Scenario: Configure config1 for workflow
    When I follow configuration "behat-workflow-config1-test"
    And I configure configuration "behat"
    And I press "Save"
    Then I should see "Extract Settings"
    And I should see "Table"

  Scenario: Save and exit config1 for workflow
    When I follow configuration "behat-workflow-config1-test"
    And I press "Save and Exit"
    Then I should see "Configuration Name"
    And I should see "behat-workflow-config1-test"

  Scenario: Create config2 for workflow
    When I fill in "configurationName" with "behat-workflow-config2-test"
    And I press "Add"
    Then I should see "behat-workflow-config2-test"
    But I should not see "Error:"

  Scenario: Configure config2 for workflow
    When I follow configuration "behat-workflow-config2-test"
    And I configure configuration "behat"
    And I press "Save"
    Then I should see "Extract Settings"
    And I should see "Table"

  Scenario: Save and exit config2 for workflow
    When I follow configuration "behat-workflow-config2-test"
    And I press "Save and Exit"
    Then I should see "Configuration Name"
    And I should see "behat-workflow-config2-test"

  Scenario: Create workflow
    When I follow "ETL Workflows"
    And I fill in "workflowName" with "behat-workflow-test"
    And I press "Add"
    Then I should see "behat-workflow-test"
    But I should not see "Error:"

  Scenario: Configure workflow
    When I follow "ETL Workflows"
    And I follow workflow "behat-workflow-test"
    And I add task for workflow
    And I wait for 20 seconds
    

#  Scenario: Run workflow on embedded server
    #When I follow "Run"
    #And I wait for 20 seconds
    #And I select "behat-workflow-test" from "workflowName"
    #And I wait for 20 seconds

  Scenario: Delete workflow as admin
    When I log out
    And I access the admin interface
    When I follow "Workflows"
    And I admin delete workflow "behat-workflow-test"

  Scenario: Delete config1 for workflow
    When I follow "ETL Tasks"
    And I delete configuration "behat-workflow-config1-test"
    Then I should not see "behat-workflow-config1-test"

  Scenario: Delete config2 for workflow
    When I follow "ETL Tasks"
    And I delete configuration "behat-workflow-config2-test"
    Then I should not see "behat-workflow-config2-test"


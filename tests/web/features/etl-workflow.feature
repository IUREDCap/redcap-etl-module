#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: User-Interface
In order to use REDCap-ETL
As a non-admin user
I need to be able to create, copy, rename and delete workflows

  Background:
    Given I am on "/"
    And I am logged in as user
    When I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"
    And I follow "ETL Workflows"

  Scenario: Create workflow configuration
    When I fill in "workflowName" with "behat-workflow-test"
    And I press "Add"
    Then I should see "behat-workflow-test"
    But I should not see "Error:"

    #  Scenario: Configure configuration
    #    When I follow configuration "behat-config-test"
    #    And I configure configuration "behat"
    #    And I press "Save"
    #    Then I should see "Extract Settings"
    #    And I should see "Table"

    #  Scenario: Set pre and post-processing SQL
    #    When I follow configuration "behat-config-test"
    #    And I fill in "Pre-Processing SQL" with "CREATE TABLE IF NOT EXISTS pre_test (i int);"
    #    And I fill in "Post-Processing SQL" with "CREATE TABLE IF NOT EXISTS post_test (j int);"
    #    And I press "Save"
    #    Then the "Pre-Processing SQL" field should contain "CREATE TABLE IF NOT EXISTS pre_test (i int);"
    #    Then the "Post-Processing SQL" field should contain "CREATE TABLE IF NOT EXISTS post_test (j int);"

    #  Scenario: Save and exit configuration
    #    When I follow configuration "behat-config-test"
    #    And I press "Save and Exit"
    #    Then I should see "Configuration Name"
    #    And I should see "behat-config-test"

    #  Scenario: Check rules for configuration
    #    When I follow configuration "behat-config-test"
    #    And I configure configuration "behat"
    #    And I press "Check Rules"
    #Then I should see "Transformation Rules Check"
    #    And I should see "Status: valid"
    #    But I should not see "Error:"

    #  Scenario: Run configuration
    #    When I follow "Run"
    #    And I select "task" from "configType"
    #    And I select "behat-config-test" from "configName"
    #And I select "(embedded server)" from "server"
    #    And I press "Run"
    #    And I wait for 4 seconds
    #Then I should see "ETL Task"
    #    And I should see "Run"
    #    And I should see "behat-config-test"
    #And I should see "Created table"
    #    And I should see "Number of record_ids found: 100"
    #    And I should see "Processing complete."
    #    But I should not see "Error:"

  Scenario: Copy workflow
    And I copy workflow "behat-workflow-test" to "behat-workflow-copy-test"
    Then I should see "behat-workflow-test"
    And I should see "behat-workflow-copy-test"
    And I should not see "Error:"

  Scenario: Rename workflow
    When I rename workflow "behat-workflow-copy-test" to "behat-workflow-rename-test"
    Then I should see "behat-workflow-rename-test"
    But I should not see "behat-workflow-copy-test"

  Scenario: Delete renamed workflow
    When I delete workflow "behat-workflow-rename-test"
    Then I should not see "behat-workflow-rename-test"
    And I should not see "Error:"
    But I should see "behat-workflow-test"

  Scenario: Delete renamed workflow as admin (to actually delete it from the system)
    When I log out
    And I access the admin interface
    When I follow "Workflows"
    And I admin delete workflow "behat-workflow-rename-test"

  Scenario: Delete workflow configuration
    When I delete workflow "behat-workflow-test"
    Then I should not see "behat-workflow-test"
    And I should not see "Error:"

  Scenario: Delete workflow as admin (to actually delete it from the system)
    When I log out
    And I access the admin interface
    When I follow "Workflows"
    And I admin delete workflow "behat-workflow-test"


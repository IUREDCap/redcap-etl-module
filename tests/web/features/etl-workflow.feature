#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: ETL Workflows
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

  # Make sure the initial state is that the workflow does not exist
  Scenario: Delete workflow as admin (to actually delete it from the system)
    When I log out
    And I access the admin interface
    When I follow "Workflows"
    And I admin delete workflow "behat-workflow-test" if it exists

  Scenario: Create workflow configuration
    When I fill in "workflowName" with "behat-workflow-test"
    And I press "Add"
    Then I should see "behat-workflow-test"
    But I should not see "Error:"

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


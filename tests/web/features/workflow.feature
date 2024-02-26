#-------------------------------------------------------
# Copyright (C) 2021 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Workflows
In order to use REDCap-ETL
As a non-admin user
I need to be able to create, run and schedule workflows

  Background:
    Given I am on "/"
    And I am logged in as user
    When I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"

  # Need to delete workflow as admin to really get rid of it,
  # otherwise it will exist as a "re-instateable" workflow.
  Scenario: Delete workflow as admin
    When I log out
    And I access the admin interface
    When I follow "Workflows"
    And I admin delete workflow "behat-workflow-test" if it exists

  Scenario: Delete config1 for workflow
    When I follow "ETL Configurations"
    And I delete configuration "behat-workflow-config1-test" if it exists
    Then I should not see "behat-workflow-config1-test"

  Scenario: Delete config2 for workflow
    When I follow "ETL Configurations"
    And I delete configuration "behat-workflow-config2-test" if it exists
    Then I should not see "behat-workflow-config2-test"

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
    And I wait for 2 seconds
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
    And I rename task 1 to "Task1"
    And I specify etl-configuration "behat-workflow-config1-test" for task "Task1"

    And I add task for workflow
    And I wait for 2 seconds
    And I rename task 2 to "Task2"
    And I specify etl-configuration "behat-workflow-config2-test" for task "Task2"

    Then I should see tasks "Task1", "Task2"

    When I move task "Task2" up
    Then I should see tasks "Task2", "Task1"
    When I move task "Task2" down
    Then I should see tasks "Task1", "Task2"
    
    When I add task for workflow
    And I rename task 3 to "Task3"
    Then I should see tasks "Task1", "Task2", "Task3"
    And I should not see "Error:"
    When I delete task "Task3"
    Then I should see tasks "Task1", "Task2"
    And I should not see "Error:"

  Scenario: Run workflow on embedded server
    When I follow "Run"
    And I select "workflow" from "configType"
    And I select "behat-workflow-test" from "workflowName"
    And I select "(embedded server)" from "server"
    And I press "Run"
    Then I should see "[Task1] Processing complete."
    And I should see "[Task2] Processing complete."
    And database table "redcap_project_info" should contain 2 rows
    And database table "enrollment" should contain 200 rows
    But I should not see "Error:"

  Scenario: Workflow global properties configuration
    When I follow "ETL Workflows"
    And I follow workflow "behat-workflow-test"
    And I check "E-mail errors"
    And I check "E-mail summary"
    And I fill in "E-mail subject" with "REDCap-ETL Module workflow configuration test"
    And I fill in "E-mail to list" with the user e-mail
    And I press "Save"
    Then the "E-mail errors" checkbox should be checked
    And the "E-mail summary" checkbox should be checked
    And Field "E-mail subject" should contain value "REDCap-ETL Module workflow configuration test"

  Scenario: Schedule configuration on embedded server
    When I follow "Schedule"
    And I select "workflow" from "configType"
    And I select "behat-workflow-test" from "workflowName"
    And I select "(embedded server)" from "server"
    And I schedule for next hour
    And I press "Save"
    Then I should see "behat-workflow-test"
    And I should see "ETL Server"
    And I should see "(embedded server)"
    And I should not see "Error:"

  Scenario: Create remote server for testing workflows
    When I log out
    And I access the admin interface
    And I follow "Servers"
    And I delete server "remote-server-workflow-test"
    And I fill in "Server:" with "remote-server-workflow-test"
    And I press "Add Server"
    And I follow server "remote-server-workflow-test"
    And I check "isActive"
    And I configure server "password_authentication"
    Then I should see "remote-server-workflow-test"
    And I should not see "Error:"

  Scenario: Run workflow on remote server
    When I follow "ETL Workflows"
    And I follow workflow "behat-workflow-test"
    And I fill in "E-mail subject" with "REDCap-ETL Module web test 2/3: run workflow on remote server"
    And I press "Save"
    And I follow "Run"
    And I select "workflow" from "configType"
    And I select "behat-workflow-test" from "workflowName"
    And I select "remote-server-workflow-test" from "server"
    And I press "Run"
    And I wait for 4 seconds
    # And Print element "body" text
    Then I should see "Your job has been submitted to server"
    And I should see "remote-server-workflow-test"
    And I should not see "Error:"
    # Need to check e-mail for this test

  Scenario: Schedule configuration on remote server
    When I follow "ETL Workflows"
    And I follow workflow "behat-workflow-test"
    And I fill in "E-mail subject" with "REDCap-ETL Module web test 3/3: schedule workflow on remote server"
    And I press "Save"
    And I follow "Schedule"
    And I select "workflow" from "configType"
    And I select "behat-workflow-test" from "workflowName"
    And I select "remote-server-workflow-test" from "server"
    And I schedule for next hour
    And I press "Save"
    Then I should see "behat-workflow-test"
    And I should see "ETL Server"
    And I should see "remote-server-workflow-test"
    And I should not see "Error:"
    # Need to check e-mail for this test

  Scenario: Check that workflow runs were logged in the admin interface
    When I log out
    And I access the admin interface
    And I follow "Log"
    Then I should see "behat-workflow-test"
    And I should not see "Error:"

  Scenario: Check that the workflow is on the admin workflows page
    When I log out
    And I access the admin interface
    And I follow "Workflows"
    Then I should see "behat-workflow-test"
    And Workflow "behat-workflow-test" should have status "Ready" in admin workflows
    And I should not see "Error:"

  Scenario: Check that the workflow configuration on the admin workflows page goes to the right page
    When I log out
    And I access the admin interface
    And I follow "Workflows"
    When I configure workflow "behat-workflow-test" in admin workflows
    Then I should see "behat-workflow-test"
    And I should see "Configure"
    # And Print element "body" text
    And I should see "Task1"
    And I should see "Task2"
    And I should not see "Error:"


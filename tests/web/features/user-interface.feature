#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: User-Interface
In order to use REDCap-ETL
As a non-admin user
I need to be able to view the REDCap-ETL external module pages
  for a REDCap-ETL enabled project

  Background:
    Given I am on "/"
    And I am logged in as user
    And I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"

  Scenario: Access REDCap-ETL home page for test project
    Then I should see tabs "ETL Tasks", "ETL Workflows", "Configure", "Run", "Schedule","User Manual"
    #Then I should see "ETL Configurations"
    #And I should see "Configure"
    #And I should see "Run"
    #And I should see "Schedule"
    #And I should see "User Manual"

  Scenario: Access REDCap-ETL configure page for test project
    When I follow "Configure"
    And I follow "ETL Task"
    Then I should see "ETL Task Configuration"
    Then I should see "Save"
    Then I should see "Save and Exit"
    But I should not see "Run Now"

  Scenario: Access REDCap-ETL run page for test project
    When I follow "Run"
    Then I should see "ETL Task"
    Then I should see "ETL Workflow"

  Scenario: Access REDCap-ETL schedule page for test project
    When I follow "Schedule"
    Then I should see "Save"
    And I should see "ETL Task"
    And I should see "ETL Workflow"
    And I should see "Server:"
    And I should see "Sunday"
    And I should see "Monday"
    And I should see "Tuesday"
    And I should see "Wednesday"
    And I should see "Thursday"
    And I should see "Friday"
    And I should see "Saturday"

  Scenario: Access REDCap-ETL user manual page for test project
    When I follow "User Manual"
    Then I should see "Overview"
    And I should see "REDCap-ETL Configurations"
    And I should see "Running REDCap-ETL"

  Scenario: Access ETL Tasks page using the tab
    When I follow "ETL Tasks"
    Then I should see "REDCap-ETL configuration name:"
    And I should see "Add"


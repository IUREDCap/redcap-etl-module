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
    Then I should see tabs "ETL Configurations", "ETL Workflows", "Configure", "Run", "Schedule","User Manual"
    But I should not see "REDCap crashed"

  Scenario: Access REDCap-ETL ETL workflows page for test project
    When I follow "ETL Workflows"
    Then I should see "REDCap-ETL Workflow name:"
    And I should see "Configure"
    And I should see "Run"
    But I should not see "REDCap crashed"

  Scenario: Access REDCap-ETL configure page for test project
    When I follow "Configure"
    Then I should see "ETL Configuration"
    And I should see "ETL Workflow"
    But I should not see "REDCap crashed"

  Scenario: Access REDCap-ETL run page for test project
    When I follow "Run"
    Then I should see "ETL Configuration"
    Then I should see "ETL Workflow"
    But I should not see "REDCap crashed"

  Scenario: Access REDCap-ETL schedule page for test project
    When I follow "Schedule"
    And I wait for 10 seconds
    Then I should see "ETL Configuration"
    And I should see "ETL Workflow"
    And I should see "Server:"
    And I should see "Sunday"
    And I should see "Monday"
    And I should see "Tuesday"
    And I should see "Wednesday"
    And I should see "Thursday"
    And I should see "Friday"
    And I should see "Saturday"
    And I should see "ETL Server"
    But I should not see "REDCap crashed"

  Scenario: Access REDCap-ETL user manual page for test project
    When I follow "User Manual"
    Then I should see "Overview"
    And I should see "REDCap-ETL Configurations"
    And I should see "Running REDCap-ETL"
    But I should not see "REDCap crashed"
    But I should not see "Error message:"

  Scenario: Access ETL Configurations page using the tab
    When I follow "ETL Configurations"
    Then I should see "REDCap-ETL configuration name:"
    And I should see "Add"
    But I should not see "REDCap crashed"


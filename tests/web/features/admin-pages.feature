#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Admin-Interface
  In order to execute admin actions
  As an admin
  I need to be able to access the REDCap-ETL Admin Pages

  Background:
    Given I am on "/"
    When I access the admin interface

  Scenario: Access the admin home page
    Then I should see "Info"
    And I should see "Config"
    And I should see "Cron Detail"
    And I should see "Users"
    And I should see "ETL Servers"
    And I should see "Help Edit"

  Scenario: Access the admin config page
    When I follow "Config"
    Then I should see "Last ETL cron run time"
    And I should see "Sunday"
    And I should see "Monday"
    And I should see "Tuesday"
    And I should see "Wednesday"
    And I should see "Thursday"
    And I should see "Friday"
    And I should see "Saturday"

  Scenario: Access the admin cron detail page
    When I follow "Cron Detail"
    Then I should see "Day"
    And I should see "Time"

  Scenario: Access the admin users page
    When I follow "Users"
    Then I should see "List"
    And I should see "Search"

  Scenario: Access the admin user search page
    When I follow "Users"
    And I follow "Search"
    Then I should see "User:"

  Scenario: Access the admin ETL servers page
    When I follow "ETL Servers"
    Then I should see "List"
    And I should see "Configuration"

  Scenario: Access the admin ETL server config page
    When I follow "ETL Servers"
    And I follow "Configuration"
    Then I should see "Server:"

  Scenario: Access the admin help edit list page
    When I follow "Help Edit"
    Then I should see "List"
    And I should see "Edit"
    And I should see "Topic"
    And I should see "Setting"

  Scenario: Access the admin help edit edit page
    When I follow "Help Edit"
    And I follow "Edit"
    Then I should see "Help Topic"
    And I should see "Help Text"
    And I should see "Preview"
    And I should see "Default Help Text"

  Scenario: Access the admin info page
    When I follow "Info"
    Then I should see "Overview"
    And I should see "REDCap-ETL Servers"
    And I should see "Extract Transform Load"

  Scenario: Access the admin log page
    When I follow "Log"
    Then I should see "REDCap-ETL Log"
    And I should see "Log Entries:"


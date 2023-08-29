#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: User-Interface
In order to use REDCap-ETL
As a non-admin user
I need to be able to autogenerate the transformation rules

  Background:
    Given I am on "/"
    And I am logged in as user
    When I follow "My Projects"
    And I select the forms project
    And I follow "REDCap-ETL"

  Scenario: Create configuration
    When I delete configuration "behat-config-test" if it exists
    And I fill in "configurationName" with "behat-config-test"
    And I press "Add"
    And Print element "body" text
    Then I should see "behat-config-test"
    But I should not see "Error:"

  Scenario: Configure configuration that has no auto-gen options specified
    When I follow configuration "behat-config-test"
    And I configure configuration "behat"
    And I press "Save"
    And I log out
    And I wait for 1 seconds
    And I am on "/"
    And I am logged in as user
    And I follow "My Projects"
    And I select the forms project
    And I follow "REDCap-ETL"
    And I follow configuration "behat-config-test"
    Then I should see "Transform Settings"
    And I "should" see this text "TABLE,registration,registration_id,ROOT"

  Scenario: Configure configuration that has the include auto-gen options specified 
    When I follow "My Projects"
    And I select the forms project
    And I follow "REDCap-ETL"
    And I follow configuration "behat-config-test"
    And I specify the auto-gen "include" options for "behat"
    Then I "should" see this text "redcap_data_access_group"
    And I "should" see this text "registration_complete"
    And I "should" see this text "consent_form"

  Scenario: Configure configuration that has the remove auto-gen options specified
    When I follow "My Projects"
    And I select the forms project
    And I follow "REDCap-ETL"
    And I follow configuration "behat-config-test"
    And I specify the auto-gen "remove" options for "behat"
    Then I "should not" see this text "first_name"
    And I "should not" see this text "email"
    And I "should not" see this text "address"
    And I "should not" see this text "comments"

  Scenario: Configure configuration that has nonrepeating-table auto-gen options specified 
    When I follow "My Projects"
    And I select the forms project
    And I follow "REDCap-ETL"
    And I follow configuration "behat-config-test"
    And I specify the auto-gen "nonrepeating fields and table" options for "behat"
    Then I "should" see this text "TABLE,merged,merged_id,ROOT"
    And I "should" see this text "TABLE,weight,merged,REPEATING_INSTRUMENTS"
    But I "should not" see this text "TABLE,emergency,emergency_id,ROOT"

  Scenario: Configure configuration that has combine nonrepeating fields checkbox-only specified (this is an error condition)
    When I follow "My Projects"
    And I select the forms project
    And I follow "REDCap-ETL"
    And I follow configuration "behat-config-test"
    And I specify the auto-gen "combined nonrepeating checkbox only" options for "behat"
    Then I should see "ERROR: In AUTO-GENERATE TRANSFORMATION RULES"
    But I "should not" see this text "TABLE,merged,merged_id,ROOT"
    And I "should not" see this text "TABLE,emergency,emergency_id,ROOT"

 Scenario: Configure configuration that has nonrepeating-table name option only specified (this is not an error condition)
    When I follow "My Projects"
    And I select the forms project
    And I follow "REDCap-ETL"
    And I follow configuration "behat-config-test"
    And I specify the auto-gen "nonrepeating table name only" options for "behat"
    Then I "should" see this text "TABLE,registration,registration_id,ROOT"
    But I should not see "ERROR: In AUTO-GENERATE TRANSFORMATION RULES"
    And I "should not" see this text "TABLE,merged,merged_id,ROOT"

  Scenario: Configure configuration to automatically auto-generate rules before each ETL run and then run ETL 
    When I follow configuration "behat-config-test"
    And I configure configuration "behat"
    And I check "autogen_before_run"
    And I check "autogen_combine_non_repeating_fields"
    And I fill in "autogen_non_repeating_fields_table" with "testtable"
    And I press "Save"
    And I log out
    And I wait for 1 seconds
    And I am on "/"
    And I am logged in as user
    And I follow "My Projects"
    And I select the forms project
    And I follow "REDCap-ETL"
    And I follow configuration "behat-config-test"
    Then I should see "Transform Settings"
    And I "should" see this text "TABLE,registration,registration_id,ROOT"
    But I "should not" see this text "testtable"

    When I log out
    And I wait for 1 seconds
    And I am on "/"
    And I am logged in as user
    And I follow "My Projects"
    And I select the forms project
    And I follow "REDCap-ETL"
    And I follow "Run"
    And I select "task" from "configType"
    And I select "behat-config-test" from "configName"
    And I select "(embedded server)" from "server"
    And I press "Run"
    And I wait for 5 seconds
    Then I should see "Processing complete"
    And I should see "Created table 'testtable'"
    And I should see "Created table 'weight'"
    But I should not see "Created table 'registration'"


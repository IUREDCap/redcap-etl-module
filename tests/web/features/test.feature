#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Zipped CSV file dowload functionality
  In order to download zipped CSV files from the ETL process
  As a non-admin user
  I need to be able specify to download the file

  Background:
    Given I am on "/"
    And I am logged in as user
    When I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"
    
  Scenario: Initialize by deleting test configuration if it exists
    When I follow "ETL Configurations"
    And I delete configuration "behat-config-test" if it exists
    Then I should not see "behat-config-test"

   Scenario: Confirm dataTarget element is displayed correctly 
    When I fill in "configurationName" with "behat-config-test"
    And I press "Add"
    And I follow configuration "behat-config-test"
    And I configure configuration "behat"
    And I press "Save"
    And I follow "Run"
    And I select "workflow" from "configType"
    And I wait for 2 seconds
    Then I should not see "Load data into database"

   Scenario: Run with zip base as target 
    When I follow "Run"
    And I select "task" from "configType"
    And I select "behat-config-test" from "configName"
    And I select "(embedded server)" from "server"
    And I select "csv_zip" from "dataTarget"
    And I press "Run"
    And I wait for 6 seconds
    Then a downloaded file should be found



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
    
   Scenario: Confirm dataTarget element is displayed correctly 
    When I fill in "configurationName" with "behat-config-test"
    And I press "Add"
    And I follow configuration "behat-config-test"
    And I configure configuration "behat"
    And I press "Save"
    And I follow "Run"
    And I select "workflow" from "configType"
    Then I should not see "Load data into database"

    When I select "task" from "configType"
    And I select "behat-config-test" from "configName"
    And I select "(embedded server)" from "server"
    And I wait for 2 seconds
    Then I should see "Load data into database"

    When I follow "Run"
    And I select "task" from "configType"
    And I select "behat-config-test" from "configName"
    And I select "(embedded server)" from "server"
    And I wait for 2 seconds
    And I select "csv_zip" from "dataTarget"
    Then I should see "Export data as CSV zip file"

   Scenario: Run with database as target 
    When I follow "Run"
    And I select "task" from "configType"
    And I select "behat-config-test" from "configName"
    And I select "(embedded server)" from "server"
    And I wait for 1 seconds
    And I select "db" from "dataTarget"
    And I press "Run"
    And I wait for 4 seconds
    Then I should see "Created table 'root'"

   Scenario: Set max Zip file size
    When I log out
    And I access the admin interface
    And I follow "Servers"
    And I follow server "(embedded server)"
    And I fill in "maxZipDownloadFileSize" with "124"
    And I press "Save"
    And I follow server "(embedded server)"
    Then the "maxZipDownloadFileSize" field should contain "124"

   Scenario: Run with zip base as target 
    When I follow "Run"
    And I select "task" from "configType"
    And I select "behat-config-test" from "configName"
    And I select "(embedded server)" from "server"
    And I wait for 1 seconds
    And I select "csv_zip" from "dataTarget"
    And I press "Run"
    And I wait for 6 seconds
    Then a downloaded file should be found

   Scenario: Run zip file as target and max file size too small
    When I log out
    And I access the admin interface
    And I follow "Servers"
    And I follow server "(embedded server)"
    And I fill in "maxZipDownloadFileSize" with ".015"
    And I press "Save"

    When I log out
    And I am logged in as user
    When I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"
    And I follow "Run"
    And I select "task" from "configType"
    And I select "behat-config-test" from "configName"
    And I select "(embedded server)" from "server"
    And I wait for 1 seconds
    And I select "csv_zip" from "dataTarget"
    And I press "Run"
    And I wait for 6 seconds
    Then I should see "ERROR: CSV zip"

  Scenario: Cleanup by deleting configuration
    When I follow "ETL Configurations"
    And I delete configuration "behat-config-test"
    Then I should not see "behat-config-test"

   Scenario: Clean up max Zip file size
    When I log out
    And I access the admin interface
    And I follow "Servers"
    And I follow server "(embedded server)"
    And I fill in "maxZipDownloadFileSize" with "124"
    And I press "Save"
    And I follow server "(embedded server)"
    Then the "maxZipDownloadFileSize" field should contain "124"


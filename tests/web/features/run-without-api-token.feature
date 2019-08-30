#------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: User-Interface
In order to use REDCap-ETL
As a user
I need to be able to create, copy, rename and delete configurations
  for a REDCap-ETL enabled project

  Background:
    Given I am on "/"

    #  @tag create-etl-config-behat-no-api-token
  Scenario: Create configuration
    # As admin, set configuration for test
    When I log in as admin and access REDCap-ETL
    And I follow "Config"
    And I uncheck "Require API token for embedded server"
    And I check "Allow ETL jobs to be run on demand?"
    And I press "Save"
    And I log out

    # Create test project
    And I log in as user and access REDCap-ETL for test project
    And I fill in "configurationName" with "behat-no-api-token"
    And I press "Add"
    And I follow configuration "behat-no-api-token"
    And I configure configuration "behat" without API token
    And I press "Save"

    # Run test project
    And I follow "Run"
    And I press "Run"
    Then I should see "Number of record_ids found: 100"
    And I should see "Processing complete."
    But I should not see "Error:"




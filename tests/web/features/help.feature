#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Help
In order to use REDCap-ETL
As a non-admin user
I need to be able access help

  Background:
    Given I am on "/"
    And I am logged in as user
    And I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"

  Scenario: Delete configuration if it exists
    When I delete configuration "behat-help-test" if it exists
    And I wait for 2 seconds
    Then I should not see "behat-help-test"

  Scenario: Create configuration
    And I fill in "configurationName" with "behat-help-test"
    And I press "Add"
    Then I should see "behat-help-test"

  Scenario: Batch size help
    When I follow "configure-behat-help-test"
    And I follow "batch-size-help-link"
    Then I should see "The batch size indicates how many REDCap record IDs will be processed"
    And I should see "View text on separate page"

  Scenario: Batch size help on separate page
    When I follow "configure-behat-help-test"
    And I follow "batch-size-help-link"
    And I wait for 2 seconds
    And I follow "batch-size-help-page" to new window
    Then I should see "The batch size indicates how many REDCap record IDs will be processed"
    But I should not see "View text on separate page"


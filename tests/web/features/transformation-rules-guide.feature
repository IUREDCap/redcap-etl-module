#-------------------------------------------------------
# Copyright (C) 2023 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Transformation Rules Guide
In order to use REDCap-ETL
As a non-admin user
I need to be able to see the Transformation Rules Guide for editing transformation rules for a configuration

  Background:
    Given I am on "/"
    And I am logged in as user
    And I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"

  Scenario: Delete configuration if it exists
    When I delete configuration "behat-rules-guide-test" if it exists
    Then I should not see "behat-rules-guide-test"

  Scenario: Create configuration
    And I fill in "configurationName" with "behat-rules-guide-test"
    And I press "Add"
    Then I should see "behat-rules-guide-test"

  Scenario: Transformation rules guide
    When I follow "configure-behat-rules-guide-test"
    And I wait for 2 seconds
    And I follow "Transformation Rules Guide" to new window
    Then I should see "Transformation Rules"
    And I should see "This is a simple example"


#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: User-Interface
In order to use REDCap-ETL
As a non-admin user
I need to be able to create, copy, rename and delete configurations
  and get help for a REDCap-ETL enabled project

  Background:
    Given I am on "/"
    And I am logged in as user
    And I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"

  Scenario: Create configuration
    When I fill in "configurationName" with "behat-help-test"
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
    And I follow "batch-size-help-page" to new window
    Then I should see "The batch size indicates how many REDCap record IDs will be processed"
    But I should not see "View text on separate page"
    Then I go to previous window
    Then I follow "Home"

  Scenario: Transformation rules guide
    When I follow "configure-behat-help-test"
    And I follow "Transformation Rules Guide" to new window
    Then I should see "Transformation Rules"
    And I should see "This is a simple example"
    Then I go to previous window
    Then I follow "Home"

  Scenario: Delete configuration
    When I follow "ETL Tasks"
    And I press "deleteConfig1"
    And I press "Delete configuration"
    Then I should not see "behat-test"

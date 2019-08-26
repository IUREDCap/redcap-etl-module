#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Admin Help Customization
  In order to provide site-specific help
  As an admin
  I need to be able to create custom help text

  Background:
    Given I am on "/"

  Scenario: Use help topic selection to access a help topic
    When I log in as admin and access REDCap-ETL
    And I follow "Help Edit"
    And I follow "Edit"
    And I select "E-mail To List" from "Help Topic"
    Then I should see "A comma-separated list of e-mail addresses"

  Scenario: Try to save help with no help topic selected
    When I log in as admin and access REDCap-ETL
    And I follow "Help Edit"
    And I follow "Edit"
    And I press "Save"
    Then I should see "Error:"
    And I should see "No help topic specified"

  Scenario: Preview custom help prepended to default help
    When I log in as admin and access REDCap-ETL
    And I follow "Help Edit"
    And I follow "API Token User"
    And I fill in "customHelp" with "custom help"
    And I select "Prepend custom text to default" from "helpSetting"
    And I press "Preview"
    And I wait for 2 seconds
    Then I should see "custom help This selection specifies"

  Scenario: Preview custom help appended to default help
    When I log in as admin and access REDCap-ETL
    And I follow "Help Edit"
    And I follow "Database Logging"
    And I fill in "customHelp" with "custom help"
    And I select "Append custom text to default" from "helpSetting"
    And I press "Preview"
    And I wait for 2 seconds
    Then I should see "the same ETL process. custom help"

  @modified-help-for-batch-size
  Scenario: Replacing default help text with custom help text shhould update the help setting select
    When I log in as admin and access REDCap-ETL
    And I follow "Help Edit"
    # Edit batch size help:
    And I follow "Batch Size"
    And I fill in "customHelp" with "<p>Batch sizes over 100 are not recommended.</p>"
    And I select "Use custom text" from "helpSetting"
    And I press "Save"
    # Check that the help setting select has been updated:
    Then the "#helpSetting option:selected" element should contain "Use custom text"
    And the "#helpSetting option:selected" element should not contain "Use default text"

  @modified-help-for-batch-size
  Scenario: Replace default help text with custom help text (check user interface)
    When I log in as admin and access REDCap-ETL
    And I follow "Help Edit"
    # Edit batch size help
    And I follow "Batch Size"
    And I fill in "customHelp" with "<p>Batch sizes over 500 are not recommended.</p>"
    And I select "Use custom text" from "helpSetting"
    And I press "Save"
    And I log out

    # Check that help modifications show up for user
    And I log in as user and access REDCap-ETL for test project

    # Add new configuration (so that help can be accessed):
    And I fill in "behat-help-test" for "REDCap-ETL configuration name:"
    And I press "Add"
    And I follow configuration "behat-help-test"
    And I follow "batch-size-help-link"
    Then I should see "Batch sizes over 500 are not recommended."
    But I should not see "The batch size indicates how many REDCap record IDs will be processed"

  Scenario: Remove custom help text
    When I access the admin interface
    And I follow "Help Edit"
    And I follow "Batch Size"

    # Edit batch size help - add custom help
    And I fill in "customHelp" with "<p>Batch sizes over 500 are not recommended.</p>"
    And I select "Use custom text" from "helpSetting"
    And I press "Save"

    # Edit batch size help - remove custom help
    And I fill in "customHelp" with ""
    And I select "Use default text" from "helpSetting"
    And I press "Save"

    Then I should not see "Batch sizes over 500 are not recommended."

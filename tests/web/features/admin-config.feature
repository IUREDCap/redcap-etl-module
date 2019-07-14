#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Admin Config
  In order to control user run options
  As an admin
  I need to be able to turn on and off running on demand and running as a cron job

  Background:
    Given I am on "/"

  Scenario: Admin uncheck "allow on demand" and "allow cron"
    When I access the admin interface
    When I follow "Config"
    And I uncheck "allowOnDemand"
    And I uncheck "allowCron"
    And I uncheck "allowedCronTimes[6][23]"
    And I press "Save"
    Then I should see "Last ETL cron run time"
    And I should see "Sunday"
    And I should see "Monday"
    And I should see "Tuesday"
    And I should see "Wednesday"
    And I should see "Thursday"
    And I should see "Friday"
    And I should see "Saturday"
    And the checkbox "allowedCronTimes[6][23]" should be unchecked

  Scenario: User logged in with "allow on demand" and "allow cron" disabled
    Given I am logged in as user
    And I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"
    Then I should see "ETL Configurations"
    And I should see "Configure"
    And I should see "User Manual"
    But I should not see "Run"
    And I should not see "Schedule"

  Scenario: Check "allow on demand" and "allow cron"
    When I access the admin interface
    When I follow "Config"
    And I check "allowOnDemand"
    And I check "allowCron"
    And I check "allowedCronTimes[6][23]"
    And I press "Save"
    Then I should see "Last ETL cron run time"
    And the checkbox "allowedCronTimes[6][23]" should be checked

  Scenario: User logged in with "allow on demand" and "allow cron" enabled
    Given I am logged in as user
    And I follow "My Projects"
    When I select the test project
    And I follow "REDCap-ETL"
    Then I should see "ETL Configurations"
    And I should see "Configure"
    And I should see "User Manual"
    And I should see "Run"
    And I should see "Schedule"



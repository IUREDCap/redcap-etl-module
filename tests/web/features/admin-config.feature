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

  Scenario: Admin save of config changes for "allow on demand", "allow cron", and
      and an allowed cron time.
    When I access the admin interface
    And I follow "Config"
    And I uncheck "allowOnDemand"
    And I uncheck "allowCron"
    And I uncheck "allowedCronTimes[6][23]"
    And I press "Save"
    Then I should see "Last ETL cron run time"
    And I should see table headers "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
    And the checkbox "allowedCronTimes[6][23]" should be unchecked

  Scenario: User logged in with "allow on demand" and "allow cron" disabled
    # Make changes as admin:
    When I access the admin interface
    And I follow "Config"
    And I uncheck "allowOnDemand"
    And I uncheck "allowCron"
    And I press "Save"
    And I log out

    # Access test project as user:
    And I log in as user
    And I follow "My Projects"
    And I select the test project
    And I follow "REDCap-ETL"

    # Check the tabs:
    And I should see tabs "ETL Configurations", "Configure", "User Manual", "Configure", "User Manual"
    But I should not see tabs "Run", "Schedule"
    And tab "ETL Configurations" should be selected

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
    When I log in as user and access REDCap-ETL for test project
    Then I should see tabs "ETL Configurations", "Configure", "User Manual", "Run", "Schedule"



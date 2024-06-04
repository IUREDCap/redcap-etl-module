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
    And I uncheck "allowedCronTimes[6][23]"
    And I press "Save"
    Then I should see "Last ETL cron run time"
    And I should see table headers "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
    And the checkbox "allowedCronTimes[6][23]" should be unchecked

  Scenario: Check "allow on demand" and "allow cron"
    When I access the admin interface
    When I follow "Config"
    And I check "allowedCronTimes[6][23]"
    And I press "Save"
    Then I should see "Last ETL cron run time"
    And the checkbox "allowedCronTimes[6][23]" should be checked



#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Admin Log
  In order to monitor user activity
  As an admin
  I need to be able to use the REDCap-ETL Admin Log page

  Background:
    Given I am on "/"
    When I access the admin interface

  Scenario: Use the admin log page to see cron jobs
    When I follow "Log"
    And I select "Cron Jobs" from "Log Entries:"
    And I press "Display"
    Then I should see "Cron Jobs"
    Then I should see table headers "Log ID", "Time", "Day", "Hour", "# Jobs"

  Scenario: Use the admin log page to see ETL processes
    When I follow "Log"
    And I select "ETL Processes" from "Log Entries:"
    And I press "Display"
    Then I should see "ETL Processes"
    Then I should see table headers "Log ID", "Time", "Project ID", "Server", "Config", "User ID", "Username", "Cron?", "Cron Day", "Cron Hour", "Details"


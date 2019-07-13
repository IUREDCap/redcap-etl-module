#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Admin-Interface
  In order to execute admin actions
  As an admin
  I need to be able to access the REDCap-ETL Admin Pages

  Background:
    Given I am on "/"
    When I access the admin interface

  Scenario: Access the admin user search page
    When I follow "Users"
    And I follow "Search"
    And I search for user
    Then I should see "ETL Access?"
    And I should see "PID"
    And I should see "Project"
    And I should see "ETL Configurations"


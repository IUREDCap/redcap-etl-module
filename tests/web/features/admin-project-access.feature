#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Admin-Interface
  In order to help users
  As an admin
  I need to be able to access their projects' ETL information

  Background:
    Given I am on "/"
    When I access the admin interface

  Scenario: Access the REDCap-ETL page of a user's project
    When I follow "Users"
    And I follow "Search"
    And I search for user
    And I select the test project in new window
    And I follow "REDCap-ETL"
    Then I should see "ETL Tasks"


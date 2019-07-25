#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Admin User Management
  In order to manage users
  As an admin
  I need to be able to add users to, and delete users from, the list of approved ETL users;
  and I need to be able to set ETL access for their projects

  Background:
    Given I am on "/"

  Scenario: Delete the test user from REDCap-ETL
    When I access the admin interface
    When I follow "Users"
    And I follow "Search"
    And I search for user
    And I press "Delete User from REDCap-ETL"
    And I press "Delete user"
    Then I should see "deleted"

  Scenario: Try to access REDCap-ETL for test project as the test user
    When I log in as user
    And I follow "My Projects"
    And I select the test project
    And I follow "REDCap-ETL"
    Then I should see "To request access, click on the button below"

  Scenario: Add back the test user to REDCap-ETL
    When I access the admin interface
    When I follow "Users"
    And I follow "Search"
    And I search for user
    And I check test project access
    And I press "Save"


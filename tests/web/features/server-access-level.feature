#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Server access level management
  In order to manage access to servers
  As an admin
  I need to be able to set the access level and assign and remove allowed users for private-level servers

  Background:
    Given I am on "/"
 
  Scenario: Without adding any users to it list of allowed users
    When I access the admin interface
    And I follow "ETL Servers"
    And I follow server "(embedded server)"
    And I choose "private" as the access level
    And I follow "Add User Access"
    And I follow "List"
    And I click on the user
    And I check "accessCheckbox[(embedded server)]"
    And I press "Save"
    And I wait for 2 seconds
    And I follow "ETL Servers"
    And I follow server "(embedded server)"
    And I choose "admin" as the access level
    And I wait for 5 seconds
    Then the "#accessLevelId option:selected" element should contain "admin"
    And I "should not" see a "remove user checkbox" item for the user

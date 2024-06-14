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
    When I access the admin interface

  Scenario: Set the access level to admin
    When I follow "Servers"
    And I delete server "local-server"
    And I wait for 1 seconds
    And I fill in "server-name" with "local-server"
    And I press "Add Server"
    And I wait for 1 seconds
    And I follow server "local-server"
    And I configure server "local"
    And I wait for 1 seconds
    And I follow "Servers"
    And I follow server "local-server"
    And I select "admin" from "accessLevel"
    And I wait for 5 seconds
    Then the "#accessLevelId option:selected" element should contain "admin"
    But I should not see "Manage Private Access Users"

    When I follow "Servers"
    Then I should see "local-server" followed by "admin"

  Scenario: Set the access level to public
    When I follow "Servers"
    And I follow server "local-server"
    And I select "public" from "accessLevel"
    And I wait for 5 seconds
    Then the "#accessLevelId option:selected" element should contain "public"
    But I should not see "Manage Private Access Users"

    When I follow "Servers"
    Then I should see "local-server" followed by "public"

  Scenario: Set the access level to private
    When I follow "Servers"
    And I follow server "local-server"
    And I select "private" from "accessLevel"
    And I wait for 5 seconds
    Then the "#accessLevelId option:selected" element should contain "private"
    And I should see "Manage Private Access Users"

  Scenario: Access the User page for server with private access
    When I follow "Servers"
    And I follow server "(embedded server)"
    And I select "private" from "accessLevel"
    And I wait for 2 seconds
    Then I should see "Manage Private Access Users"
 
  Scenario: Add a user to a server with private access
    When I follow "Servers"
    And I follow server "(embedded server)"
    And I select "private" from "accessLevel"
    And I press "Manage Private Access Users"
    And I search for user
    And I press "Add"
    And I wait for 2 seconds
    And I press "Save"
    And I wait for 2 seconds
    And I follow "Users"
    And I follow "List"
    And I wait for 2 seconds
    Then I "should" see a "link" item for the user
    
    When I click on the user
    Then I should see "Server private-level access for user"
    And I should see "(embedded server)"
    And I wait for 10 seconds

    When I check "accessCheckbox[(embedded server)]"
    And I press "Save"
    And I click on the user
    And I wait for 10 seconds
    Then the checkbox "accessCheckbox[(embedded server)]" should be checked

    When I follow "Servers"
    And I follow server "(embedded server)"
    And I press "Manage Private Access Users"
    Then the test user should have private server access
    # Then I should see "Manage Private Access Users"

  Scenario: Using the server page, remove an assigned user from a server with private access
    When I follow "Servers"
    And I follow server "(embedded server)"
    And I press "Manage Private Access Users"
    And I delete private server access for the test user
    And I wait for 2 seconds
    And I press "save-private-access"
    And I wait for 2 seconds
    And I press "Manage Private Access Users"
    And I wait for 2 seconds
    Then the test user should not have private server access

    When I follow "Users"
    And I click on the user
    Then the checkbox "accessCheckbox[(embedded server)]" should be unchecked
     
  Scenario: Using the user page, remove an assigned user from a server with private access
    When I follow "Servers"
    And I follow server "(embedded server)"
    And I select "private" from "accessLevel"
    And I press "Manage Private Access Users"
    And I search for user
    And I press "Add"
    And I wait for 2 seconds
    And I press "save-private-access"
    And I wait for 2 seconds
    And I press "Save"
    And I wait for 2 seconds

    And I follow "Users"
    And I follow "List"
    And I click on the user
    And I uncheck "accessCheckbox[(embedded server)]"
    And I press "Save"

    And I follow "Servers"
    And I follow server "(embedded server)"
    And I press "Manage Private Access Users"
    Then the test user should not have private server access

  Scenario: Change the access level from private with users assigned to admin and do not delete the users list
    When I follow "Servers"
    And I follow server "(embedded server)"
    And I choose "private" as the access level
    And I press "Save"

    And I follow "Users"
    And I follow "List"
    And I click on the user
    And I check "accessCheckbox[(embedded server)]"
    And I press "Save"
    And I wait for 2 seconds
    And I follow "Servers"
    And I follow server "(embedded server)" 
    And I select "admin" from "accessLevel"
    And I wait for 2 seconds
    And I press "Save list"
    And I wait for 4 seconds
    Then the "#accessLevelId option:selected" element should contain "admin"

    When I select "private" from "accessLevel"
    And I press "Manage Private Access Users"
    Then the test user should have private server access

  Scenario: Change the access level from private with users assigned to public and delete the users list
    When I follow "Servers"
    And I follow server "(embedded server)"
    # And I choose "public" as the access level and click "Delete list"
    And I select "public" from "accessLevel"
    And I wait for 2 seconds
    And I press "Delete list"
    And I wait for 2 seconds
    When I follow "Servers"
    And I follow server "(embedded server)"
    Then the "#accessLevelId option:selected" element should contain "public"

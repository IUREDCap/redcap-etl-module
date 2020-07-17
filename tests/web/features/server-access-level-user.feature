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

  Scenario: If needed, assign a private-level access to the embedded server and add the user
    When I access the admin interface
    And I follow "ETL Servers"
    And I follow server "(embedded server)"
    And I select "private" from "accessLevel"
    And I follow "Add User Access"
    And I follow "List"
    And I click on the user
    And I check "accessCheckbox[(embedded server)]"
    And I press "Save"

    And I follow "ETL Servers"
    And I delete server "local-server"
    And I follow "ETL Servers"
    And I fill in "server-name" with "local-server"
    And I press "Add Server"
    And I follow "ETL Servers"
    And I follow server "local-server"
    And I configure server "local"
    And I follow "ETL Servers"
    And I follow server "local-server"
    And I select "public" from "accessLevel"

    And I follow "ETL Servers"
    And I delete server "admin-test"
    And I follow "ETL Servers"
    And I copy server "local-server" to "admin-test"
    And I follow "ETL Servers"
    And I follow server "admin-test"
    And I select "admin" from "accessLevel"

    #create a private server with no assigned users
    And I follow "ETL Servers"
    And I delete server "private-test"
    And I wait for 1 seconds
    And I follow "List"
    Then I should not see "private-test"

    When I copy server "local-server" to "private-test"
    And I follow "ETL Servers"
    And I follow server "private-test"
    And I select "private" from "accessLevel"
    And I wait for 5 seconds

  Scenario: Log in a user who has been allowed private-level access and verify the correct servers are listed on the Run tab
    And I log in as user and access REDCap-ETL for test project
    And I follow "Run"
    Then the "#serverId" element should contain "(embedded server)"
    And the "#serverId" element should contain "local-server"
    But the "#serverId" element should not contain "admin-test"
    But the "#serverId" element should not contain "private-test"

  Scenario: Log in a user who has been allowed private-level access and verify the correct servers are listed on the Schedule tab
    When I log in as user and access REDCap-ETL for test project
    And I follow "Schedule"
    Then the "#serverId" element should contain "(embedded server)"
    And the "#serverId" element should contain "local-server"
    But the "#serverId" element should not contain "admin-test"
    But the "#serverId" element should not contain "private-test"

  Scenario: For an admin user, verify they can see all servers on the Run tab
    When I access the admin interface
    And I follow "Browse Projects"
    And I press "View all projects"
    And I select the test project
    And I follow "REDCap-ETL"
    And I follow "Run"
    Then the "#serverId" element should contain "(embedded server)"
    And the "#serverId" element should contain "local-server"
    And the "#serverId" element should contain "admin-test"
    And the "#serverId" element should contain "private-test"

  Scenario: For an admin user, verify they can see all servers on the Schedule tab
    When I access the admin interface
    And I follow "Browse Projects"
    And I press "View all projects"
    And I select the test project
    And I follow "REDCap-ETL"
    And I follow "Schedule"
    Then the "#serverId" element should contain "(embedded server)"
    And the "#serverId" element should contain "local-server"
    And the "#serverId" element should contain "admin-test"
    And the "#serverId" element should contain "private-test"


 Scenario: Change the access level from private with users assigned to public and delete the users list
    When I access the admin interface
    And I follow "ETL Servers"
    And I follow server "(embedded server)"
    And I choose "public" as the access level and click "Delete list"
    And I press "Save"
    And I wait for 5 seconds
    When I follow "ETL Servers"
    And I follow server "(embedded server)"
    Then the "#accessLevelId option:selected" element should contain "public"


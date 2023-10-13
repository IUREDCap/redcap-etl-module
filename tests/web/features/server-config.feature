#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Server configuration management
  In order to manage servers
  As an admin
  I need to be able to create, configure, test, copy, delete and rename servers

  Background:
    Given I am on "/"
    When I access the admin interface

  Scenario: Delete local servers (if they exist)
    When I follow "Servers"
    And I delete server "local-server"
    And I delete server "local-server-copy"
    And I delete server "local-server-rename"
    And I wait for 4 seconds
    Then I should see "(embedded server)"
    But I should not see "local-server"
    But I should not see "local-server-copy"
    But I should not see "local-server-rename"
    And I should not see "Error:"

  Scenario: Test embedded server configuration
    When I follow "Servers"
    And I follow server "(embedded server)"
    Then I should see "Data Load Options"
    And I should see "CSV ZIP Download"
    But I should not see "Server Connection Settings"
    But I should not see "Server Command Settings"

  Scenario: Test embedded server connection
    When I follow "Servers"
    And I follow server "(embedded server)"
    And I press "Test Server Connection"
    Then the "#testOutput" element should contain "REDCap-ETL"
    And the "#testOutput" element should contain "found"

  Scenario: Create local server configuration
    When I follow "Servers"
    And I fill in "server-name" with "local-server"
    And I press "Add Server"
    Then I should see "local-server"
    But I should not see "Error: "

  Scenario: Configure local server configuration
    When I follow "Servers"
    And I follow server "local-server"
    And I configure server "local"
    Then I should see "local-server"

  Scenario: Copy server configuration
    When I follow "Servers"
    And I copy server "local-server" to "local-server-copy"
    Then I should see "local-server-copy"
    But I should not see "Error: "

  Scenario: Rename copied server configuration
    When I follow "Servers"
    And I rename server "local-server-copy" to "local-server-rename"
    Then I should see "local-server-rename"
    But I should not see "local-server-copy"
    And I should not see "Error: "

  Scenario: Delete renamed configuration
    When I follow "Servers"
    And I delete server "local-server-rename"
    Then I should see "(embedded server)"
    And I should see "local-server"
    But I should not see "local-server-rename"
    And I should not see "Error: "

  Scenario: Create local server configuration that already exists
    When I follow "Servers"
    And I fill in "server-name" with "local-server"
    And I press "Add Server"
    Then I should see "Error: "
    And I should see "already exists"


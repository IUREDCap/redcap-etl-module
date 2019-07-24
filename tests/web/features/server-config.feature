#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Server configuration management
  In order to manage servers
  As an admin
  I need to be able to create, configure, copy, delete and rename servers

  Background:
    Given I am on "/"
    When I access the admin interface

  Scenario: Delete local server (if it exist)
    When I follow "ETL Servers"
    And I delete server "local-server"
    Then I should see "(embedded server)"
    But I should not see "local-server"
    And I should not see "Error:"

  Scenario: Create local server configuration
    When I follow "ETL Servers"
    And I fill in "server-name" with "local-server"
    And I press "Add Server"
    Then I should see "local-server"
    But I should not see "Error: "

  Scenario: Configure local server configuration
    When I follow "ETL Servers"
    And I follow server "local-server"
    And I configure server "local"
    Then I should see "local-server"

  Scenario: Copy server configuration
    When I follow "ETL Servers"
    And I copy server "local-server" to "local-server-copy"
    Then I should see "local-server-copy"
    But I should not see "Error: "

  Scenario: Rename copied server configuration
    When I follow "ETL Servers"
    And I rename server "local-server-copy" to "local-server-rename"
    Then I should see "local-server-rename"
    But I should not see "local-server-copy"
    And I should not see "Error: "

  Scenario: Delete renamed configuration
    When I follow "ETL Servers"
    And I delete server "local-server-rename"
    Then I should see "(embedded server)"
    And I should see "local-server"
    But I should not see "local-server-rename"
    And I should not see "Error: "

  Scenario: Create local server configuration that already exists
    When I follow "ETL Servers"
    And I fill in "server-name" with "local-server"
    And I press "Add Server"
    Then I should see "Error: "
    And I should see "already exists"


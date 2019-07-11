#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Server configuration management
  In order to manage up servers
  As an admin
  I need to be able to create, configure, copy, delete and rename servers

  Background:
    Given I am on "/"
    When I access the admin interface

    Scenario: Create local server configuration
    When I follow "ETL Servers"
    And I fill in "server-name" with "local-server"
    And I press "Add Server"
    Then I should see "local-server"
    But I should not see "Error: "

  Scenario: Copy server configuration
    When I follow "ETL Servers"
    And I press "copyServer2"
    And I fill in "copy-to-server-name" with "local-server-copy"
    And I press "Copy server"
    Then I should see "local-server-copy"
    But I should not see "Error: "

  Scenario: Rename copied server configuration
    When I follow "ETL Servers"
    And I press "renameServer3"
    And I fill in "rename-new-server-name" with "local-server-rename"
    And I press "Rename server"
    Then I should see "local-server-rename"
    But I should not see "local-server-copy"
    And I should not see "Error: "

  Scenario: Delete renamed configuration
    When I follow "ETL Servers"
    And I press "deleteServer3"
    And I press "Delete server"
    Then I should see "(embedded server)"
    And I should see "local-server"
    But I should not see "local-server-rename"
    And I should not see "Error: "

  Scenario: Delete local server configuration
    When I follow "ETL Servers"
    And I press "deleteServer2"
    And I press "Delete server"
    Then I should see "(embedded server)"
    But I should not see "local-server"


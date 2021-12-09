#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

Feature: Zipped CSV file dowload functionality
  In order to download zipped CSV files from the ETL process
  As an admin
  I need to be able to modify the maximum filesize of a file to be downloaded

  Background:
    Given I am on "/"
    When I access the admin interface

  Scenario: Change maximum file size
    When I follow "Servers"
    And I follow server "(embedded server)"
    And I fill in "maxZipDownloadFileSize" with "77"
    And I press "Save"
    And I follow server "(embedded server)"
    Then the "maxZipDownloadFileSize" field should contain "77"

  Scenario: Invoke default max file size
    When I follow "Servers"
    And I follow server "(embedded server)"
    And I fill in "maxZipDownloadFileSize" with ""
    And I press "Save"
    And I follow server "(embedded server)"
    Then the "maxZipDownloadFileSize" field should contain "100"


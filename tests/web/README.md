<!--
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
-->

REDCap-ETL External Module Web Tests
======================================

Web tests, which access the REDCap-ETL External Module running in REDCap, have been developed
using [behat](https://behat.org). 

One-time initial setup:
--------------------------

1. Install the Chrome browser if it is not already installed. For example, on Ubuntu 16 you can use the following:

    sudo apt install chromium-browser

2. Create a non-admin REDCap user and admin REDCap user for testing.

3. Create a database account (if you don't already have one) for testing.

4. Create a REDCap-ETL enabled project for the non-admin user where:

    * the title of the project is unique
    * the project is created by importing the REDCap-ETL [Repeating Events](https://github.com/IUREDCap/redcap-etl/blob/master/tests/projects/RepeatingEvents.REDCap.xml) project file
    * the project is approved for REDCap-ETL use (by the non-admin user)
    * the project has no ETL configurations
    * the project has an API token with export permission

4. Install Composer if you don't already have it, and run the following command in the tests/web directory:

    composer install

5. Run the following command in the top-level web tests directory:

    cp config-example.ini config.ini

6. Edit the config.ini file created above, and enter appropriate values for properties

7. If you want to collect test coverage data, make sure that the tests/web/coverage-data/ directory can be written to by your REDCap web server.
    The REDCap web server has to have permission to write to this directory for code coverage
    data to be collected.


Setup each time before tests are run
---------------------------------------

Clear any previous coverage data:

    php clear_coverage_data.php

Set coverage code to run at the beginning and end of each request. You need to set the following
PHP properties as follows:

* **auto_prepend_file** - should be set to the full path of the start_coverage.php script in this directory
* **auto_append_file** - should be set to the full path of the end_coverage.php script in this directory

The easiest way to do this is to set these in the php.ini file for the web server running REDCap.
The scripts are designed to only collect test coverage data for the web tests.


Run chrome browser setting ports as shown below (running Chrome for use with DMore chromebrowser):

    chrome --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222

Or, in headless mode (this runs faster, but you won't see the browser running):

    chrome --disable-gpu --headless --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222

Running the tests
----------------------

There are some web tests that use phpunit, and they can be run using the following
command in the top-level web tests directory (web/tests):

    ./vendor/bin/phpunit

Most of the web tests use behat. You can use the following commands in the top-level
web tests directory (tests/web) to run the behat web tests:

    ./vendor/bin/behat
    ./vendor/bin/behat -f progress      # just prints summary of results
    ./vendor/bin/behat <path-to-feature-file>    # for testing a single feature file


Viewing the test coverage data
-------------------------------

Combine the coverage data:

    php combine_coverage.php

Open the following file with a web browser:

    tests/web/coverage/index.php

You can add the unit test coverage data by executing the following command in the top-level module directory:

    ./vendor/bin/phpunit --coverage-php tests/web/coverage-data/coverage.unit

Then to update the coverage/index.php file, you need to re-run the combine_coverage.php script.

Similarly, you can also add manual test coverage data by setting the 'code-coverage-id' cookie in your browser, and then going through your tests in that browser. For example, in Chrome:

* Enter &lt;CTRL&gt;&lt;SHIFT&gt;J to bring up the developer tools console
* In the web console, enter:

        document.cookie="code-coverage-id=manual"


Other commands
----------------------

See the definition expressions for behat:

    ./vendor/bin/behat -dl


Test writing guidelines
----------------------------

Each behat feature file should leave the system in the initial test status after it completes, including:

* The user account and test project in the test configuration file should still exist
* The test project should be configured so that the tests user can run REDCap-ETL on it
* The admin account in the test configuration file should still exist
* The admin configuration should be set so that both cron and on-demand jobs are allowed
* The embedded server should be active and have an access level of "public"


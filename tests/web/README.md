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

5. Create a REDCap-ETL enabled project for the non-admin user where:

    * the title of the project is unique
    * the project is created by importing the REDCap-ETL [Repeating Forms](https://github.com/IUREDCap/redcap-etl/blob/master/tests/projects/RepeatingForms.REDCap.xml) project file
    * the project is approved for REDCap-ETL use (by the non-admin user)
    * the project has no ETL configurations
    * the project has an API token with export permission

6. Install Composer if you don't already have it, and run the following command in the tests/web directory:

    composer install

7. Run the following command in the top-level web tests directory:

    cp config-example.ini config.ini

8. Edit the config.ini file created above, and enter appropriate values for properties

9. If you want to collect test coverage data, you need to complete the following steps:

    * Make sure that the tests/web/coverage-data/ directory can be written to by your REDCap web server.
      The REDCap web server has to have permission to write to this directory for code coverage
      data to be collected.
    * Set coverage code to run at the beginning and end of each web test request. You need to set the
      PHP properties as shown below. The easiest way to do this is to set these in the php.ini file
      for the web server running REDCap.

        * **auto_prepend_file** - should be set to the full path of the **tests/web/start_coverage.php** script
        * **auto_append_file** - should be set to the full path of the **tests/web/end_coverage.php** script

        * If you are using the Apache web server, a more flexible approach to set up the coverage
          code is as follows (using Ubuntu as the example operating system):

            * Create an Apache configuration file **code-coverage.conf** in the available Apache configuration
              files directory (e.g., **/etc/apache2/conf-available/**) with the following contents
              (the script directory needed to be changed as appropropriate):

    php_value auto_prepend_file /var/www/html/redcap/modules/redcap-etl-module_v2.2.0/tests/web/start_coverage.php
    php_value auto_append_file  /var/www/html/redcap/modules/redcap-etl-module_v2.2.0/tests/web/end_coverage.php

            * Enable the above configuration file with the following commands:
                
    sudo a2enconf code-coverage
    sudo systemctl reload apache2'

            * Disable the configuration file with this command:

    sudo a2disconf code-coverage
    sudo systemctl reload apache2



Setup each time before tests are run
---------------------------------------

### Test coverage statistics

If you want to collect test coverage data, you will need to
clear any previous coverage data by executing the following in the **tests/web** directory:

    php clear_coverage_data.php

### Browser setup

For the automated web tests to run, you need to run an instance of the Chrome browser that the web tests
can access.
To run the browser in headless mode (the recommended approach), use the command shown below.
Running in headless mode will make the tests run faster, and can be used to run the entire set of tests at once,
but you won't see the browser running.

    chrome --disable-gpu --headless --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222

If you want to actually see the tests interacting with the browser, use the command shown below 
to start Chrome instead of the command above.
If you use the command below, you will need to run the tests one feature at a time.

    chrome --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222




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

Similarly, you can also add manual test coverage data by setting the 'code-coverage-id' cookie in your browser,
and then going through your tests in that browser. For example, in Chrome:

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


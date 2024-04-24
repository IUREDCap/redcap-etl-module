<!--
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
-->

**Note:** The web tests are sometimes failing when run all together, and they are taking a very long time to run.
Running the web tests one feature at a time will prevent these issues.

REDCap-ETL External Module Web Tests
======================================

Automated web tests, which access the REDCap-ETL External Module running in REDCap, have been developed
using [Behat](https://behat.org) with [Mink](https://mink.behat.org/en/latest/). The tests are
written in English based on standard and custom sentence patterns and are in the **tests/web/features**
directory. In addition, some web tests have been written that use [PHPUnit](https://phpunit.de/) with 
[Mink](https://mink.behat.org/en/latest/). There tests have been written in PHP.


One-time initial setup:
--------------------------

1. Install the Chrome browser if it is not already installed. For example, on Ubuntu 20 you can use the following:

    sudo apt install chromium-browser

2. In REDCap, create a non-admin REDCap user and admin REDCap user for testing.

3. Create a database and database account (if you don't already have one) for the tests to use for loading data extracted
   from REDCap. For example, in MySQL:

        CREATE DATABASE `etl_test`;
        CREATE USER 'etl_user'@'localhost' IDENTIFIED BY 'etlPassword';
        GRANT ALL ON `etl_test`.* TO 'etl_user'@'localhost';

4. In REDCap, create a REDCap-ETL enabled project for the non-admin user where:

    * the title of the project is unique
    * the project is created by importing the REDCap-ETL [Repeating Events](https://github.com/IUREDCap/redcap-etl/blob/master/tests/projects/RepeatingEvents.REDCap.xml) project file
    * the project is approved for REDCap-ETL use (by the non-admin user)
    * the project has no ETL configurations
    * the project has an API token with export permission

5. In REDCap, create a REDCap-ETL enabled project for the non-admin user where:

    * the title of the project is unique
    * the project is created by importing the REDCap-ETL [Repeating Forms](https://github.com/IUREDCap/redcap-etl/blob/master/tests/projects/RepeatingForms.REDCap.xml) project file
    * the project is approved for REDCap-ETL use (by the non-admin user)
    * the project has no ETL configurations
    * the project has an API token with export permission

6. In REDCap, in the REDCap-ETL admin interface, make sure that the from e-mail for the embedded server is set

7. Remote server setup

    * To run all of the tests, you will need to set up a remote REDCap-ETL server.
    * See [docs/RemoteEtlServerGuide.md](../../docs/RemoteEtlServerGuide.md) for more information

8. Install Composer if you don't already have it, and run the following command in the tests/web directory:

    composer install

9. Run the following command in the top-level web tests directory:

    cp config-example.ini config.ini

10. Edit the config.ini file created above, and enter appropriate values for properties

11. If you want to collect test coverage data, you need to complete the following steps:

    * Make sure that the tests/web/coverage-data/ directory can be written to by your REDCap web server.
      The REDCap web server has to have permission to write to this directory for code coverage
      data to be collected.
    * Set coverage code to run at the beginning and end of each web test request. You need to set the
      PHP properties as shown below. The easiest way to do this is to set these in the php.ini file
      for the web server running REDCap.

        * **auto_prepend_file** - should be set to the full path of the **tests/web/start_coverage.php** script
        * **auto_append_file** - should be set to the full path of the **tests/web/end_coverage.php** script

    * If you are using the Apache web server, an alternative, more flexible approach to set up the coverage
      code is as follows (using Ubuntu as the example operating system):

        * Create an Apache configuration file **code-coverage.conf** in Apache's available configuration
          files directory (e.g., **/etc/apache2/conf-available/**) with the following contents
          (the script directory needs to be changed as appropriate):

            <pre>
            php_value auto_prepend_file /var/www/html/redcap/modules/redcap-etl-module_v2.2.0/tests/web/start_coverage.php
            php_value auto_append_file  /var/www/html/redcap/modules/redcap-etl-module_v2.2.0/tests/web/end_coverage.php
            </pre>

        * Enable the above configuration file with the following commands:
                
            <pre>
            sudo a2enconf code-coverage
            sudo systemctl reload apache2
            </pre>

        * Disable the configuration file with these commands:

            <pre>
            sudo a2disconf code-coverage
            sudo systemctl reload apache2
            </pre>



Setup each time before tests are run
---------------------------------------

Since the web tests need to access a running instance of the REDCap-ETL external module, REDCap must be running
and have REDCap-ETL external module installed.

### Test coverage statistics

If you want to collect test coverage data, you will need to
clear any previous coverage data by executing the following in the **tests/web** directory:

    php clear_coverage_data.php

And you will need to run the following before running phpunit or behat:

    XDEBUG_MODE=coverage
    export XDEBUG_MODE

### Browser setup

For the automated web tests to run, you need to run an instance of the Chrome browser that the web tests
can access.
To run the browser in headless mode (the recommended approach), use the command shown below.
Running in headless mode will make the tests run faster, and can be used to run the entire set of tests at once,
but you won't see the browser running.

    chrome --disable-gpu --disable-popup-blocking --headless=new --remote-debugging-port=9222 --window-size=1920,1080

If you want to actually see the tests interacting with the browser, use the command shown below 
to start Chrome instead of the command above.
If you use the command below, you will need to run the tests one feature at a time.

    chrome --disable-gpu --disable-popup-blocking --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222

Note that if you installed **chromium-browser**, you will either need to make an alias named "chrome" for it, or
use "chromium-browser" in the commands above instead of "chrome".

You will need to make sure that pop-ups are enabled for the site from which you are running the tests
(e.g., localhost).


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


Debugging tests
-------------------------

If tests that look like they should be working are failing, here are some things to try:

* **Run in Non-headless browser mode.** If you are running in headless browser mode, try running the tests
  in non-headless browser mode to see if you can see an error displayed in the browser.
  If an error message goes away before you
  are able to read it, you can put in a wait statement (e.g., "And I wait for 10 seconds") in
  the feature file, or a "sleep(10);" statement in PHP code,
  so that you have time to read the message. Note that there have been several cases where tests
  have failed in headless mode, but then work in non-headless mode, so this approach will not always work.
  One of the reasons for this has been related to REDCap CSRF tokens (see below for more information).
  Additional reasons this can happen are also discussed below.

* **Add wait statements.** Sometimes errors occur because an access of a page occurs before the page is
  fully updated. To fix this issue, a wait statement (e.g., "And I wait for 4 seconds") can be added in the
  feature file before the statement accessing the page that causes the error. If the issue occurs in PHP
  code, then a sleep statement can be added, for example, "sleep(2);" to wait for 2 seconds.

* **Check XPath expressions.** Some of the custom steps that have been defined use XPath expressions to specify
  elements on web pages. There have been inconsistencies in how these work between headless and non-headless
  browser modes, and between newer and older versions of the web testing software dependencies. There have been
  cases where an XPath expression was working in both headless and non-headless browser modes, but then after
  an update of the web test software dependencies, quit working for the headless mode. The fix for this has
  generally been to specify a more nested element (e.g., changing "td[1]" to
  "td[1]/a" for clicking on a link in a table).

* **Add REDCap CSRF tokens.** REDCap's CSRF (Cross-Site Request Forgery) token should be added automatically to forms,
  but in certain cases it needs to be added explicitly to the form for the automated tests to work.
  This can be done by adding a statement like the following within the form:

        <input type="hidden" name="redcap_csrf_token" value="<?php echo $module->getCsrfToken(); ?>"/>

* **Modify the browser options.** Modifying the options used when the Chrome browser is started has fixed some issues.
  The following option fixed an issue that was only occurring in headless mode for a case when a new page/tab was
  opened:

        --disable-popup-blocking

* **Run tests in isolation.** If you run the tests more then once in too short a time period, the different test
  runs may interfere with each other. This is particularly true for the tests that schedule ETL jobs,
  which may run up to about an hour after the tests are run. If you are having trouble with any of the
  scheduling tests, try to make sure the tests run completely finish before running additional tests.

Test E-mails
------------------------

If the ETL servers for the web tests are configured correctly, and the web tests run successfully, e-mails with the
following subjects should be sent to the specified target for the e-mails:

* REDCap-ETL Access Request
* REDCap-ETL Module web test 1/3: schedule ETL configuration
* REDCap-ETL Module web test 2/3: run workflow on remote server
* REDCap-ETL Module web test 3/3: schedule workflow on remote server

Note that the e-mails sent from scheduled jobs may take a while (up to about an hour) to be sent.

Viewing the test coverage data
-------------------------------

Combine the coverage data:

    php combine_coverage.php

Open the following file with a web browser:

    tests/web/coverage/index.php

You can add the unit test coverage data by executing the following command in the top-level module directory:

    XDEBUG_MODE=coverage
    export XDEBUG_MODE
    ./dev/bin/phpunit --coverage-php tests/web/coverage-data/coverage.unit

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


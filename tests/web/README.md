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
    * the project is approved for REDCap-ETL use (by the non-admin user)
    * the project has no ETL configurations

4. Install Composer if you don't already have it, and run the following command in the tests/web directory:

    composer install

5. Run the following command in the top-level web tests directory:

    cp config-example.ini config.ini

6. Edit the config.ini file created above, and enter appropriate values for properties


Setup each time before tests are run
---------------------------------------

Run chrome browser setting ports as shown below (running Chrome for use with DMore chromebrowser):

    chrome --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222

Or, in headless mode (this runs faster, but you won't see the browser running):

    chrome --disable-gpu --headless --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222

Running the tests
----------------------

You can use the following commands in the top-level web tests directory (tests/web) to run the web tests:

    ./vendor/bin/behat
    ./vendor/bin/behat -f progress      # just prints summary of results
    ./vendor/bin/behat <path-to-feature-file>    # for testing a single feature file


Other commands
----------------------

See the definition expressions:

    ./vendor/bin/behat -dl


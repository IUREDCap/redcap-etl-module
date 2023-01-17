<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

Developer Guide
===================

Directory Structure
-----------------------

* __classes/__ - PHP classes other than the main module class
* config.json - module configuration file
* docgen.php - script for generating the external module version of the transformation rules
    guide from the REDCap-ETL version
* __dev/__ - develpment dependencies, which are NOT committed to Git
* __docs/__ - documents
* README.md - module description and usage requirements
* RedCapEtlModule.php - main module class
* __resources/__ - CSS, image, and JavaScript files
* __tests/__ - test files
    * __unit/__ - unit tests
    * __web/__ - web tests (that access a running instance of the module)
* __vendor/__ - production dependencies, which are committed to Git
* __web/__ - user web pages
    * __admin/__ - admin web pages

Updating Dependencies
--------------------------

__Production Dependencies__

To avoid requiring Composer to be run when the module is installed, dependencies are committed to Git, however,
only the non-development dependencies should be committed to Git. The non-development dependencies are stored
in the standard __vendor/__ directory.

To check for out of date dependencies, use:

    composer outdated --direct

To update the production dependencies update the composer.json file with the new dependency version
numbers and run the following command:

    composer update

__Development Dependencies__

Development depedencies are stored in the __dev/__ directory and are NOT committed to Git. They are managed
using the __dev-composer.json__ configuration file.

To install and update the development dependencies, which will be stored in directory __dev/__, use the following
commands in the top-level directory:

    COMPOSER=dev-composer.json composer install
    COMPOSER=dev-composer.json composer update

__Automated Web Tests Dependencies__

There are also separate dependencies (not committed to Git) that are used for the automated web tests.
The configuration file for these dependencies is:

    tests/web/composer.json

And the dependencies are stored in the following directory:

    tests/web/vendor


Coding Standards Compliance
-----------------------------

The REDCap-ETL external module follows these PHP coding standards, except where
prevented from following them by REDCap:

* [PSR-1: Basic Coding Standard](http://www.php-fig.org/psr/psr-1/)
* [PSR-2: Coding Style Guide](http://www.php-fig.org/psr/psr-2/)
* [PSR-4: Autoloader](http://www.php-fig.org/psr/psr-4/)
* Lower camel case variable names, e.g., $primaryKey


To check for coding standards compliance, enter the following command in the top-level directory:

    ./dev/bin/phpcs -n
    
The "-n" option eliminated warnings. The configuration for phpcs is in file __phpcs.xml__ in the top-level directory.


Static Code Analyzer
--------------------------

Starting with version 2.0.2, the Vimeo Psalm scanner was added to the development dependencies.
This scanner is a static code analyzer, so it
does not require a running instance of the REDCap-ETL external module to work.
This scanner has been adopted by Vanderbilt
as a security scanner for REDCap external module submissions. To scan the REDCap-ETL external module, use the following
command in the top-level directory of the project:

    ./dev/bin/psalm

A configuration file (psalm.xml) has been created that will cause Psalm to run in security analysis mode.


REDCap External Module Security Scan
-----------------------------------------------

As of sometime around December 2022, a new security script was added to REDCap that needs
to be run successfully on external modules before they can be submitted.
To run this command use (as of January 2023):

Before the command is run, you should remove the following directories (which are not committed to Git),
so that they will not show up in the security scan results:

    dev/
    tests/web/vendor

Before you remove the tests/web/vendor directory, it is important that you turn off test coverage statistics
collection, because that will use scripts in this directory, which will not function with the tests/web/vendor
directory removed.

Command to scan module:

    <redcap-root>/bin/scan <path-to-module>

For example:

    /var/www/html/redcap/bin/scan /var/www/html/redcap/modules/redcap-etl-module_v1.4.0

Note:

* any errors that show up for the vendor or tests/web/vendor directories can be ignored, because these
    directories will not be committed to GitHub
* the ExternalModules directory under the REDCap version directory you are using
    must be writable by the user running the scan command for the scan to work

To see the latest information on scanning, in the __Cotrol Center__ in REDCap:

* access __External Modules -> Manage__
* click on __Module Security Scanning__



Automated Tests
--------------------------
To run the unit tests, enter the following command in the top-level directory:

    ./dev/bin/phpunit
    
The configuration for phpunit is in file __phpunit.xml__ in the top-level directory.

The module also has web tests that access a running REDCap-ETL external module. For
information on running these tests, see the file:

[tests/web/README.md](../tests/web/README.md)

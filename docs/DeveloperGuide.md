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
* __dependencies/__ - production dependencies
* docgen.php - script for generating the external module version of the transformation rules
    guide from the REDCap-ETL version
* __docs/__ - documents
* README.md - module description and usage requirements
* RedCapEtlModule.php - main module class
* __resources/__ - CSS, image, and JavaScript files
* __tests/__ - test files
    * __unit/__ - unit tests
    * __web/__ - web tests (that access a running instance of the module)
* __vendor/__ - development dependencies (if these have been installed)
* __web/__ - user web pages
    * __admin/__ - admin web pages

Updating Dependencies
--------------------------
To avoid requiring Composer to be run when the module is installed, the non-development dependencies
are copied to the __dependencies/__ directory, and this directory is committed to Git.
To update the contents of this directory, the following commands
can be used from the top-level directory:

    composer update
    composer install --no-dev
    rm -rf dependencies
    mv vendor dependencies
    composer install


To check for out of date dependencies, use:

    composer outdated --direct


Coding Standards Compliance
-----------------------------

The REDCap-ETL external module follows these PHP coding standards, except where
prevented from following them by REDCap:

* [PSR-1: Basic Coding Standard](http://www.php-fig.org/psr/psr-1/)
* [PSR-2: Coding Style Guide](http://www.php-fig.org/psr/psr-2/)
* [PSR-4: Autoloader](http://www.php-fig.org/psr/psr-4/)
* Lower camel case variable names, e.g., $primaryKey


To check for coding standards compliance, enter the following command in the top-level directory:

    ./vendor/bin/phpcs -n
    
The "-n" option eliminated warnings. The configuration for phpcs is in file __phpcs.xml__ in the top-level directory.


Static Code Analyzer
--------------------------

Starting with version 2.0.2, the Vimeo Psalm scanner was added to the development dependencies.
This scanner is a static code analyzer, so it
does not require a running instance of the REDCap-ETL external module to work.
This scanner has been adopted by Vanderbilt
as a security scanner for REDCap external module submissions. To scan the REDCap-ETL external module, use the following
command in the top-level directory of the project:

    ./vendor/bin/psalm

A configuration file (psalm.xml) has been created that will cause Psalm to run in security analysis mode.

Automated Tests
--------------------------
To run the unit tests, enter the following command in the top-level directory:

    ./vendor/bin/phpunit
    
The configuration for phpunit is in file __phpunit.xml__ in the top-level directory.

The module also has web tests that access a running REDCap-ETL external module. For
information on running these tests, see the file:

[tests/web/README.md](../tests/web/README.md)

Developer Guide
===================


Updating REDCap-ETL
-----------------------

REDCap-ETL is included as a Git subtree.

To see the current version number of REDCap-ETL being used, run the following command from the top-level directory of
the external module:

    cat redcap-etl/src/Version.php

To update REDCap-ETL to a new version, use, for example:

    git subtree pull --squash --prefix redcap-etl https://github.com/IUREDCap/redcap-etl/ tags/0.6.0 -m 'Updated subtree redcap-etl to 0.6.0'


Updating Dependencies
--------------------------
To avoid requiring Composer to be run when the module is installed, the non-development dependencies
are copied to the __dependencies/__ directory, and this directory is committed to Git.
To update the contents of this directory, the following commands
can be used from the top-level directory:

    composer install --no-dev
    rm -rf dependencies
    mv vendor dependencies


Coding Standards Compliance
-----------------------------

To check for coding standards compliance, enter the following command in the top-level directory:

    ./vendor/bin/phpcs
    
The configuration for phpcs is in file __phpcs.xml__ in the top-level directory.



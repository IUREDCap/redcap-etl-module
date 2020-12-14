<!-- =================================================
Copyright (C) 2019 The Trustees of Indiana University
SPDX-License-Identifier: BSD-3-Clause
================================================== -->

REDCap-ETL External Module
=================================

The REDCap-ETL (Extract Transform Load) external module:

1. Extracts data from REDCap
2. Transforms the extracted data based on user-specified transformation rules
3. Loads the transformed data into a database

![REDCap-ETL](./resources/redcap-etl.png)


REDCap-ETL supports:

* running ETL processes on demand
* scheduling ETL processes to run automatically on a daily or weekly basis


---

Requirements for using REDCap-ETL
--------------------------------------

To use REDCap-ETL on a project, you need the following:

* **Project Design and Setup Permission**. In general, you need this REDCap user
    right to access external modules for a project,
    and it is also required for REDCap-ETL.
* **Data Export Permission**. REDCap "Data Exports" user right of
    "Full Data Set" for the project.
* **No Data Access Group (DAG)**. You need to have access to
    all of the project's fields, so you cannot 
    belong to a DAG.
* **REDCap API Token**. REDCap-ETL uses the REDCap API (Application
    Programming Interface) to extract data from REDCap, so you need to have
    a REDCap API token for the project with export permission. You can either use your
    own API token, or select the API token of another of the project's users who
    has "Full Data Set" export rights and is not in a data access group.
    To be able to request an API token with export
    permission, you need to have the REDCap user right "API Export".
* **Database Account.** An account for a database where the data can be loaded.
    The REDCap-ETL external module currently supports the following
    databases: MySQL, PostgreSQL and SQL Server.
    The database account has to have at least the following permissions:
    * SELECT
    * INSERT
    * CREATE
    * DROP
    * CREATE VIEW
* **REDCap Server PHP Database Extensions**.
    If you use a database other than MySQL to load your extracted and transformed data, the appropriate
    PHP extension(s) for the database will need to be enabled in the version of PHP used by your
    REDCap server (assuming you are using the default "embedded" REDCap-ETL server that comes with the
    REDCap-ETL external module). For example, for PostgreSQL, the pgsql extension will need to be
    enabled in PHP.


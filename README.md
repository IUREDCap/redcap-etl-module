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
* **Data Export Permission**. REDCap "Data Exports" user right of at least
    "De-identified" for the project.
* **REDCap API Token**. REDCap-ETL uses the REDCap API (Application
    Programming Interface) to extract data from REDCap, so you need to have
    A REDCap API token for the project with export permission. You can either use your
    own API token, or select the API token of another of the project's users who
    has the same "Data Exports" user right as you.
* **MySQL Database Account.** An account for a MySQL database where the data can be loaded. The account has to
    have at least the following permissions:
    * SELECT
    * INSERT
    * CREATE
    * DROP
    * CREATE VIEW



-
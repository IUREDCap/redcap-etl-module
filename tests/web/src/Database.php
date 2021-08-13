<?php
#-------------------------------------------------------
# Copyright (C) 2021 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule\WebTests;

/**
 * Class for accessing the load database for the test project.
 */
class Database
{
    const ETL_CONFIG_NAME = 'behat';

    private $db;

    public function __construct()
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);
        $etlConfig = $testConfig->getEtlConfig(self::ETL_CONFIG_NAME);

        $dbHost     = $etlConfig['db_host'];
        $dbName     = $etlConfig['db_name'];
        $dbUser     = $etlConfig['db_user'];
        $dbPassword = $etlConfig['db_password'];

        $this->db = new \PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPassword);
    }

    function getNumberOfTableRows($tableName)
    {
        $query = 'SELECT COUNT(*) FROM `' . $tableName . '`';
        $statement = $this->db->query($query);
        $data = $statement->fetchAll(\PDO::FETCH_NUM);
        return $data[0][0];
    }

    function getTableColumnValues($tableName, $columnName)
    {
        $query = 'SELECT `' . $columnName . '` FROM `' . $tableName . '`';
        $statement = $this->db->query($query);
        $data = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $data = array_column($data, $columnName);
        return $data;
    }

    function getTableColumnUniqueValues($tableName, $columnName)
    {
        $query = 'SELECT DISTINCT `' . $columnName . '` FROM `' . $tableName . '`';
        $statement = $this->db->query($query);
        $data = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $data = array_column($data, $columnName);
        return $data;
    }
}

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Schema;

/**
 * Schema is used for holding information about dynamically created
 * tables/fields, as well as rows of data assigned to those tables.
 *
 * These tables and rows know about a datastore (such as a relational
 * database or flat file) where their data can be persisted
 *
 */
class Schema
{

    private $tables = array();
    private $rootTables = array();
    
    /** @var LookupTable table for mapping multiple choice codes to values */
    private $lookupTable = null;
    
    private $dbLogTable      = null;
    private $dbEventLogTable = null;

    public function __construct()
    {
        return true;
    }

    /**
     * Adds the specified table to the schema.
     *
     * @param Table $table the table to add to the schema.
     */
    public function addTable($table)
    {
        // Add table to list of all tables
        array_push($this->tables, $table);

        // If it is a root table, add to list of root tables
        if (in_array(RowsType::ROOT, $table->rowsType)) {
            array_push($this->rootTables, $table);
        }
    }

    /**
     * Get the tables in top-down, depth-first order, i.e., parents before children
     */
    public function getTablesTopDown()
    {
        $tables = array();
        foreach ($this->rootTables as $table) {
            array_push($tables, $table);
            $descendants = $table->getDescendantsDepthFirst();
            $tables = array_merge($tables, $descendants);
        }
        return $tables;
    }

    /**
     * Returns tables always listing children before parents. This is useful
     * for table deletion so that foreign key constraints won't be violated.
     */
    public function getTablesBottomUp()
    {
        $tables = $this->getTablesTopDown();
        return array_reverse($tables);
    }


    /**
     * Gets all the tables in the schema.
     *
     * @return array an array of Table objects for all the
     *    tables in the schema.
     */
    public function getTables()
    {
        return($this->tables);
    }

    /**
     * Gets all the root (i.e., non-child) tables in the schema.
     *
     * @return array an array of Table objects for all the
     *     root tables in the schema.
     */
    public function getRootTables()
    {
        return($this->rootTables);
    }

    /**
     * Gets the table with the specified table name.
     *
     * @param string $tableName the name of the table to return.
     * @return mixed if the table is found, the Table object for it is
     *     returned, otherwise the table name that was specified is
     *     returned.
     */
    public function getTable($tableName)
    {
        foreach ($this->tables as $table) {
            if (0 == strcmp($table->name, $tableName)) {
                return($table);
            }
        }

        // If the table is not found, return the tableName
        return($tableName);
    }


    /**
     * Gets the lookup table.
     *
     * @return LookupTable table that maps multiple choice coded values to labels.
     */
    public function getLookupTable()
    {
        return $this->lookupTable;
    }

    /**
     * Sets the lookup table for the database.
     *
     * @param Table $lookupTable the lookup table for the database.
     */
    public function setLookupTable($lookupTable)
    {
        $this->lookupTable = $lookupTable;
    }


    public function getDbLogTable()
    {
        return $this->dbLogTable;
    }

    public function setDbLogTable($dbLogTable)
    {
        $this->dbLogTable = $dbLogTable;
    }


    public function getDbEventLogTable()
    {
        return $this->dbEventLogTable;
    }

    public function setDbEventLogTable($dbEventLogTable)
    {
        $this->dbEventLogTable = $dbEventLogTable;
    }


    /**
     * Returns a string representation of the schema.
     *
     * @return string a human-readable string representation of the
     *     schema.
     */
    public function toString($indent = 0)
    {
        $in = str_repeat(' ', $indent);
        $string = '';
        $string .= "${in}Number of tables: ".count($this->tables)."\n";
        $string .= "${in}Number of root tables: ".count($this->rootTables)."\n";
        $string .= "\n${in}Root tables\n";
        foreach ($this->rootTables as $table) {
            $string .= $table->toString($indent + 4)."\n";
        }
        $string .= "\nTables\n";
        foreach ($this->tables as $table) {
            $string .= $table->toString($indent + 4)."\n";
        }

        return $string;
    }
}

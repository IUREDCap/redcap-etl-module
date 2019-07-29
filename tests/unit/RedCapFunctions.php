<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

class RedCapFunctions
{
    private static $dbQueryResults = array();
    private static $queries = array();

    public function addDbQueryResult($query, $isException, $value)
    {
        self::$dbQueryResults[$query] = [$isException, $value];
    }

    public function getDbQueryResult($query)
    {
        $result = null;
        if (array_key_exists($query, self::$dbQueryResults)) {
            $result = self::$dbQueryResults[$query];
        }
        return $result;
    }

    public function addQuery($query)
    {
        self::$queries[] = $query;
    }

    public function getLastQuery()
    {
        return array_slice(self::$queries, -1)[0];
    }
}

#------------------------------------------------------------------------------
# Overridden REDCap functions
#------------------------------------------------------------------------------

function db_query($query)
{
    RedCapFunctions::addQuery($query);
    $queryResult = RedCapFunctions::getDbQueryResult($query);
    if (isset($queryResult) && is_array($queryResult)) {
        if ($queryResult[0]) {
            throw new \Exception($queryResult[1]);
        } else {
            $result = $queryResult[1];
        }
    } else {
        $result = null;
    }
    return $result;
}

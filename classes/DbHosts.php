<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

/**
 * Database hosts class used for representing
 * possible or allowed database hosts.
 */
class DbHosts implements \JsonSerializable
{
    /** @var array map from database host name to description */
    private $dbHosts;

    public function __construct()
    {
        $this->dbHosts = array();
    }

    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function getDbHosts()
    {
        return $this->dbHosts;
    }

    public function addDbHost($dbHostName, $dbHostDescription)
    {
        array_push($this->dbHosts, array($dbHostName, $dbHostDescription));
    }
    
    public function removeDbHost($dbHostName)
    {
        for ($i = 0; $i < count($this->dbHosts); $i++) {
            if ($dbHosts[$i][0] == $dbHostName) {
                unset($this->dbHosts[$i]);
            }
        }
    }

    public function fromJson($json)
    {
        if (!empty($json)) {
            $values = json_decode($json, true);
            foreach (get_object_vars($this) as $var => $value) {
                $this->$var = $values[$var];
            }
        }
    }

    public function toJson()
    {
        $json = json_encode($this);
        return $json;
    }
}

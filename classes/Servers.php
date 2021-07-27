<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

class Servers implements \JsonSerializable
{
    private $servers;

    public function __construct()
    {
        $this->servers = array();
        $this->servers[ServerConfig::EMBEDDED_SERVER_NAME] = 1;
    }

    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function getServers()
    {
        $servers = array_keys($this->servers);
        sort($servers);
        return $servers;
    }

    public function addServer($name)
    {
        if (array_key_exists($name, $this->servers)) {
            throw new \Exception('Server "' . $name . '" already exists.');
        }
        ServerConfig::validateName($name);
        $this->servers[$name] = 1;
    }

    public function removeServer($serverName)
    {
        unset($this->servers[$serverName]);
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

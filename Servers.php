<?php

namespace IU\RedCapEtlModule;

class Servers implements \JsonSerializable
{
    private $servers;

    public function __construct()
    {
        $this->servers = array();
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

    public function addServer($serverName)
    {
        $this->servers[$serverName] = 1;
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

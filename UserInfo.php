<?php

namespace IU\RedCapEtlModule;


/**
 * Top-level class for storing user information.
 */
class UserInfo implements \JsonSerializable
{
    private $username;

    /** @var array where keys are configuration names for the user */
    private $configs;

    public function __construct($username)
    {
        $this->username = $username;
        $this->configs = array();
    }

    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function getConfigurationNames()
    {
        $configNames = array_keys($this->configs);
        sort($configNames);
        return $configNames;
    }

    public function addConfigName($configName)
    {
        $this->configs[$configName] = 1;
    }
    
    public function removeConfigName($configName)
    {
        unset($this->configs[$configName]);
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

    public function getUsername()
    {
        return $this->username;
    }
}


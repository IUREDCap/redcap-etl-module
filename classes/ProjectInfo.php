<?php

namespace IU\RedCapEtlModule;

/**
 * Top-level class for storing project information.
 */
class ProjectInfo implements \JsonSerializable
{
    /** @var array where keys are configuration names for the project */
    private $configs;

    public function __construct()
    {
        $this->configs = array();
    }

    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function hasConfigName($configName)
    {
        $has = false;
        if (array_key_exists($configName, $this->configs)) {
            $has = true;
        }
        return $has;
    }
    
    public function getConfigNames()
    {
        $configNames = array_keys($this->configs);
        sort($configNames);
        return $configNames;
    }

    public function addConfigName($configName)
    {
        Configuration::validateName($configName);
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
}

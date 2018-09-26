<?php

namespace IU\RedCapEtlModule;


class ServerConfig implements \JsonSerializable
{
    private $name;
    private $serverAddress; # address of REDCap-ETL server
    private $authMethod;
    private $username;
    private $password;
    private $sshKeyFile;
    private $configDir;
    private $etlCommand;  # full path of command to run on REDCap-ETL server

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function fromJson($json)
    {
        if (!empty($json)) {
            $object = json_decode($json);
            foreach (get_object_vars($this) as $var => $value) {
                $this->$var = $object->$var;
            }
        }
    }

    public function toJson()
    {
        $json = json_encode($this);
        return $json;
    }

    public function getName()
    {
        return $this->name;
    }
}

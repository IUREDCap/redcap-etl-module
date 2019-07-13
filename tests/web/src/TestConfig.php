<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule\WebTests;

/**
 * Test Configuration class. Instances of this class are created
 * using a .ini configuration file.
 */
class TestConfig
{
    private $redCap;
    private $admin;
    private $user;
    private $etlConfigs; 
    private $serverConfigs;

    /**
     * @param string $file path to file containing test configuration.
     */
    public function __construct($file)
    {
        $this->etlConfigs = array();
        $this->serverConfigs = array();
        $processSections = true;
        $properties = parse_ini_file($file, $processSections);

        foreach ($properties as $name => $value) {
            $matches = array();
            if ($name === 'redcap') {
                $this->redCap = $value;
            } elseif ($name === 'admin') {
                $this->admin = $value;
            } elseif ($name === 'user') {
                $this->user = $value;
            } elseif (preg_match('/^etl_config_(.*)$/', $name, $matches) === 1) {
                $configName = $matches[1];
                $this->etlConfigs[$configName] = $value;
            } elseif (preg_match('/^server_config_(.*)$/', $name, $matches) === 1) {
                $configName = $matches[1];
                $this->serverConfigs[$configName] = $value;
            }
        }
    }

    public function getRedCap()
    {
        return $this->redCap;
    }

    public function getAdmin()
    {
        return $this->admin;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getEtlConfigs()
    {
        return $this->etlConfigs;
    }

    public function getEtlConfig($name)
    {
        return $this->etlConfigs[$name];
    }

    public function setEtlConfig($name, $etlConfig)
    {
        $this->etlConfigs[$name] = $etlConfig;
    }

    public function getServerConfigs()
    {
        return $this->serverConfigs;
    }

    public function getServerConfig($name)
    {
        return $this->serverConfigs[$name];
    }

    public function setServerConfig($name, $serverConfig)
    {
        $this->serverConfigs[$name] = $serverConfig;
    }
}

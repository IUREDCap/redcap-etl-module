<?php

namespace ExternalModules;

/**
 * Mock class for REDCap's AbstractExternalModule class, which is used for testing.
 */
abstract class AbstractExternalModule
{
    protected $log;
    protected $index;

    public function __construct()
    {
        $this->log = array();
        $this->index = 0;
    }

    public function log($message, $params)
    {
        $this->index++;
        $this->log[$this->index] = array('message' => $message, 'params' => $params);
        return $this->index;
    }

    public function getLogEntry($index)
    {
        return $this->log[$index];
    }
}

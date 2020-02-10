<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

/**
 * Mock of RedCapEtlModule
 */
class ModuleMock
{
    private $log;
    private $index;

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

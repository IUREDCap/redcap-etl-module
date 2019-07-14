<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

require_once __DIR__.'/../../vendor/autoload.php';

$codeCoverageId = null;
if (array_key_exists('code-coverage-id', $_COOKIE)) {
    $codeCoverageId = $_COOKIE['code-coverage-id'];
}

if (!empty($codeCoverageId)) {
    $coverage = new \SebastianBergmann\CodeCoverage\CodeCoverage();

    # Included files and directories
    $coverage->filter()->addFileToWhitelist(__DIR__.'/../../RedCapEtlModule.php');
    $coverage->filter()->addDirectoryToWhitelist(__DIR__.'/../../classes');
    $coverage->filter()->addDirectoryToWhitelist(__DIR__.'/../../web');

    # Excluded files
    $coverage->filter()->removeFileFromWhitelist(__DIR__.'/../../classes/EtlExtRedCapProject.php');
    $coverage->filter()->removeFileFromWhitelist(__DIR__.'/../../web/test.php');

    $coverage->start($codeCoverageId);
}

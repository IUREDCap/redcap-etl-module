<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

require_once __DIR__.'/vendor/autoload.php';

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Driver\Selector;

$codeCoverageId = null;
if (array_key_exists('code-coverage-id', $_COOKIE)) {
    $codeCoverageId = $_COOKIE['code-coverage-id'];
}


if (!empty($codeCoverageId)) {
    $filter = new Filter;

    # Included main external module file
    $filter->includeFile(__DIR__.'/../../RedCapEtlModule.php');

    # Add applicable files in the classes directory
    $classesFiles = glob(__DIR__.'/../../classes/*.php');
    #foreach ($classesFiles as $key => $value) {
    #    if (preg_match('/EtlExtRedCapProject.php$/', $value)) {
    #        unset($classesFiles[$key]);
    #    }
    #}
    $classesFiles = array_Values($classesFiles);
    $filter->includeFiles($classesFiles);

    # Add applicable files in the web directory
    $webFiles = glob(__DIR__.'/../../web/*.php');
    foreach ($webFiles as $key => $value) {
        if (preg_match('/test.php$/', $value)) {
            unset($webFiles[$key]);
        }
    }
    $webFiles = array_values($webFiles);
    $filter->includeFiles($webFiles);

    # Add PHP files in the web/admin directory
    $filter->includeFiles(glob(__DIR__.'/../../web/admin/*.php'));

    # Excluded files
    # $filter->excludeFile(__DIR__.'/../../classes/EtlExtRedCapProject.php');
    # $filter->excludeFile(__DIR__.'/../../web/test.php');


    $selector = new Selector;

    $coverage = new CodeCoverage($selector->forLineCoverage($filter), $filter);

    $coverage->start($codeCoverageId);
}

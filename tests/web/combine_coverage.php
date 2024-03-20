#!/usr/bin/php
<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

require_once __DIR__.'/vendor/autoload.php';

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Driver\Selector;


$files = glob(__DIR__.'/coverage-data/coverage.*');

#------------------------------------------------------

$filter = new Filter;

# Included main external module file
$filter->includeFile(__DIR__.'/../../RedCapEtlModule.php');

# Add applicable files in the classes directory
$classesFiles = glob(__DIR__.'/../../classes/*.php');
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

$selector = new Selector;

# ------------------------------------------------------
$combinedCoverage = new CodeCoverage($selector->forLineCoverage($filter), $filter);

$count = 0;
foreach ($files as $file) {
    $coverage = require $file;
    $combinedCoverage->merge($coverage); 
    $count++;
}

$writer = new \SebastianBergmann\CodeCoverage\Report\Html\Facade();
$writer->process($combinedCoverage, __DIR__.'/coverage');

print "{$count} files combined.\n";


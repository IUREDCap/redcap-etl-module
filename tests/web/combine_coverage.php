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

# Included files and directories
$filter->includeFile(__DIR__.'/../../RedCapEtlModule.php');
$filter->includeDirectory(__DIR__.'/../../classes');
$filter->includeDirectory(__DIR__.'/../../web');

# Excluded files
$filter->excludeFile(__DIR__.'/../../classes/EtlExtRedCapProject.php');
$filter->excludeFile(__DIR__.'/../../web/test.php');


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


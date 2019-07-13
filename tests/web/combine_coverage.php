<?php

require_once __DIR__.'/../../vendor/autoload.php';

$files = glob(__DIR__.'/coverage-data/coverage.*');

$combinedCoverage = new \SebastianBergmann\CodeCoverage\CodeCoverage();

$count = 0;
foreach ($files as $file) {
    require $file;
    $combinedCoverage->merge($coverage); 
    $count++;
}

$writer = new \SebastianBergmann\CodeCoverage\Report\Html\Facade();
$writer->process($combinedCoverage, __DIR__.'/coverage');

print "{$count} files combined.\n";


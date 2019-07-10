<?php

require_once __DIR__.'/../../vendor/autoload.php';

$files = glob(__DIR__.'/coverage-data/coverage.*');

$count = 0;
foreach ($files as $file) {
    $result = unlink($file);
    if ($result === true) {
        $count++;
    } else {
        print "WARNING: unable to delete file {$file}\n";
    }
}

print "{$count} files deleted.\n";

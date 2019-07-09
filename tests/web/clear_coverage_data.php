<?php

require_once __DIR__.'/../../vendor/autoload.php';

$files = glob(__DIR__.'/coverage-data/coverage.*');

foreach ($files as $file) {
    unlink($file);
}


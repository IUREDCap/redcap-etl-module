<?php

require_once __DIR__.'/../../vendor/autoload.php';

use SebastianBergmann\CodeCoverage\CodeCoverage;

$test = $_COOKIE['test'];

if ($test === 'web-test') {
    $coverage = new CodeCoverage;
    $coverage->filter()->addDirectoryToWhitelist(__DIR__.'/../..');
    $coverage->filter()->addDirectoryToWhitelist(__DIR__.'/../classes');
    $coverage->filter()->addDirectoryToWhitelist(__DIR__.'/../web');
    $coverage->start($test);
}

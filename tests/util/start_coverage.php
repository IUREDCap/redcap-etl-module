<?php

require_once __DIR__.'/../../vendor/autoload.php';

$test = null;
if (array_key_exists('test', $_COOKIE)) {
    $test = $_COOKIE['test'];
}

if ($test === 'web-test') {
    $coverage = new \SebastianBergmann\CodeCoverage\CodeCoverage;
    $coverage->filter()->addFileToWhitelist(__DIR__.'/../../RedCapEtlModule.php');
    $coverage->filter()->addDirectoryToWhitelist(__DIR__.'/../../classes');
    $coverage->filter()->addDirectoryToWhitelist(__DIR__.'/../../web');
    $coverage->start($test);
}

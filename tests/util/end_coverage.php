<?php

require_once __DIR__.'/../../vendor/autoload.php';

use SebastianBergmann\CodeCoverage\CodeCoverage;

$test = $_COOKIE['test'];

if ($test === 'web-test') {
    $coverage->stop();

    $writer = new \SebastianBergmann\CodeCoverage\Report\Clover;
    $writer->process($coverage, __DIR__'/../coverage/clover.xml');

    $writer = new \SebastianBergmann\CodeCoverage\Report\Html\Facade;
    $writer->process($coverage, __DIR__.'/../coverage/code-coverage-report');
}

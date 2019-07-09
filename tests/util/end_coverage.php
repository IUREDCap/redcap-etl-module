<?php

if (isset($tests) && $test === 'web-test') {
    if (isset($coverage)) {
        $coverage->stop();

        #$writer = new \SebastianBergmann\CodeCoverage\Report\Clover;
        #$writer->process($coverage, __DIR__.'/../coverage/clover.xml');

        #$writer = new \SebastianBergmann\CodeCoverage\Report\Html\Facade;
        #$writer->process($coverage, __DIR__.'/../coverage/code-coverage-report');
    }
}

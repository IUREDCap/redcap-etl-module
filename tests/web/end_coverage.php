<?php

if (isset($coverage)) {
    $coverage->stop();

    $writer = new \SebastianBergmann\CodeCoverage\Report\PHP();
    $writer->process($coverage, __DIR__.'/coverage-data/coverage.'.uniqid('', true));
}


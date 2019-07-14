<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

if (isset($coverage)) {
    $coverage->stop();

    $writer = new \SebastianBergmann\CodeCoverage\Report\PHP();
    $directory = __DIR__.'/coverage-data/';
    $fileName = 'coverage.'.$codeCoverageId.'.'.uniqid('', true);
    $writer->process($coverage, $directory.$fileName);
}


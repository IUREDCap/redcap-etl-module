#!/usr/bin/php
<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

require_once __DIR__.'/vendor/autoload.php';

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

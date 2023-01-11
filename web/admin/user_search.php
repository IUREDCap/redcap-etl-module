<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

#----------------------------------------------------
# Web service for getting user search results
#----------------------------------------------------

# Check that the user has admin permission
$module->checkAdminPagePermission();


require_once __DIR__ . '/../../dependencies/autoload.php';

use IU\RedCapEtlModule\RedCapDb;

$term = '';
if (array_key_exists('term', $_GET)) {
    $term = trim($_GET['term']);
}

$redCapDb = new RedCapDb();

$users = $redCapDb->getUserSearchInfo($term);

$encodedData = json_encode($users);

# Send back response to web service client
$fh = fopen('php://output', 'w');
fwrite($fh, $encodedData);

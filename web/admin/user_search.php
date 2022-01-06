<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

#---------------------------------------------
# Check that the user has access permission
#---------------------------------------------
$module->checkAdminPagePermission();


require_once __DIR__ . '/../../dependencies/autoload.php';

use IU\RedCapEtlModule\RedCapDb;

$term = '';
if (array_key_exists('term', $_GET)) {
    $term = trim($_GET['term']);
}

$redCapDb = new RedCapDb();

$users = $redCapDb->getUserSearchInfo($term);

$data = $users;

echo json_encode($data);

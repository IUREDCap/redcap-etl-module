<?php

#-------------------------------------------------------
# Copyright (C) 2023 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

#---------------------------------------------
# Get user information for a private server
#---------------------------------------------
$module->checkAdminPagePermission();

require_once __DIR__ . '/../../vendor/autoload.php';

use IU\RedCapEtlModule\Filter;

$result = '';

try {
    $serverName = Filter::sanitizeString($_POST['server_name']);
    $userNames  = $_POST['user_names']; // JSON array

    $newUsers = json_decode($userNames);

    if ($newUsers !== null && is_array($newUsers)) {
        $module->setPrivateServerUsers($serverName, $newUsers);
        $result = ''; // "Users set to: {" . implode(", ", $newUsers) . "}";
    } else {
        $result = "Users unchanged.";
    }
} catch (\Exception $exception) {
    $result = 'ERROR: ' . $exception->getMessage();
}

echo $result;

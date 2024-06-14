<?php

#-------------------------------------------------------
# Copyright (C) 2023 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

//header('Content-Type: application/json; charset=utf-8');

#---------------------------------------------
# Get user information for a private server
#---------------------------------------------
$module->checkAdminPagePermission();

require_once __DIR__ . '/../../vendor/autoload.php';

use IU\RedCapEtlModule\Filter;

$serverName = Filter::sanitizeString($_POST['server_name']);

$users = $module->getPrivateServerUsers($serverName);

$usersInfo = [];
foreach ($users as $user) {
    $userInfo = $module->getUserInfo($user);
    $usersInfo[] = $userInfo;
}

$usersInfoJson = json_encode($usersInfo);

echo $usersInfoJson;

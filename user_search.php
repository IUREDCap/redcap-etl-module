<?php

require_once __DIR__.'/RedCapDb.php'; 

use IU\RedCapEtlModule\RedCapDb;

$term = '';
if (array_key_exists('term', $_GET)) {
    $term = trim($_GET['term']);
}

$redCapDb = new RedCapDb();

$users = $redCapDb->getUserSearchInfo($term);

$data = $users;
#$data = array();
#foreach ($users as $user) {
#    $userData = array('id' => $user['ui_id'], 'value' => $user['username']);
#    array_push($data, $userData);
#}

#$data = [['id' => 0, 'value' => 'test-value-'],['id' => 1, 'value' => 'test2']];

echo json_encode($data);

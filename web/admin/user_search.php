<?php


if (!SUPER_USER) {
    exit("Only super users can access this page!");
}

require_once __DIR__.'/../../dependencies/autoload.php';

use IU\RedCapEtlModule\RedCapDb;

$term = '';
if (array_key_exists('term', $_GET)) {
    $term = trim($_GET['term']);
}

$redCapDb = new RedCapDb();

$users = $redCapDb->getUserSearchInfo($term);

$data = $users;

echo json_encode($data);

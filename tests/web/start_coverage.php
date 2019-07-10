<?php

require_once __DIR__.'/../../vendor/autoload.php';

$codeCoverage = null;
if (array_key_exists('code-coverage', $_COOKIE)) {
    $codeCoverage = $_COOKIE['code-coverage'];
}

if ($codeCoverage === 'web-test') {
    $coverage = new \SebastianBergmann\CodeCoverage\CodeCoverage;

    # Included files and directories
    $coverage->filter()->addFileToWhitelist(__DIR__.'/../../RedCapEtlModule.php');
    $coverage->filter()->addDirectoryToWhitelist(__DIR__.'/../../classes');
    $coverage->filter()->addDirectoryToWhitelist(__DIR__.'/../../web');

    # Excluded files
    $coverage->filter()->removeFileFromWhitelist(__DIR__.'/../../classes/EtlExtRedCapProject.php');
    $coverage->filter()->removeFileFromWhitelist(__DIR__.'/../../web/test.php');

    $coverage->start($codeCoverage);
}

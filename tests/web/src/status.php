<?php
#-------------------------------------------------------
# Copyright (C) 2020 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

#--------------------------------------------------------------
# Status test for web tests configuration and system settings
#--------------------------------------------------------------

require_once(__DIR__.'/../vendor/autoload.php');

use Behat\Mink\Mink;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver;

use IU\RedCapEtlModule\WebTests\EtlServerConfigPage;
use IU\RedCapEtlModule\WebTests\EtlServersPage;
use IU\RedCapEtlModule\WebTests\FeatureContext;
use IU\RedCapEtlModule\WebTests\TestConfig;
use IU\RedCapEtlModule\WebTests\Util;

$configFile = FeatureContext::CONFIG_FILE;
$testConfig = new TestConfig($configFile);
$baseUrl = $testConfig->getRedCap()['base_url'];

$mink = new Mink(array(
        'browser' => new Session(new ChromeDriver('http://localhost:9222', null, $baseUrl))
    ));

$session = $mink->getSession('browser');

$adminUsername = $testConfig->getAdmin()['username'];
$username = $testConfig->getUser()['username'];
$testProjectTitle = $testConfig->getUser()['test_project_title'];


if (empty($adminUsername)) {
    print "No admin username defined in test configuration file \"{$configFile}\".\n";
    exit(1);
} elseif (empty($username)) {
    print "No username defined in test configuration file \"{$configFile}\".\n";
    exit(1);
}

print "\n";
print "Test admin username: \"{$adminUsername}\"\n";
print "Test user username:  \"{$username}\"\n";
print "Test project title:  \"{$testProjectTitle}\"\n";
print "\n";


#-------------------------------------
# Test user login
#-------------------------------------
Util::loginAsUser($session);
$page = $session->getPage();
$text = $page->getText();

print "User login: ";
if (preg_match("/Logged in as {$username}/", $text) === 1) { 
    print "OK\n";
} else {
    print "ERROR\n";
    exit(1);
}

#------------------------------------------------------------
# Check that test REDCap ETL project is set up correctly
#------------------------------------------------------------
Util::selectTestProject($session);
$page = $session->getPage();
$page->clickLink('REDCap-ETL');
$text = $page->getText();
print "Test Project Setup: ";
if (preg_match("/ETL Configurations/", $text)  === 1) {
    print "OK\n";
} else {
    print "ERROR\n";
    exit(1);
}

#--------------------------
# Test logout
#--------------------------
$page->clickLink('Log out');
$text = $page->getText();
print "User Logout: ";
if (preg_match("/Loggen in as {$username}/", $text) === 1) {
    print "ERROR\n";
    exit(1);
} else {
    print "OK\n";
}

#--------------------------------------
# Test admin login
#--------------------------------------
print "\n";
Util::loginAsAdmin($session);
$page = $session->getPage();

$link = $page->findLink('Control Center');
print "Admin Login: ";
if (empty($link)) {
    print "ERROR\n";
    exit(1);
}
else {
    print "OK\n";
}

$page->clickLink('Control Center');
$page->clickLink('REDCap-ETL');

print "REDCap-ETL admin interface access: ";
$text = $page->getText();
if (preg_match("/REDCap-ETL Admin/", $text) === 1) {
    print "OK\n";
} else {
    print "ERROR\n";
    exit(1);
}

$page->clickLink("ETL Servers");

EtlServersPage::followServer($session, "(embedded server)");

$accessLevel = EtlServerConfigPage::getAccessLevel($session);
print "Embedded server public access: ";
if ($accessLevel === 'public') {
    print "OK\n";
} else {
    print "ERROR - access level is \"{$accessLevel}\"\n";
    exit(1);
}


print "Embedded server is active: ";
$isActive = EtlServerConfigPage::isActive($session);
if ($isActive) {
    print "OK\n";
} else {
    print "ERROR\n";
    exit(1);
}

print "\n";

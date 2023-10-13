<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule\WebTests;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Class for interacting with the user "Configure" page for editing and ETL configuration.
 */
class ConfigurePage
{
    public static function configureConfiguration($session, $configName, $emailSubject = null)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);

        $etlConfig = $testConfig->getEtlConfig($configName);

        $dbHost     = $etlConfig['db_host'];
        $dbName     = $etlConfig['db_name'];
        $dbUser     = $etlConfig['db_user'];
        $dbPassword = $etlConfig['db_password'];

        $page = $session->getPage();

        print( $page->getText() );

        $page->selectFieldOption('api_token_username', $etlConfig['api_token_username']);

        $page->pressButton('Auto-Generate');

        #------------------------------
        # Load database
        #------------------------------
        $page->fillField('db_host', $dbHost);
        $page->fillField('db_name', $dbName);
        $page->fillField('db_username', $dbUser);
        $page->fillField('db_password', $dbPassword);

        #------------------------------
        # E-mail notifications
        #------------------------------
        if ($etlConfig['email_errors'] === "1" || $etlConfig['email_errors'] === "true") {
            $page->checkField('email_errors');
        }
        if ($etlConfig['email_summary'] === "1" || $etlConfig['email_summary'] === "true") {
            $page->checkField('email_summary');
        }

        if (empty($emailSubject)) {
            $page->fillField('email_subject', $etlConfig['email_subject']);
        } else {
            $page->fillField('email_subject', $emailSubject);
        }

        $page->fillField('email_to_list', $etlConfig['email_to_list']);

        #------------------------------
        # Save
        #------------------------------
        $page->pressButton('Save');
    }


    public static function configureAutoGen($session, $configName, $option)
    {
        $testConfig = new TestConfig(FeatureContext::CONFIG_FILE);

        $etlConfig = $testConfig->getEtlConfig($configName);

        $include_complete_fields = false;
        $include_dag_fields = false;
        $include_file_fields = false;
        $include_survey_fields = false;
        $remove_notes_fields = false;
        $remove_identifier_fields  = false;
        $combine_non_repeating_fields = false;
        $non_repeating_fields_table = null;

        $page = $session->getPage();

        $page->selectFieldOption('api_token_username', $etlConfig['api_token_username']);

        if ($option === "include") {
            $include_complete_fields = true;
            $include_dag_fields = true;
            $include_file_fields = true;
            $include_survey_fields = false;
        }
        if ($option === "remove") {
            $remove_notes_fields = true;
            $remove_identifier_fields = true;
        }
        if ($option === "nonrepeating fields and table") {
            $combine_non_repeating_fields = true;
            $non_repeating_fields_table = 'merged';
        }
        if ($option === "combined nonrepeating checkbox only") {
            $combine_non_repeating_fields = true;
            $non_repeating_fields_table = null;
        }
        if ($option === "nonrepeating table name only") {
            $non_repeating_fields_table = 'merged';
        }

        if ($include_complete_fields) {
            $page->checkField('autogen_include_complete_fields');
        }
        if ($include_file_fields) {
            $page->checkField('autogen_include_file_fields');
        }
        if ($include_survey_fields) {
            $page->checkField('autogen_include_survey_fields');
        }
        if ($include_dag_fields) {
            $page->checkField('autogen_include_dag_fields');
        }
        if ($remove_notes_fields) {
            $page->checkField('autogen_remove_notes_fields');
        }
        if ($remove_identifier_fields) {
            $page->checkField('autogen_remove_identifier_fields');
        }
        if ($combine_non_repeating_fields) {
            $page->checkField('autogen_combine_non_repeating_fields');
        } else {
            $page->uncheckField('autogen_combine_non_repeating_fields');
        }
        if (!empty($non_repeating_fields_table)) {
            $page->fillField('autogen_non_repeating_fields_table'
                , $non_repeating_fields_table);
        }

        $page->pressButton('Auto-Generate');
   }
}

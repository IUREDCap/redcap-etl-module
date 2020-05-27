<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

class Help
{
    // Help settings
    const DEFAULT_TEXT        = 0;
    const CUSTOM_TEXT         = 1;
    const PREPEND_CUSTOM_TEXT = 2;  // prepend custom text to default text
    const APPEND_CUSTOM_TEXT  = 3;  // append custom text to default text
    
    const HELP_SETTING_PREFIX = 'help-setting:';    // Prefix for help setting for a topic
    const HELP_TEXT_PREFIX    = 'help-text:';       // Prefix for (custom) help text for a topic
    
    /** @var array map from help topic to help content */
    private static $help = [
        'api-token-user' =>
            "<p>"
            ."REDCap-ETL uses the REDCap API (Application Programming Interface) to access REDCap,"
            ." and the REDCap API requires an API token."
            ." This selection specifies which user's token will be used for REDCap-ETL."
            ."</p>"
        ,
        'auto-generate-rules' =>
            "<p>"
            ."Transformation rules can be generated automatically from the REDCap project."
            ." You can optionally include the following fields in the rules that are generated: "
            ." data access group, file, and form complete."
            ." Including file fields in the rules will only cause the status for the file fields"
            ." to be exported, and <em>not</em> the contents of those files."
            ." Specifically, if a file field contains a file, then the string \"[document]\" will be"
            ." exported, and if the field does not contain a file, then a blank string will be exported."
            ."</p>"
        ,
        'batch-size' =>
            "<p>The batch size indicates how many REDCap record IDs will be processed at a time."
            ." In general, the larger the batch size, the"
            ." faster your ETL process will run, but the more memory it will use."
            ." For very large projects, using a large batch size may cause"
            ." system memory limits to be exceeded and the ETL process to fail.</p>"
        ,
        'database-keys' =>
            "<p>"
            ."Selecting database primary keys indicates that primary key constraints will be generated for"
            ." the tables that are created by the ETL process. Selecting foreign keys will cause foreign keys"
            ." to be generated for child tables that reference their parent tables. Foreign keys can only"
            ." be selected when primary keys have been selected also."
            ."</p>"
        ,
        'database-logging' =>
            "<p>"
            ."Enabling database logging will log ETL job information to you load database."
            ." Information is logged to 2 tables:"
            ."</p>"
            ."<ul>"
            ."<li><b>database log table</b> - the main database log table, which contains"
            ." one entry for each ETL process that is run.</li>"
            ."<li><b>database event log table</b> - contains one entry for each logged event"
            ." for the ETL processes that have run. All events with the same log_id belong to"
            ." the same ETL process.</li>"
            ."</ul>"
        ,
        'email-errors' =>
            "<p>"
            ."Indicates if an e-mail should be sent if the ETL process encounters an error."
            ."</p>"
        ,
        'email-notifications' =>
            "<p>"
            ."REDCap-ETL can send e-mail notifications about ETL processes. The following options can be set:</p>"
            ." <ul>"
            ." <li><b>E-mail errors</b>"
            ." - if checked, an e-mail will be sent if an ETL process encounters an error."
            ." </li>"
            ." <li><b>E-mail summary</b>"
            ." - if checked, an e-mail summary of logging information will be sent when, and if, the ETL"
            ." process completes successfully."
            ." </li>"
            ." <li><b>E-mail subject</b>"
            ." - the subject to use for e-mail notifications."
            ." </li>"
            ." <li><b>E-mail to list</b>"
            ." - the comma-separated list of e-mails that e-mail notifications should be sent to."
            ." </li>"
            ."</ul>"
            ."</p>"
        ,
        'email-subject' =>
            "<p>"
            ."The subject to use for e-mails sent to you from REDCap-ETL servers."
            ." Note: if you are using a custom REDCap-ETL server, this property might not be supported."
            ."</p>"
        ,
        'email-summary' =>
            "<p>"
            ."Indicates if a summary e-mail should be sent if the ETL process completes successfully."
            ." The information contained in this e-mail will also be in the database log tables if"
            ." database logging is enabled."
            ."</p>"
        ,
        'email-to-list' =>
            "<p>"
            ."A comma-separated list of e-mail addresses that REDCap-ETL sends error and summary e-mails to."
            ."</p>"
        ,
        'extract-settings' =>
            "<p>"
            ."REDCap-ETL uses the REDCap API (Application Programming Interface) to extract data from REDCap."
            ." You need to have an API token for your project that REDCap-ETL can use."
            ."</p>"
            ."<p>"
            ."If an API token with full data set export permission already exists for the project,"
            ." then there should be at least one username in the API token drop-down. If there are none,"
            ." then a user who has full data set export permission will"
            ." need to request an API token with export rights."
            ."</p>"
        ,
        'label-view-suffix' =>
            "<p>"
            ."REDCap-ETL generates tables that have choice <em>values</em> for multiple choice fields in REDCap."
            ." However, it also creates views of these tables that contain the choice <em>labels</em>"
            ." instead of the choice values."
            ." The name of a view is the name of the table it corresponds to"
            ." with the \"label view suffix\" appended to it."
            ."</p>"
        ,
        'load-settings' =>
            "<p>"
            ."The load settings specify the database where the extracted and transformed"
            ." data from REDCap will be loaded."
            ." The databases currently supported are: MySQL, PostgreSQL and SQL Server."
            ."</p>"
            ."<p>"
            ."The type, host name and name for the database need to be specified, as well as a"
            ." username and password for a valid user account for the database. The port number"
            ." only needs to be specified if the database is using a non-standard port number"
            ."</p>"
            ."<p>"
            ." The user account"
            ." has to have at least the following database permissions for REDCap-ETL to work:"
            ."</p>"
            ."<ul>"
            ."<li>SELECT</li>"
            ."<li>INSERT</li>"
            ."<li>CREATE</li>"
            ."<li>DROP</li>"
            ."<li>CREATE VIEW</li>"
            ."</ul>"
        ,
        'pre-processing-sql' =>
            "<p>The pre-processing SQL field is used to specify SQL commands that you want"
            ." REDCap-ETL to run on the load database"
            ." before the ETL process starts."
            ." </p>"
            ." <p>Pre-processing is intended for SQL commands that update the database, and any"
            ." select commands entered will not generate output."
            ." Note that the table name prefix (if any) will NOT be added automatically to "
            ." pre-processing SQL commands, so if you are using a table prefix, you will"
            ." need to manually add it to table names in these commands.</p>"
        ,
        'post-processing-sql' =>
            "<p>The post-processing SQL field is used to specify SQL commands that you want"
            ." REDCap-ETL to run on the load database"
            ." after the ETL process completes. For example, you"
            ." might want to create indexes on the tables generated by the ETL process."
            ." </p>"
            ." <p>Post-processing is intended for SQL commands that update the database, and any"
            ." select commands entered will not generate output."
            ." Note that the table name prefix (if any) will NOT be added automatically to "
            ." post-processing SQL commands, so if you are using a table prefix, you will"
            ." need to manually add it to table names in these commands.</p>"
        ,
        'table-name-prefix' =>
            "<p>"
            ."A prefix that will be added to the names of all ETL generated tables in the"
            ." load database, except for the log tables."
            ." This can be useful if you have multiple processes loading data to "
            ." the same database and want to be able to easily distinguish the tables"
            ." of the different processes."
            ."</p>"
        ,
        'transformation-rules' =>
            "<p>"
            ."The transformation rules describe how REDCap-ETL should transform the data"
            ." from REDCap before loading it into the database."
            ."</p>"
    ];

    public static function getTitle($topic)
    {
        # Change dashes to blanks and capitalize the first letter of each word
        $title = str_replace('-', ' ', $topic);
        $title = ucwords($title);

        # Make adjustments
        $title = str_replace('Post Processing', 'Post-Processing', $title);
        $title = str_replace('Api', 'API', $title);
        $title = str_replace('Email', 'E-mail', $title);
        $title = str_replace('Sql', 'SQL', $title);
                
        return $title;
    }
    
    
    public static function getDefaultHelp($topic)
    {
        $help = Filter::sanitizeHelp(self::$help[$topic]);
        return $help;
    }
    
    public static function getCustomHelp($topic, $module)
    {
        $help = $module->getCustomHelp($topic);
        $help = Filter::sanitizeHelp($help);
        return $help;
    }
    
    public static function getHelp($topic, $module)
    {
        $help = '';
        
        # Get the specified topic's help setting
        $setting = $module->getHelpSetting($topic);
        
        switch ($setting) {
            case self::CUSTOM_TEXT:
                $help = self::getCustomHelp($topic, $module);
                break;
            case self::PREPEND_CUSTOM_TEXT:
                $help = self::getCustomHelp($topic, $module) . self::getDefaultHelp($topic);
                break;
            case self::APPEND_CUSTOM_TEXT:
                $help = self::getDefaultHelp($topic) . self::getCustomHelp($topic, $module);
                break;
            default:
                $help = self::getDefaultHelp($topic);
                break;
        }
        
        return $help;
    }
    
    public static function getHelpWithPageLink($topic, $module)
    {
        $help = self::getHelp($topic, $module);
        $help = '<a id="'.$topic.'-help-page" href="'.$module->getUrl('web/help.php?topic='.$topic).'"'
            .' target="_blank" style="float: right;"'   // @codeCoverageIgnore
            .'>'                                        // @codeCoverageIgnore
            .'View text on separate page</a>'           // @codeCoverageIgnore
            .'<div style="clear: both;"></div>'         // @codeCoverageIgnore
            .Filter::sanitizeHelp($help);
        return $help;
    }
    
    public function getHelpFromText($setting, $defaultHelp, $customHelp)
    {
        $help = '';
        
        switch ($setting) {
            case self::CUSTOM_TEXT:
                $help = Filter::sanitizeHelp($customHelp);
                break;
            case self::PREPEND_CUSTOM_TEXT:
                $help = Filter::sanitizeHelp($customHelp . $defaultHelp);
                break;
            case self::APPEND_CUSTOM_TEXT:
                $help = Filter::sanitizeHelp($defaultHelp . $customHelp);
                break;
            default:
                $help = Filter::sanitizeHelp($defaultHelp);
                break;
        }
        
        return $help;
    }
    
    
    public static function getTopics()
    {
        return array_keys(self::$help);
    }
    
    /**
     * Indicates if the specified topic is a valid help topic.
     *
     * @return boolean true if the specified topic is a valid help topic, and false otherwise.
     */
    public static function isValidTopic($topic)
    {
        $isValid = false;
        $topics = array_keys(self::$help);
        if (in_array($topic, $topics)) {
            $isValid = true;
        }
        return $isValid;
    }
    
    /**
     * Indicates if the specified help setting is valid.
     */
    public static function isValidSetting($setting)
    {
        $isValid = false;
        $settingText = '';
        switch ($setting) {
            case self::DEFAULT_TEXT:
            case self::CUSTOM_TEXT:
            case self::PREPEND_CUSTOM_TEXT:
            case self::APPEND_CUSTOM_TEXT:
                $isValid = true;
                break;
        }
        return $isValid;
    }
    
    public static function getSettingText($setting)
    {
        $settingText = '';
        switch ($setting) {
            case self::CUSTOM_TEXT:
                $settingText = 'custom help';
                break;
            case self::PREPEND_CUSTOM_TEXT:
                $settingText = 'prepend custom help to default';
                break;
            case self::APPEND_CUSTOM_TEXT:
                $settingText = 'append custom help to default';
                break;
            default:
                $settingText = 'default help';
                break;
        }
        return $settingText;
    }
}

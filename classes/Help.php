<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

class Help
{
    // Help settings
    public const DEFAULT_TEXT        = 0;
    public const CUSTOM_TEXT         = 1;
    public const PREPEND_CUSTOM_TEXT = 2;  // prepend custom text to default text
    public const APPEND_CUSTOM_TEXT  = 3;  // append custom text to default text

    public const HELP_SETTING_PREFIX = 'help-setting:';    // Prefix for help setting for a topic
    public const HELP_TEXT_PREFIX    = 'help-text:';       // Prefix for (custom) help text for a topic

    /** @var array map from help topic to help content */
    private static $help = [
        'api-token-user' =>
            "<p>"
            . "REDCap-ETL uses the REDCap API (Application Programming Interface) to access REDCap,"
            . " and the REDCap API requires an API token."
            . " This selection specifies which user's token will be used for REDCap-ETL."
            . "</p>"
        ,
        'auto-generate-rules' =>
            "<p>"
            . "Transformation rules can be generated automatically from the REDCap project."
            . " For the rules, you can optionally:"
            . "</p>"
            . "<p>"
            . "<b>Include the following fields</b> in the generated rules: "
            . "<ul>"
            . "<li>data access group</li>"
            . "<li>file: Including file fields in the rules will only cause the status "
            . "for the file fields to be exported, and <em>not</em> the contents "
            . "of those files. Specifically, if a file field contains a file, then "
            . "the string \"[document]\" will be exported. However, if the field "
            . "does not contain a file, then a blank string will be exported.</li>"
            . "<li>form completed</li>"
            . "<li>survey: Including survey fields in the rules will return "
            . "the survey identifier and timestamp for forms that are enabled "
            . "as surveys.</li>"
            . "</ul>"
            . "</p>"
            . "<p>"
            . "<b>Remove the following fields</b> from the rules that are generated:"
            . " notes and identifier. If you select <em>notes</em>, all fields that"
            . " have a 'Notes Box' field type will be removed. If you select"
            . " <em>identifier</em>, all fields that have been flagged in REDCap"
            . " as an identifier will be removed."
            . "</p>"
            . "<p>"
            . "<b>Combine all non-repeating fields from different forms into one table"
            . " (for non-longitudinal studies only)</b>: Click the"
            . " <em>Combine all non-repeating fields into one table</em> checkbox,"
            . " then enter the name you want to use for that table in the"
            . " <em>Table name to use</em> text box."
            . "<ul>"
            . "<li>"
            . "If your project is longitudinal, any values you enter here will be"
            . " ignored."
            . "</li>"
            . "<li>"
            . "If you uncheck the <em>combine all non-repeating fields into one table</em>"
            . " checkbox and leave a value for <em>table name</em>, the table name"
            . " will not be deleted, but will be ignored until you recheck the checkbox."
            . "</li>"
            . "</ul>"
            . "</p>"
            . "<p>"
            . "<b>Auto-generate the transformation rules and display them in the"
            . " <em>Transformation Rules</em> textbox</b>: Click the"
            . " <em>Auto-Generate</em> button to immediately auto-generate the"
            . " transformation rules and display them in the text box. If you then"
            . " save this configuration, these rules will be used for the ETL process."
            . "</p>"
            . "<p>"
            . "<b>Have the transformation rules auto-generated before each ETL run</b>:"
            . " Check the <em>Auto-generate new rules before each run</em> checkbox</b>"
            . " to indicate that REDCap ETL should generate new ETL transformation rules"
            . " prior to each run of the ETL the process. The last version of new rules"
            . " that were generated will be stored in the database and displayed"
            . " in the <em>Transformation Rules</em> textbox when you return"
            . " to this page. (Any transformation rules already saved in the"
            . " <em>Transformation Rules</em> textbox, including any that you have"
            . " manually entered, will be overwritten at ETL rum time with the"
            . " automatically-generated rules.)"
            . "</p>"
        ,
        'batch-size' =>
            "<p>The batch size indicates how many REDCap record IDs will be processed at a time."
            . " In general, the larger the batch size, the"
            . " faster your ETL process will run, but the more memory it will use."
            . " For very large projects, using a large batch size may cause"
            . " system memory limits to be exceeded and the ETL process to fail.</p>"
        ,
        'data-load-options' =>
            " <p>"
            . "The embedded server supports loading data extracted from REDCap to either a database"
            . " or a CSV ZIP file. Data load options configuration allows either of these options"
            . " to be disallowed for users."
            . "</p>"
            . "<p>"
            . "Note that the CSV ZIP file option can only be used interactively (on the Run page)."
            . " It cannot be used when scheduling an ETL process."
            . "</p>"
        ,
        'database-keys' =>
            " <p>"
            . "Selecting database primary keys will cause primary key constraints to be generated for"
            . " the tables that are created by the ETL process. Selecting foreign keys will cause foreign keys"
            . " to be generated for child tables that reference their parent tables. Foreign keys can only"
            . " be selected when primary keys have been selected also."
            . "</p>"
        ,
        'database-logging' =>
            "<p>"
            . "Enabling database logging will log ETL job information to you load database."
            . " Information is logged to 2 tables:"
            . "</p>"
            . "<ul>"
            . "<li><b>database log table</b> - the main database log table, which contains"
            . " one entry for each ETL process that is run.</li>"
            . "<li><b>database event log table</b> - contains one entry for each logged event"
            . " for the ETL processes that have run. All events with the same log_id belong to"
            . " the same ETL process.</li>"
            . "</ul>"
        ,
        'email-errors' =>
            "<p>"
            . "Indicates if an e-mail should be sent if the ETL process encounters an error."
            . "</p>"
        ,
        'email-notifications' =>
            "<p>"
            . "REDCap-ETL can send e-mail notifications about ETL processes. The following options can be set:</p>"
            . " <ul>"
            . " <li><b>E-mail errors</b>"
            . " - if checked, an e-mail will be sent if an ETL process encounters an error."
            . " </li>"
            . " <li><b>E-mail summary</b>"
            . " - if checked, an e-mail summary of logging information will be sent when, and if, the ETL"
            . " process completes successfully."
            . " </li>"
            . " <li><b>E-mail subject</b>"
            . " - the subject to use for e-mail notifications."
            . " </li>"
            . " <li><b>E-mail to list</b>"
            . " - the comma-separated list of e-mails that e-mail notifications should be sent to."
            . " </li>"
            . "</ul>"
            . "</p>"
        ,
        'email-subject' =>
            "<p>"
            . "The subject to use for e-mails sent to you from REDCap-ETL servers."
            . " Note: if you are using a custom REDCap-ETL server, this property might not be supported."
            . "</p>"
        ,
        'email-summary' =>
            "<p>"
            . "Indicates if a summary e-mail should be sent if the ETL process completes successfully."
            . " The information contained in this e-mail will also be in the database log tables if"
            . " database logging is enabled."
            . "</p>"
        ,
        'email-to-list' =>
            "<p>"
            . "A comma-separated list of e-mail addresses that REDCap-ETL sends error and summary e-mails to."
            . "</p>"
        ,
        'etl-configurations' =>
             "<p>"
             . "This page lets you manage the ETL configuration for the project. You need to create at least one ETL"
             . " configuration to be able to run REDCap-ETL on the project. To create an ETL configuration, enter"
             . " the name for your configuration in the text box, and then click on the <b>Add</b> button."
             . "</p>"
             . "<p>"
             . "Once you have created a configuration, you need to configure it. To do that, click on the"
             . " configuration's button in the <b>Configure</b> column."
             . "</p>"
        ,
        'etl-cron-jobs' =>
            "<p>"
            . "REDCap-ETL supports ETL cron jobs, which allow users to schedule ETL processes to run daily or weekly."
            . " Users can only schedule ETL processes to run for a given day and time range if its checkbox"
            . " in the table is checked."
            . " The number in each box in the table (if any) represents the number of ETL processes scheduled to run"
            . "  at that time, and it"
            . " can be clicked to see the details of those ETL processes."
            . "</p>"
        ,
        'etl-servers' =>
            "<p>"
            . "The REDCap-ETL external module uses ETL servers to process users' ETL configurations and workflows."
            . " REDCap-ETL includes a built-in ETL server, called the \"embedded server\", which runs within REDCap,"
            . " and which cannot be copied, renamed or deleted."
            . "<p>"
            . "</p>"
            . " You can also add configurations here for any remote ETL servers you have set up."
            . " Remote ETL servers run outside of your REDCap server, and they can"
            . " be used to reduce the ETL processing load on your REDCap server and to get around"
            . " firewall restrictions your REDCap server may have."
            . " Information on how to set up a remote ETL server is here:"
            . " <a href=\"https://github.com/IUREDCap/redcap-etl-module/blob/master/docs/RemoteEtlServerGuide.md\">"
            . " Remote ETL Server Guide</a>"
            . "</p>"
        ,
        'etl-users' =>
            "<p>"
            . "Non-admin users need to be given permission to use REDCap-ETL on a per project basis."
            . " Use REDCap-ETL's users search feature to find users and add ETL permissions for them."
            . "</p>"
        ,
        'extract-settings' =>
            "<p>"
            . "REDCap-ETL uses the REDCap API (Application Programming Interface) to extract data from REDCap."
            . " You need to have an API token for your project that REDCap-ETL can use."
            . "</p>"
            . "<p>"
            . "If an API token with full data set export permission already exists for the project,"
            . " then there should be at least one username in the API token drop-down. If there are none,"
            . " then a user who has full data set export permission will"
            . " need to request an API token with export rights."
            . "</p>"
            . "<p>"
            . "<b>Extract Filter Logic.</b> This property can be used to restrict the records that are"
            . " extracted from REDCap. For example, the following logic would cause only records with"
            . " a record_id less than 1020 to be extracted:"
            . " <pre>[record_id] &lt; 1020</pre>"
            . "Values for this property should use the standard REDCap syntax for logic. This value"
            . " is passed to REDCap, and REDCap-ETL relies on REDCap to check this value for errors."
            . " Unfortunately, error checking for filter logic in REDCap is extremely limited, and"
            . " in most cases where there is an error, REDCap will not generate an error message"
            . " and simply return no records."
            . "</p>"
        ,
        'global-properties' =>
            "<p>"
            . "Global properties are used to override the values of task properties in a workflow."
            . " If a global property is set, its value will replace"
            . " the corresponding values for all tasks in the workflow."
            . " This can be used, for example, to set the database,"
            . " so that all tasks in a workflow load data to the same database."
            . "</p>"
        ,
        'ignore-empty-incomplete-forms' =>
            "<p>"
            . "REDCap is inconsistent in how it handles forms that have not been edited."
            . " For unedited forms, the form complete field may have a value of blank or zero."
            . " While a blank value appears to always indicate a form has not yet been edited,"
            . " a zero value could occur for both edited and unedited forms."
            . " By default this configuration property is turned off, and this causes REDCap-ETL to store"
            . " rows in the database for empty forms with a form complete value of zero"
            . " (i.e., incomplete). "
            . " If this property is checked, then empty incomplete forms will be ignored, and"
            . " no row will be stored in the database for them."
            . "</p>"
        ,
        'label-view-suffix' =>
            "<p>"
            . "REDCap-ETL generates tables that have choice <em>values</em> for multiple choice fields in REDCap."
            . " However, it also creates views of these tables that contain the choice <em>labels</em>"
            . " instead of the choice values."
            . " The name of a view is the name of the table it corresponds to"
            . " with the \"label view suffix\" appended to it."
            . "</p>"
        ,
        'labels' =>
            "<p>"
            . "REDCap multiple choice question responses have both values and labels"
            . " (e.g., value 0 for label \"no\")."
            . " Originally, REDCap-ETL stored only the multiple choice values in the"
            . " database tables it generated."
            . " Corresponding views were also created that contained"
            . " the labels for the multiple choice questions instead of the values."
            . " The name of the view was the same as the"
            . " name of its corresponding table with the \"label view suffix\" appended to it."
            . "</p>"
            . "<p>"
            . " Now, both value and label fields are generated for database tables created by REDCap-ETL."
            . " The value is stored in the field name specified by the user, and the label is"
            . " stored in the same field name with the \"label field suffix\" appended to it."
            . " If the \"label field suffix\" is left blank, no label fields will be generated."
            . " The label views have been deprecated and turned off by default for new projects,"
            . " and the future of label views is uncertain."
            . "</p>"
            . "<ul>"
            . "<li><b>Label views</b> - <i>(deprecated)</i> turns on/off creation of"
            . " views containing multiple choice response labels</li>"
            . "<li><b>Label view suffix</b> - <i>(deprecated)</i> text appended to "
            . " a table name to make the name for its label view</li>"
            . "<li><b>Label field suffix</b> - text appended to field name of multiple choice response value field to"
            . "  make the corresponding multiple choice label field</li>"
            . "<li><b>Label field type</b> - the type used in the database for label fields. For CSV files, the type"
            . " makes no difference. For databases, specifying the \"string\" type will store labels as \"text\"."
            . " Specifying the \"string\" type instead of the \"char\" or \"varchar\" types can be used in some cases"
            . " to resolve database row size errors</li>"
            . "</ul>"
        ,
        'load-settings' =>
            "<p>"
            . "The load settings specify the database where the extracted and transformed"
            . " data from REDCap will be loaded."
            . " The databases currently supported are: MySQL, PostgreSQL and SQL Server."
            . "</p>"
            . "<p>"
            . "The type, host name and name for the database need to be specified, as well as a"
            . " username and password for a valid user account for the database. The port number"
            . " only needs to be specified if the database is using a non-standard port number"
            . "</p>"
            . "<p>"
            . " The user account"
            . " has to have at least the following database permissions for REDCap-ETL to work:"
            . "</p>"
            . "<ul>"
            . "<li>SELECT</li>"
            . "<li>INSERT</li>"
            . "<li>CREATE</li>"
            . "<li>DROP</li>"
            . "<li>CREATE VIEW</li>"
            . "</ul>"
        ,
        'lookup-table' =>
            "<p>The lookup table lists the multiple choice fields in the database tables with"
            . " their associated values and labels."
            . " </p>"
        ,
        'pre-processing-sql' =>
            "<p>The pre-processing SQL field is used to specify SQL commands that you want"
            . " REDCap-ETL to run on the load database"
            . " before the ETL process starts."
            . " </p>"
            . " <p>Pre-processing is intended for SQL commands that update the database, and any"
            . " select commands entered will not generate output."
            . " Note that the table name prefix (if any) will NOT be added automatically to "
            . " pre-processing SQL commands, so if you are using a table prefix, you will"
            . " need to manually add it to table names in these commands.</p>"
        ,
        'post-processing-sql' =>
            "<p>The post-processing SQL field is used to specify SQL commands that you want"
            . " REDCap-ETL to run on the load database"
            . " after the ETL process completes. For example, you"
            . " might want to create indexes on the tables generated by the ETL process."
            . " </p>"
            . " <p>Post-processing is intended for SQL commands that update the database, and any"
            . " select commands entered will not generate output."
            . " Note that the table name prefix (if any) will NOT be added automatically to "
            . " post-processing SQL commands, so if you are using a table prefix, you will"
            . " need to manually add it to table names in these commands.</p>"
        ,
        'run' =>
            "<p>"
            . "This page allows you to run an ETL configuration or ETL workflow"
            . " that you have previously created."
            . "</p>"
            . "<p>"
            . "Steps:"
            . "<ol>"
            . "<li>"
            . "Select whether you want to run an <b>ETL Configuration</b> or <b>ETL Workflow</b>."
            . "</li>"
            . "<li>"
            . "Select the name of the ETL configuration/workflow you want to run."
            . "</li>"
            . "<li>"
            . " Select the <b>ETL Server</b> you want to use. Unless your administrator has set up and"
            . " configured additional ETL servers, then the \"embedded server\", which is"
            . " included with REDCap-ETL,  will be your only choice for ETL server."
            . "</li>"
            . "<li>"
            . "If you are running an ETL configuration on the \"embedded server\", select"
            . " whether you want the extracted and transformed data to be loaded into"
            . " the database (specified in the ETL configuration), or if you want"
            . " the data to be exported as a CSV (comma-separated value) zip file."
            . "</li>"
            . "<li>"
            . "Click on the <b>Run</b> button."
            . "</li>"
            . "</ol>"
            . "</p>"
        ,
        'schedule' =>
            "<p>"
            . "This page allows you to schedule an ETL configuration or ETL workflow"
            . " to run recurringly up to once per day."
            . "</p>"
            . "<p>"
            . "Steps:"
            . "<ol>"
            . "<li>"
            . "Select whether you want to run an <b>ETL Configuration</b> or <b>ETL Workflow</b>."
            . "</li>"
            . "<li>"
            . "Select the name of the ETL configuration/workflow you want to run."
            . "</li>"
            . "<li>"
            . "Select the <b>ETL Server</b> you want to use. Unless your administrator has set up and"
            . " configured additional ETL servers, then the \"embedded server\", which is"
            . " included with REDCap-ETL,  will be your only choice for ETL server."
            . "</li>"
            . "<li>"
            . "Select the days of the week and times that you want the ETL process to run. You can select"
            . " at most one time per day."
            . "</li>"
            . "<li>"
            . "Click on the <b>Save</b> button."
            . "</li>"
            . "</ol>"
            . "</p>"
        ,
        'table-name-prefix' =>
            "<p>"
            . "A prefix that will be added to the names of all ETL generated tables in the"
            . " load database, except for the log tables."
            . " This can be useful if you have multiple processes loading data to "
            . " the same database and want to be able to easily distinguish the tables"
            . " of the different processes."
            . "</p>"
        ,
        'transformation-rules' =>
            "<p>"
            . "The transformation rules describe how REDCap-ETL should transform the data"
            . " from REDCap before loading it into the database."
            . "</p>"
        ,
        'workflow-tasks' =>
            "<p>"
            . "Workflows consist of a set of tasks, where each task represents an"
            . " ETL configuration for a REDCap project."
            . " The tasks of the workflow are executed sequentially in the order defined."
            . "</p>"
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
        $help = '<a id="' . $topic . '-help-page" href="' . $module->getUrl('web/help.php?topic=' . $topic) . '"'
            . ' target="_blank" style="float: right;"'   // @codeCoverageIgnore
            . '>'                                        // @codeCoverageIgnore
            . 'View text on separate page</a>'           // @codeCoverageIgnore
            . '<div style="clear: both;"></div>'         // @codeCoverageIgnore
            . Filter::sanitizeHelp($help);
        return $help;
    }

    public static function getHelpFromText($setting, $defaultHelp, $customHelp)
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

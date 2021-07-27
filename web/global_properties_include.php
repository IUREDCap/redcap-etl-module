<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

use IU\REDCapETL\EtlRedCapProject;
use IU\REDCapETL\Database\DbConnectionFactory;
use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

#-------------------------------------------------------------------------
# Only allow this page to be included in the user ETL Configure page,
# and not accessed directly
#-------------------------------------------------------------------------
if (!defined('REDCAP_ETL_MODULE')) {
    header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
    exit;
}
?>

<!-- ==============================================================================================
GLOBAL PROPERTIES
=============================================================================================== -->

<?php
$success = '';
$warning = '';
$error   = '';

$parseResult = '';

# $workflowName should be set by configure.php, which includes this file

$workflowStatus = $module->getWorkflowStatus($workflowName);

try {
    #-----------------------------------------------------------
    # Check that the user has permission to access this page
    #-----------------------------------------------------------
    $module->checkUserPagePermission(USERID);

    #-------------------------------------------------------------------
    # Check for test mode (which should only be used for development)
    #-------------------------------------------------------------------
    $testMode = false;
    if (@file_exists(__DIR__ . '/../test-config.ini')) {
        $testMode = true;
    }

    if (array_key_exists('success', $_GET)) {
        $success = Filter::stripTags($_GET['success']);
    }

    if (array_key_exists('warning', $_GET)) {
        $warning = Filter::stripTags($_GET['warning']);
    }

    $selfUrl = $module->getUrl('web/configure.php')
        . '&workflowName=' . Filter::escapeForUrlParameter($workflowName)
        . '&configType=workflow'
        ;
    $workflowUrl = $module->getUrl('web/configure.php')
        . '&workflowName=' . Filter::escapeForUrlParameter($workflowName)
        . '$configType=workflow'
        ;
    $workflowsUrl = $module->getUrl('web/workflows.php');
    $configureUrl = $module->getUrl('web/configure.php');

    $adminConfig = $module->getAdminConfig();


    $configuration = $module->getWorkflowGlobalConfiguration($workflowName);

    $properties = array();
    $properties = $module->getWorkflowGlobalProperties($workflowName);

    $redCapDb = new RedCapDb();

    $isWorkflowGlobalProperties = true;
    if (!empty($configuration)) {
        #-------------------------
        # Get the submit value
        #-------------------------
        $submitValue = '';
        if (array_key_exists('submitValue', $_POST)) {
            $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);
        }
        #---------------------------------------------------------------
        # if this is a POST other than Cancel,
        # update the configuration properties with the POST values
        #---------------------------------------------------------------
        if (!empty($submitValue) && strcasecmp($submitValue, 'Cancel')) {
            $configuration->set(Filter::stripTagsArrayRecursive($_POST), $isWorkflowGlobalProperties);
            # Reset properties, since they may have been modified above
            $initialize = false;
            $properties = $configuration->getGlobalProperties($initialize);
        }

        #------------------------------------------------------
        # Process Actions
        #------------------------------------------------------
        $properties[Configuration::DB_CONNECTION] = null;
        try {
            if (strcasecmp($submitValue, 'Cancel') === 0) {
                header('Location: ' . $workflowUrl);
            } elseif (strcasecmp($submitValue, 'Save') === 0) {
                if (empty($warning) && empty($error)) {
                    $configuration->validate($isWorkflowGlobalProperties);
                    // Save configuration to database
                    $module->setWorkflowGlobalProperties($workflowName, $properties, USERID);
                }
            }
        } catch (\Exception $exception) {
            $error = 'ERROR: ' . $exception->getMessage();
        }
    }  // END - if configuration is not empty
} catch (\Exception $exception) {
    $error = 'ERROR: ' . $exception->getMessage();
}
?>


<script>
    // Help dialog events
    $(document).ready(function() {
        $( function() {
            
            $('#db_primary_keys').click(function () {
                if (this.checked) {
                    $("#db_foreign_keys").prop("disabled", false);
                } else {
                    $("#db_foreign_keys").prop("checked", false);
                    $("#db_foreign_keys").prop("disabled", true);
                }
            });
            
            $('#auto-generate-rules-help-link').click(function () {
                $('#auto-generate-rules-help').dialog({dialogClass: 'redcap-etl-help', width: 400, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right+370 top-140', of: $(this)})
                    ;
                return false;
            });
            $('#batch-size-help-link').click(function () {
                $('#batch-size-help').dialog({dialogClass: 'redcap-etl-help', width: 400, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right+20 top', of: $(this)})
                    ;
                return false;
            });
            $('#database-event-log-table-help-link').click(function () {
                $('#database-event-log-table-help').dialog({dialogClass: 'redcap-etl-help'})
                    .dialog('widget').position({my: 'left top', at: 'right+20 top', of: $(this)})
                    ;
                return false;
            });             
            $('#database-log-table-help-link').click(function () {
                $('#database-log-table-help').dialog({dialogClass: 'redcap-etl-help'})
                    .dialog('widget').position({my: 'left top', at: 'right+20 top', of: $(this)})
                    ;
                return false;
            });            
            $('#database-logging-help-link').click(function () {
                $('#database-logging-help').dialog({dialogClass: 'redcap-etl-help'})
                    .dialog('widget').position({my: 'left top', at: 'right+20 top', of: $(this)})
                    ;
                return false;
            });
            $('#email-notifications-help-link').click(function () {
                $('#email-notifications-help').dialog({dialogClass: 'redcap-etl-help', width: 400, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right-20 top', of: $(this)})
                    ;
                return false;
            });            
            $('#extract-settings-help-link').click(function () {
                $('#extract-settings-help').dialog({dialogClass: 'redcap-etl-help', width: 500, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right top+60', of: $(this)})
                    ;
                return false;
            });
            $('#global-properties-help-link').click(function () {
                $('#global-properties-help').dialog({dialogClass: 'redcap-etl-help', width: 500, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right top+60', of: $(this)})
                    ;
                return false;
            });
            $('#ignore-empty-incomplete-forms-help-link').click(function () {
                $('#ignore-empty-incomplete-forms-help')
                    .dialog({dialogClass: 'redcap-etl-help', width: 400, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right+20 top', of: $(this)})
                    ;
                return false;
            });
            $('#label-view-suffix-help-link').click(function () {
                $('#label-view-suffix-help').dialog({dialogClass: 'redcap-etl-help', width: 400, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right+20 top', of: $(this)})
                    ;
                return false;
            });
            $('#database-keys-help-link').click(function () {
                $('#database-keys-help').dialog({dialogClass: 'redcap-etl-help', width: 400, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right+20 top', of: $(this)})
                    ;
                return false;
            });            
            $('#load-settings-help-link').click(function () {
                $('#load-settings-help').dialog({dialogClass: 'redcap-etl-help', width: 500, maxHeight: 440})
                    .dialog('widget').position({my: 'left top', at: 'right+20 top', of: $(this)})
                    ;
                return false;
            });
            $('#pre-processing-sql-help-link').click(function () {
                $('#pre-processing-sql-help').dialog({dialogClass: 'redcap-etl-help', width: 400, maxHeight: 400})
                    .dialog('widget').position({my: 'left top', at: 'right+10 top', of: $(this)})
                    ;
                return false;
            });  
            $('#post-processing-sql-help-link').click(function () {
                $('#post-processing-sql-help').dialog({dialogClass: 'redcap-etl-help', width: 400, maxHeight: 400})
                    .dialog('widget').position({my: 'left top', at: 'right+10 top', of: $(this)})
                    ;
                return false;
            });            
            $('#table-name-prefix-help-link').click(function () {
                $('#table-name-prefix-help').dialog({dialogClass: 'redcap-etl-help'})
                    .dialog('widget').position({my: 'left top', at: 'right+20 top', of: $(this)})
                    ;
                return false;
            });
        });
        
        // Database schema display
        $(function() {
        $("select[name=<?php echo Configuration::DB_TYPE;?>]").change(function() {
            var value = $(this).val();
            if (value == "<?php echo DbConnectionFactory::DBTYPE_POSTGRESQL; ?>") {
                $("#dbSchemaRow").show();
            } else {
                $("#dbSchemaRow").hide();
            }
        });
            
    });
    

});
</script>



<!-- <div id="accordion" style="margin-bottom: 22px; width: 40%;"> -->
<div style="border: 1px solid #aaa; border-radius: 7px; padding: 1em;">

<?php
#print "<hr/>POST:<br/>\n";
#print "<pre>\n";
#print_r($_POST);
#print "</pre>\n";
?>

  <div style="float: right;">
    <a href="#" id="global-properties-help-link" class="etl-help" title="help">?</a>
  </div>

  <div id="global-properties-help" title="Global Properties" style="display: none; clear: both;">
        <?php echo Help::getHelpWithPageLink('global-properties', $module); ?>
  </div> 

  <div style="font-weight: bold; font-size: 110%; text-align: center;">Global Properties</div>
  <div>
    <!-- ====================================
    Global Properties Form
    ===================================== -->
    <form action="<?php echo $selfUrl;?>" method="post"
          enctype="multipart/form-data" style="margin-top: 17px;" autocomplete="off">

    <input type="hidden" name="taskKey"
        value="<?php echo Filter::escapeForHtmlAttribute($taskKey); ?>" />
    
    <input type="hidden" name="<?php echo Configuration::CONFIG_API_TOKEN; ?>"
           value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::CONFIG_API_TOKEN]); ?>" />
    
    <input type="hidden" name="<?php echo Configuration::TRANSFORM_RULES_SOURCE; ?>"
           value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::TRANSFORM_RULES_SOURCE]); ?>" />
     
    <!--<div style="padding: 10px; border: 1px solid #ccc; background-color: #f0f0f0;"> -->


    <fieldset class="config" style="padding: 4px;">
    <table style="width: 50%; margin: 0 auto;">
        <tr>
            <td style="text-align: center;">&nbsp;</td>
            <td style="text-align: center;">
                <input type="submit" name="submitValue" value="Save" />
                <input type="submit" name="submitValue" value="Cancel" style="margin-left: 24px;" />
            </td>
            <td style="text-align: center;">&nbsp;</td>
        </tr>
    </table>
    </fieldset>

    <fieldset class="config">
        <legend>Load Settings</legend>
       
        <table>
            <tbody style="padding: 20px;">
                <!-- DATABASE TYPE -->

                <tr>
                    <td>Database type</td>
                    <td>
                        <select name="<?php echo Configuration::DB_TYPE;?>">
                            <?php
                            # No database type global property
                            $selected = '';
                            if (empty($properties)) {
                                $selected = ' selected ';
                            }
                            ?>
                            <option value=''></option>

                            <?php
                            # MySQL database type option
                            $dbType = DbConnectionFactory::DBTYPE_MYSQL;
                            $selected = '';
                            if ($properties[Configuration::DB_TYPE] === $dbType) {
                                $selected = ' selected ';
                            }
                            ?>
                            <option value="<?php echo $dbType; ?>" <?php echo $selected; ?> >MySQL</option>

                            <?php
                            # PostgreSQL database type option
                            $dbType = DbConnectionFactory::DBTYPE_POSTGRESQL;
                            $selected = '';
                            if ($properties[Configuration::DB_TYPE] === $dbType) {
                                $selected = ' selected ';
                            }
                            ?>
                            <option value="<?php echo $dbType; ?>" <?php echo $selected; ?> >PostgreSQL</option>

                            <?php
                            # SQL Server database type option
                            $dbType = DbConnectionFactory::DBTYPE_SQLSERVER;
                            $selected = '';
                            if ($properties[Configuration::DB_TYPE] === $dbType) {
                                $selected = ' selected ';
                            }
                            ?>
                            <option value="<?php echo $dbType; ?>" <?php echo $selected; ?> >SQL Server</option>
                        </select>
                    </td>
                    <td>
                        <a href="#" id="load-settings-help-link" class="etl-help" title="help">?</a>
                        <div id="load-settings-help" title="Load Settings" style="display: none; clear: both;">
                            <?php echo Help::getHelpWithPageLink('load-settings', $module); ?>
                        </div> 
                    </td>
                </tr>


                <!-- DATABASE HOST -->
                <tr>
                    <td>Database host</td>
                    <td><input type="text" name="<?php echo Configuration::DB_HOST;?>"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_HOST])?>"/>
                    </td>
                </tr>

                <!-- DATABASE PORT NUMBER -->
                <tr>
                    <td style="padding-right: 1em;">Database port number</td>
                    <td><input type="text" name="<?php echo Configuration::DB_PORT;?>"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_PORT])?>"/>
                    </td>
                </tr>

                <!-- DATABASE NAME -->
                <tr>
                    <td>Database name</td>
                    <td><input type="text" name="<?php echo Configuration::DB_NAME;?>"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_NAME])?>"></td>
                </tr>
                
                <?php
                $dbSchemaStyle = '';
                if ($properties[Configuration::DB_TYPE] !== DbConnectionFactory::DBTYPE_POSTGRESQL) {
                    $dbSchemaStyle = ' style="display: none;" ';
                }
                ?>

                <!-- DATABASE SCHEMA -->
                <tr id="dbSchemaRow" <?php echo $dbSchemaStyle; ?> >
                    <td>Database schema</td>
                    <td><input type="text" name="<?php echo Configuration::DB_SCHEMA;?>"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_SCHEMA])?>"></td>
                </tr>

                <!-- DATABASE USERNAME -->
                <tr>
                    <td style="padding-right: 1em;">Database username</td>
                    <td><input type="text" name="<?php echo Configuration::DB_USERNAME;?>"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_USERNAME])?>"/>
                    </td>
                </tr>

                <!-- DATABASE PASSWORD -->
                <tr>
                    <td style="padding-right: 1em;">Database password</td>
                    <td>
                        <input type="password" name="<?php echo Configuration::DB_PASSWORD;?>"
                            value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_PASSWORD])?>"
                            id="dbPassword"/>
                    </td>
                </tr>
            </tbody>
        </table>
    </fieldset>
    
    <fieldset class="config">
        <legend>Processing Settings</legend>
        <table>
            <tbody style="padding: 20px;">    
                <!-- BATCH SIZE -->
                <tr>
                    <td>Batch size</td>
                    <td><input type="text" name="<?php echo Configuration::BATCH_SIZE;?>"
                        value="<?php echo Filter::escapeForHtml($properties[Configuration::BATCH_SIZE]);?>"/>
                        <a href="#" id="batch-size-help-link" class="etl-help">?</a>
                        <div id="batch-size-help" title="Batch Size" style="display: none;">
                            <?php echo Help::getHelpWithPageLink('batch-size', $module); ?>
                        </div>
                    </td>
                </tr>
                
                <!-- IGNORE EMPTY INCOMPLETE FORMS -->
                <tr>
                    <td>Ignore empty <br/> incomplete forms &nbsp; </td>
                    <td>
                        <?php
                        $checked = '';
                        if ($properties[Configuration::IGNORE_EMPTY_INCOMPLETE_FORMS]) {
                            $checked = ' checked ';
                        }
                        ?>
                        <input type="checkbox" name="<?php echo Configuration::IGNORE_EMPTY_INCOMPLETE_FORMS;?>"
                            id="<?php echo Configuration::IGNORE_EMPTY_INCOMPLETE_FORMS;?>" value="true"
                            <?php echo $checked;?> style="vertical-align: middle; margin: 0;">    
                        <a href="#" id="ignore-empty-incomplete-forms-help-link"
                           class="etl-help" style="margin-left: 1em;">?</a>
                        <div id="ignore-empty-incomplete-forms-help"
                             title="Ignore Empty Incomplete Forms" style="display: none;">
                            <?php echo Help::getHelpWithPageLink('ignore-empty-incomplete-forms', $module); ?>
                        </div>
                    </td>                    
                </tr>
            </tbody>
        </table>
                <fieldset class="config-nested">
                <legend>Database Tables</legend>
                        <table>
            <tbody>
          
                <!-- TABLE NAME PREFIX -->
                <tr>
                    <td style="padding-right: 1em;">
                        <label for="<?php echo Configuration::TABLE_PREFIX;?>">Table name prefix</label>
                    </td>
                    <td><input type="text" name="<?php echo Configuration::TABLE_PREFIX;?>"
                        id="<?php echo Configuration::TABLE_PREFIX;?>"
                        value="<?php echo Filter::escapeForHtml($properties[Configuration::TABLE_PREFIX]);?>"/>
                        <a href="#" id="table-name-prefix-help-link" class="etl-help">?</a>
                        <div id="table-name-prefix-help" title="Table Name Prefix" style="display: none;">
                            <?php echo Help::getHelpWithPageLink('table-name-prefix', $module); ?>
                        </div>
                    </td>
                </tr>
     
                <!-- LABEL VIEW SUFFIX -->
                <tr>
                    <td style="padding-right: 1em;">Label view suffix</td>
                    <td><input type="text" name="<?php echo Configuration::LABEL_VIEW_SUFFIX;?>"
                        value="<?php echo Filter::escapeForHtml($properties[Configuration::LABEL_VIEW_SUFFIX]);?>"/>
                        <a href="#" id="label-view-suffix-help-link" class="etl-help">?</a>
                        <div id="label-view-suffix-help" title="Label View Suffix" style="display: none;">
                            <?php echo Help::getHelpWithPageLink('label-view-suffix', $module); ?>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <td>&nbsp;</td>
                </tr>
     
                <!-- PRIMARY KEYS -->
                <tr>
                    <td style="padding-right: 1em;">Primary Keys</td>
                    <td>
                        <?php
                        $checked = '';
                        if ($properties[Configuration::DB_PRIMARY_KEYS]) {
                            $checked = ' checked ';
                        }
                        ?>
                        <input type="checkbox" name="<?php echo Configuration::DB_PRIMARY_KEYS;?>"
                            id="db_primary_keys" value="true"
                            <?php echo $checked;?> style="vertical-align: middle; margin: 0;">    
                        <a href="#" id="database-keys-help-link" class="etl-help" style="margin-left: 1em;">?</a>
                        <div id="database-keys-help" title="Database Keys" style="display: none;">
                            <?php echo Help::getHelpWithPageLink('database-keys', $module); ?>
                        </div>
                    </td>
                </tr>

                <!-- FOREIGN KEYS -->
                <tr>
                    <td style="padding-right: 1em;">Foreign Keys</td>
                    <td>
                        <?php
                        $checked = '';
                        if ($properties[Configuration::DB_FOREIGN_KEYS]) {
                            $checked = ' checked ';
                        }
                        ?>
                        <input type="checkbox" name="<?php echo Configuration::DB_FOREIGN_KEYS;?>"
                            id="db_foreign_keys" value="true"
                            <?php echo $checked;?> style="vertical-align: middle; margin: 0;">                    
                    </td>
                </tr>
            </tbody>
        </table>
        </fieldset>
        
        <fieldset class="config-nested">
        <legend>Database Logging</legend>
        <table>
            <tbody>
          
                <!-- DATABASE LOGGING -->
                <tr>
                    <td>Database logging enabled</td>
                    <td>
                        <?php
                        $checked = '';
                        if ($properties[Configuration::DB_LOGGING]) {
                            $checked = ' checked ';
                        }
                        ?>
                        <input type="checkbox" name="<?php echo Configuration::DB_LOGGING;?>" value="true"
                            <?php echo $checked;?> style="vertical-align: middle; margin: 0;">                    
                    </td>
                    <td>
                        <a href="#" id="database-logging-help-link" class="etl-help" style="margin-left: 2em">?</a> 
                        <div id="database-logging-help" title="Database Logging" style="display: none;">
                            <?php echo Help::getHelpWithPageLink('database-logging', $module); ?>
                        </div>  
                    </td>
                </tr>
          
                <!-- DATABASE LOG TABLE -->
                <tr>
                    <td>Database log table</td>
                    <td><input type="text" name="<?php echo Configuration::DB_LOG_TABLE;?>"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_LOG_TABLE]);?>"/>
                    </td>
                </tr>
                
                <!-- DATABASE EVENT LOG TABLE -->
                <tr>
                    <td style="padding-right: 1em;">Database event log table</td>
                    <?php $dbEventLogTable = $properties[Configuration::DB_EVENT_LOG_TABLE]; ?>
                    <td><input type="text" name="<?php echo Configuration::DB_EVENT_LOG_TABLE;?>"
                        value="<?php echo Filter::escapeForHtmlAttribute($dbEventLogTable);?>"/>
                    </td>
                </tr>
            </tbody>
        </table>
        </fieldset>

        
        <fieldset class="config-nested">
        <legend>E-mail Notifications</legend>
        <table>
            <tbody>
                
                <!-- E-MAIL ERRORS -->      
                <tr>
                    <td>E-mail errors</td>
                    <td>
                        <?php
                        $checked = '';
                        if ($properties[Configuration::EMAIL_ERRORS]) {
                            $checked = ' checked ';
                        }
                        ?>
                        <input type="checkbox" name="<?php echo Configuration::EMAIL_ERRORS;?>" value="true"
                            <?php echo $checked;?> style="vertical-align: middle; margin: 0;">
                    </td>
                    <td>
                        <!-- E-MAIL NOTIFICATION HELP BUTTON -->
                        <a href="#" id="email-notifications-help-link" class="etl-help" style="margin-left: 2em">?</a> 
                        <div id="email-notifications-help" title="E-mail Notifications" style="display: none;">
                            <?php echo Help::getHelpWithPageLink('email-notifications', $module); ?>
                        </div>  
                    </td>
                </tr>

                <!-- E-MAIL SUMMARY -->
                <tr>
                    <td style="padding-right: 1em;">E-mail summary</td>
                    <td>
                        <?php
                        $checked = '';
                        if ($properties[Configuration::EMAIL_SUMMARY]) {
                            $checked = ' checked ';
                        }
                        ?>
                        <input type="checkbox" name="<?php echo Configuration::EMAIL_SUMMARY;?>"
                            <?php echo $checked;?> style="vertical-align: middle; margin: 0;">
                    </td>
                </tr>
                
                <!-- E-MAIL SUBJECT -->
                <tr>
                    <td>E-mail subject</td>
                    <td><input type="text" name="<?php echo Configuration::EMAIL_SUBJECT;?>" size="32"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::EMAIL_SUBJECT]);?>"
                        />
                    </td>
                </tr>
                
                <!-- E-MAIL TO LIST -->
                <tr>
                    <td>E-mail to list</td>
                    <td><input type="text" name="<?php echo Configuration::EMAIL_TO_LIST;?>" size="32"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::EMAIL_TO_LIST]);?>"
                        />                  
                    </td>
                </tr>
            </tbody>
        </table>
        </fieldset>

    </fieldset>

    <fieldset class="config" style="padding: 4px;">
    <table style="width: 50%; margin: 0 auto;">
        <tr>
            <td style="text-align: center;">&nbsp;</td>
            <td style="text-align: center;">
                <input type="submit" name="submitValue" value="Save" />
                <input type="submit" name="submitValue" value="Cancel" style="margin-left: 24px;" />
            </td>
            <td style="text-align: center;">&nbsp;</td>
        </tr>
    </table>
    </fieldset>



    <?php Csrf::generateFormToken(); ?>
    </form>

  </div>
</div>


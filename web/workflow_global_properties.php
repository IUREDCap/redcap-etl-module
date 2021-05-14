<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\REDCapETL\EtlRedCapProject;
use IU\REDCapETL\Database\DbConnectionFactory;

use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

$success = '';
$warning = '';
$error   = '';

$parseResult = '';

$workflowName = Filter::escapeForHtml($_GET['workflowName']);
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
    if (@file_exists(__DIR__.'/../test-config.ini')) {
        $testMode = true;
    }

    if (array_key_exists('success', $_GET)) {
        $success = Filter::stripTags($_GET['success']);
    }

    if (array_key_exists('warning', $_GET)) {
        $warning = Filter::stripTags($_GET['warning']);
    }

    $selfUrl = $module->getUrl('web/workflow_global_properties.php')
                   .'&workflowName='.Filter::escapeForUrlParameter($workflowName);
    $workflowUrl = $module->getUrl('web/workflow_configure.php')
                       .'&workflowName='.Filter::escapeForUrlParameter($workflowName);
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
                header('Location: '.$workflowUrl);
            } elseif (strcasecmp($submitValue, 'Save') === 0) {
                if (empty($warning) && empty($error)) {
                    $configuration->validate($isWorkflowGlobalProperties);
                    $module->setWorkflowGlobalProperties($workflowName, $properties, USERID);  // Save configuration to database
                }
            } elseif (strcasecmp($submitValue, 'Save and Exit') === 0) {
                if (empty($warning) && empty($error)) {
                    $configuration->validate($isWorkflowGlobalProperties);
                    $module->setWorkflowGlobalProperties($workflowName, $properties, USERID);  // Save configuration to database
                    $location = 'Location: '.$workflowUrl;
                    header($location);
                }
            } 
        } catch (\Exception $exception) {
            $error = 'ERROR: '.$exception->getMessage();
        }
    }  // END - if configuration is not empty
} catch (\Exception $exception) {
    $error = 'ERROR: '.$exception->getMessage();
}
?>


<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
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

<div class="projhdr"> 
    <img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png" alt="">REDCap-ETL
    <?php
    if ($testMode) {
        echo '<span style="color: blue;">[TEST MODE]</span>';
    }
    ?>
</div>


<?php

$module->renderProjectPageContentHeader($configureUrl, $error, $warning, $success);
?>

<div  style="padding: 4px; margin-bottom: 20px; border: 1px solid #ccc; background-color: #ccc;">
    <div>
     <span style="font-weight: bold;">Workflow:</span>
        <a href="<?php echo $workflowUrl ?>"><?php echo $workflowName ?></a>
     </div>
     <div>
     <span style="font-weight: bold;">Workflow Status:</span>
        <?php echo $workflowStatus ?>
     </div>
</div>
    <div>
     <span style="font-weight: bold;">WORKFLOW GLOBAL PROPERTIES</span>
    </div>

<!-- ====================================
Configuration form
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

    <fieldset class="config">
        <legend>Load Settings</legend>
       
        <table>
            <tbody style="padding: 20px;">
                <!-- DATABASE TYPE -->
                <tr>
                    <td>Database type</td>
                    <td>
                        <select name="<?php echo Configuration::DB_TYPE;?>">
p
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
                        <input type="checkbox" name="<?php echo Configuration::EMAIL_SUMMARY;?>" value="true"
                            <?php echo $checked;?> style="vertical-align: middle; margin: 0;">
                    </td>
                </tr>
                
                <!-- E-MAIL SUBJECT -->
                <tr>
                    <td>E-mail subject</td>
                    <td><input type="text" name="<?php echo Configuration::EMAIL_SUBJECT;?>" size="64"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::EMAIL_SUBJECT]);?>"
                        />
                    </td>
                </tr>
                
                <!-- E-MAIL TO LIST -->
                <tr>
                    <td>E-mail to list</td>
                    <td><input type="text" name="<?php echo Configuration::EMAIL_TO_LIST;?>" size="64"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::EMAIL_TO_LIST]);?>"
                        />                  
                    </td>
                </tr>
            </tbody>
        </table>
        </fieldset>

        <fieldset class="config-nested">
        <legend>
            <label for="<?php echo Configuration::PRE_PROCESSING_SQL;?>">Pre-Processing SQL</label>
        </legend>        
        <table>
            <tbody>          
                
                <!-- PRE-PROCESSING SQL -->
                <tr>
                    <td style="padding-right: 1em;">SQL</td>
                    <td>
                        <?php
                        $sql = $properties[Configuration::PRE_PROCESSING_SQL];
                        $sqlName = Configuration::PRE_PROCESSING_SQL;
                        ?>
                        <textarea rows="10" cols="70"
                            style="margin-top: 4px; margin-bottom: 4px;"
                            id="<?php echo $sqlName;?>"
                            name="<?php echo $sqlName;?>"><?php echo Filter::escapeForHtml($sql);?></textarea>
                    </td>                   
                    <td>
                        <a href="#" id="pre-processing-sql-help-link" class="etl-help"
                           style="margin-left: 2em;">?</a>                      
                        <div id="pre-processing-sql-help" title="Pre-Processing SQL" style="display: none;">
                            <?php echo Help::getHelpWithPageLink('pre-processing-sql', $module); ?>
                        </div>                         
                    </td>
                </tr>

            </tbody>
        </table>
        </fieldset>
                   
        <fieldset class="config-nested">
        <legend>
            <label for="<?php echo Configuration::POST_PROCESSING_SQL;?>">Post-Processing SQL</label>
        </legend>        
        <table>
            <tbody>          
                
                <!-- POST-PROCESSING SQL -->
                <tr>
                    <td style="padding-right: 1em;">SQL</td>
                    <td>
                        <?php
                        $sql = $properties[Configuration::POST_PROCESSING_SQL];
                        $sqlName = Configuration::POST_PROCESSING_SQL;
                        ?>
                        <textarea rows="10" cols="70"
                            style="margin-top: 4px; margin-bottom: 4px;"
                            id="<?php echo $sqlName;?>"
                            name="<?php echo $sqlName;?>"><?php echo Filter::escapeForHtml($sql);?></textarea>
                    </td>                   
                    <td>
                        <a href="#" id="post-processing-sql-help-link" class="etl-help"
                           style="margin-left: 2em;">?</a>                      
                        <div id="post-processing-sql-help" title="Post-Processing SQL" style="display: none;">
                            <?php echo Help::getHelpWithPageLink('post-processing-sql', $module); ?>
                        </div>                         
                    </td>
                </tr>

            </tbody>
        </table>
        </fieldset>
    </fieldset>


    <fieldset class="config">
    <table style="width: 50%; margin: 0 auto;">
        <tr>
            <td style="text-align: center;">&nbsp;</td>
            <td style="text-align: center;">
                <input type="submit" name="submitValue" value="Save" />
                <input type="submit" name="submitValue" value="Save and Exit" style="margin-left: 24px;"/>
                <input type="submit" name="submitValue" value="Cancel" style="margin-left: 24px;" />
            </td>
            <td style="text-align: center;">&nbsp;</td>
        </tr>
    </table>
    </fieldset>
    

    <?php Csrf::generateFormToken(); ?>
</form>

<?php
// See JSON output of properties for REDCap-ETL
#if (isset($configuration)) {
#    $json = $configuration->getRedCapEtlJsonProperties();
#    $json = json_encode(json_decode($json), JSON_PRETTY_PRINT);
#    print "<pre>\n";
#    print_r($json);
#    print "</pre>\n";
#}

?>


<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\REDCapETL\EtlRedCapProject;

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

try {
    #-----------------------------------------------------------
    # Check that the user has permission to access this page
    # and get the configuration if one was specified
    #-----------------------------------------------------------
    $configCheck = true;
    $configuration = $module->checkUserPagePermission(USERID, $configCheck);
    $configName = '';
    if (!empty($configuration)) {
        $configName = $configuration->getName();
        $properties = $configuration->getProperties();
    }

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

    $listUrl  = $module->getUrl("web/index.php");
    $selfUrl  = $module->getUrl("web/configure.php");
    $generateRulesUrl = $module->getUrl('web/generate_rules.php');

    $adminConfig = $module->getAdminConfig();


    /** @var array configurations property map from property name to value */
    $properties = array();

    $redCapDb = new RedCapDb();



    if (!empty($configuration)) {
        #--------------------------------------------------------------
        # Get the API tokens for this project with export permission,
        # and the username of user whose API token should be used
        # (if any)
        #--------------------------------------------------------------
        $exportRight  = $configuration->getDataExportRight();
        $apiTokens    = $redCapDb->getApiTokens(PROJECT_ID, $exportRight);
        $apiTokenUser = $configuration->getProperty(Configuration::API_TOKEN_USERNAME);
        
        
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

            if (!isset($_POST[Configuration::API_TOKEN_USERNAME])) {
                $_POST[Configuration::API_TOKEN_USERNAME] = '';
            }
            $configuration->set(Filter::stripTagsArrayRecursive($_POST));
            
            # If this is NOT a remote REDCap configuration, set SSL certificate verification
            # to the global value (this can only be set in the configuration for remote
            # REDCap configurations)
            $apiUrl = $configuration->getProperty(Configuration::REDCAP_API_URL);
            if (strcmp($apiUrl, $module->getRedCapApiUrl()) === 0) {
                $configuration->setProperty(Configuration::SSL_VERIFY, $adminConfig->getSslVerify());
            }
            $properties = $configuration->getProperties();
        }
        
        #----------------------------------------------
        # Check API token specification
        #----------------------------------------------
        $localApiUrl = $module->getRedCapApiUrl();
        $apiTokenUser = '';

        $apiUrl = $configuration->getProperty(Configuration::REDCAP_API_URL);
        if ($testMode && strcmp($apiUrl, $module->getRedCapApiUrl()) !== 0) {
            ; // Test mode, so remote REDCap is being used, so no checks can be done
            # In test mode:
            # - the REDCap API URL becomes editable for admins
            # - if the REDCap API URL is changed so that it does not match the API URL of local REDCap,
            #   the API token user is ignored
            # -
        } else {
            if (empty($configuration->getProperty(Configuration::API_TOKEN_USERNAME))) {
                # No API token user was specified, set the API token to blank
                $configuration->setProperty(Configuration::DATA_SOURCE_API_TOKEN, '');
            } else {
                $apiTokenUser = $configuration->getProperty(Configuration::API_TOKEN_USERNAME);
                # An API token user was specified
                if (!array_key_exists($apiTokenUser, $apiTokens)) {
                    $warning = 'WARNING: user "'.$apiTokenUser.'" does not'
                        .' have an API token for this project. API token user reset to blank.';
                    # The API token user does not have a valid API token, so set it to blank
                    $configuration->setProperty(Configuration::API_TOKEN_USERNAME, '');
                    $configuration->setProperty(Configuration::DATA_SOURCE_API_TOKEN, '');
                } else {
                    # A valid API token user was specified, so set the API token to the
                    # value for this user
                    $configuration->setProperty(
                        Configuration::DATA_SOURCE_API_TOKEN,
                        $apiTokens[$apiTokenUser]
                    );
                }
            }
        }
        
        
        # Reset properties, since they may have been modified above
        $properties = $configuration->getProperties();
        
        
        #------------------------------------------------------
        # Process Actions
        #------------------------------------------------------
        try {
            if (strcasecmp($submitValue, 'Cancel') === 0) {
                header('Location: '.$listUrl);
            } elseif (strcasecmp($submitValue, 'Save') === 0) {
                if (empty($warning) && empty($error)) {
                    $configuration->validate();
                    $module->setConfiguration($configuration);  // Save configuration to database
                }
            } elseif (strcasecmp($submitValue, 'Save and Exit') === 0) {
                if (empty($warning) && empty($error)) {
                    $configuration->validate();
                    $module->setConfiguration($configuration);  // Save configuration to database
                    $location = 'Location: '.$listUrl;
                    header($location);
                }
            } elseif (strcasecmp($submitValue, 'Upload CSV file') === 0) {
                $uploadFileName = $_FILES['uploadCsvFile']['tmp_name'];
                if (empty($uploadFileName)) {
                    $error = 'ERROR: No upload transformation rules file specified.';
                } else {
                    $fileContents = file_get_contents($uploadFileName);
                    if ($fileContents === false) {
                        $error = 'ERROR: Unable to upload transformation rules file "'
                            .$_FILES['uploadCsvFile']['tmp_name'].'"';
                    } else {
                        $properties[Configuration::TRANSFORM_RULES_TEXT] = $fileContents;
                    }
                }
            } elseif (strcasecmp($submitValue, 'Download CSV file') === 0) {
                $downloadFileName = 'rules.csv';
                header('Content-Type: text/csv');
                //header("Content-Transfer-Encoding: Binary");
                header("Content-disposition: attachment; filename=\"" . $downloadFileName . "\"");
                echo $properties[Configuration::TRANSFORM_RULES_TEXT];
                return;
            } elseif (strcasecmp($submitValue, 'Auto-Generate') === 0) {
                $apiUrl    = $configuration->getProperty(Configuration::REDCAP_API_URL);
                $dataToken = $configuration->getProperty(Configuration::DATA_SOURCE_API_TOKEN);

                if (empty($apiUrl)) {
                    $error = 'ERROR: No REDCap API URL specified.';
                } elseif (empty($dataToken)) {
                    $error = 'ERROR: No data source API token information specified.';
                } else {
                    $existingRulesText = $properties[Configuration::TRANSFORM_RULES_TEXT];
                    $areExistingRules = false;
                    if (!empty($existingRulesText)) {
                        # WARN that existing rules will be overwritten
                        # ...
                        $areExistingRules = true;
                        #echo
                        #"<script>\n"
                        #.'$("#rules-overwrite-dialog").dialog("open");'."\n"
                        #."</script>\n"
                        #;
                    }
                    

                    $sslVerify  = $adminConfig->getSslVerify();
                    $caCertFile = null;
                    #$caCertFile = $adminConfig->getCaCertFile();
                    
                    if ($testMode) {
                        # If module is in test mode, override the system-wide SSL verify flag
                        # and certificate authority certificate file with the configuration
                        # specific ones
                        $sslVerify  = $configuration->getProperty(Configuration::SSL_VERIFY);
                        #$caCertFile = $configuration->getProperty(Configuration::CA_CERT_FILE);
                    }

                    #if ($testMode && strcmp($apiUrl, $module->getRedCapApiUrl()) !== 0) {
                    #    # If in test mode, and a remote API URL was used, use an API token to get the data project
                    #    # for rules generation
                    #    $dataProject = new \IU\REDCapETL\EtlRedCapProject(
                    #        $apiUrl,
                    #        $dataToken,
                    #        $sslVerify,
                    #        $caCertFile
                    #    );
                    #} else {
                        # Non-remote API URL, create a data project for rules generation
                        # that uses REDCap's developer methods
                        #$dataProject = new \IU\RedCapEtlModule\EtlExtRedCapProject(
                        $dataProject = new \IU\REDCapETL\EtlRedCapProject(
                            $apiUrl,
                            $dataToken,
                            $sslVerify,
                            $caCertFile
                        );
                    #}
                
                    $rulesGenerator = new \IU\REDCapETL\RulesGenerator();
                    $rulesText = $rulesGenerator->generate($dataProject);
                    $properties[Configuration::TRANSFORM_RULES_TEXT] = $rulesText;
                    #print "$rulesText\n";
                }
            } elseif (strcasecmp($submitValue, 'Check Rules') === 0) {
                // Code to check current transformation rules; to be completed...
                $apiUrl    = $configuration->getProperty(Configuration::REDCAP_API_URL);
                $dataToken = $configuration->getProperty(Configuration::DATA_SOURCE_API_TOKEN);
                $sslVerify  = $adminConfig->getSslVerify();
                $caCertFile = null;
                $dataProject = new \IU\REDCapETL\EtlRedCapProject($apiUrl, $dataToken, $sslVerify, $caCertFile);
                #$dataProject =
                #    new \IU\RedCapEtlModule\EtlExtRedCapProject($apiUrl, $dataToken, $sslVerify, $caCertFile);
                
                $logger = new \IU\REDCapETL\Logger('rules-check');
                $logger->setOn(false);
                
                $checkProperties = $configuration->getPropertiesArray();
                if ($sslVerify) {
                    $checkProperties[Configuration::SSL_VERIFY] = 'true';
                }
                $checkProperties[Configuration::EMAIL_ERRORS] = false;
                $checkProperties[Configuration::EMAIL_SUMMARY] = false;
                
                $checkConfiguration = new \IU\REDCapETL\Configuration($logger, $checkProperties);
                $schemaGenerator = new \IU\REDCapETL\SchemaGenerator($dataProject, $checkConfiguration, $logger);
                $rulesText = $checkConfiguration->getProperty(Configuration::TRANSFORM_RULES_TEXT);
                list($schema, $parseResult) = $schemaGenerator->generateSchema($rulesText);
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
            $('#label-view-suffix-help-link').click(function () {
                $('#label-view-suffix-help').dialog({dialogClass: 'redcap-etl-help', width: 400, maxHeight: 440})
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

$module->renderProjectPageContentHeader($selfUrl, $error, $warning, $success);

?>


<?php
#-------------------------------------
# Configuration selection form
#-------------------------------------
?>
<form action="<?php echo $selfUrl;?>" method="post" 
      style="padding: 4px; margin-bottom: 0px; border: 1px solid #ccc; background-color: #ccc;">
    <span style="font-weight: bold;">Configuration:</span>
    <select name="configName" onchange="this.form.submit()">
    <?php
    $values = $module->getAccessibleConfigurationNames();
    array_unshift($values, '');
    foreach ($values as $value) {
        if (strcmp($value, $configName) === 0) {
            echo '<option value="'.Filter::escapeForHtmlAttribute($value).'" selected>'
                .Filter::escapeForHtml($value)."</option>\n";
        } else {
            echo '<option value="'.Filter::escapeForHtmlAttribute($value).'">'
                .Filter::escapeForHtml($value)."</option>\n";
        }
    }
    ?>
    </select>
    <?php Csrf::generateFormToken(); ?>
</form>


<?php
if (empty($configuration)) {
    ; // Don't display any page content
} else {
?>


<!-- Rules overwrite dialog -->
<div id="rules-overwrite-dialog" style="display:none;" title="Overwrite transformation rules?">
  Test...
</div>

<!-- generate rules -->

<!-- <button type="button" id="api-token-button">...</button> -->
<script>
$(function() {
    $("#rules-overwrite-dialog").dialog({
        autoOpen: false
    });
    $("#api-token-button").click(function() {
        $("#dialog").dialog("open");
    });
});
</script>


<script>
// Show/hide API Token
$(function() {
    $("#showApiToken").change(function() {
        var newType = 'password';
        if ($(this).is(':checked')) {
            newType = 'text';
        }
        $("#apiToken").each(function(){
            $("<input type='" + newType + "'>")
                .attr({ id: this.id, name: this.name, value: this.value, size: this.size, style: this.style })
                .insertBefore(this);
        }).remove();       
    })
});    

// Show/hide Db Password
$(function() {
    $("#showDbPassword").change(function() {
        var newType = 'password';
        if ($(this).is(':checked')) {
            newType = 'text';
        }
        $("#dbPassword").each(function(){
            $("<input type='" + newType + "'>")
                .attr({ id: this.id, name: this.name, value: this.value, size: this.size, style: this.style })
                .insertBefore(this);
        }).remove();       
    })
});
    
</script>


<!-- ====================================
Configuration form
===================================== -->
<form action="<?php echo $selfUrl;?>" method="post"
    enctype="multipart/form-data" style="margin-top: 17px;" autocomplete="off">

    <input type="hidden" name="configName"
        value="<?php echo Filter::escapeForHtmlAttribute($configName); ?>" />
    
    <input type="hidden" name="<?php echo Configuration::CONFIG_API_TOKEN; ?>"
           value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::CONFIG_API_TOKEN]); ?>" />
           
    <input type="hidden" name="<?php echo Configuration::TRANSFORM_RULES_SOURCE; ?>"
           value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::TRANSFORM_RULES_SOURCE]); ?>" />
         
    <!--<div style="padding: 10px; border: 1px solid #ccc; background-color: #f0f0f0;"> -->

    <fieldset class="config">
        <legend>Extract Settings</legend>

        <table>
            <tbody style="padding: 20px;">

                <?php if (SUPER_USER) { ?>
                <tr>
                    <td>REDCap API URL</td>
                    <?php
                    if ($testMode && SUPER_USER) { # make API URL editable
                        $displayApiUrl = Filter::escapeForHtmlAttribute($properties[Configuration::REDCAP_API_URL]);
                    ?>
                    <td>
                        <input type="text" size="60" 
                            value="<?php echo $displayApiUrl;?>"
                            name="<?php echo Configuration::REDCAP_API_URL?>" />
                    </td>
                    <?php } else { ?>
                    <td>
                        <div style="border: 1px solid #AAAAAA; margin: 4px 0px; padding: 4px; border-radius: 4px;">
                        <?php echo Filter::escapeForHtml($properties[Configuration::REDCAP_API_URL]); ?>
                        </div>
                    </td>
                    <?php } ?>
                </tr>
                <?php } // End if super user ?>

                <?php if ($testMode && SUPER_USER) { # make API URL editable ?>
                <tr>
                    <td>
                    SSL certificate verification&nbsp;
                    </td>
                    <td>
                        <?php
                        $value = '';
                        $checked = '';
                        if ($properties[Configuration::SSL_VERIFY]) {
                            $checked = ' checked ';
                            $value = ' value="true" ';
                        }
                        ?>
                        <input type="checkbox" name="<?php echo Configuration::SSL_VERIFY;?>"
                        <?php echo $checked;?>
                        <?php echo $value;?> >
                    </td>
                </tr>
                    
                <tr>
                    <td>
                    API token
                    </td>
                    <td>
                        <?php
                        $apiToken = $properties[Configuration::DATA_SOURCE_API_TOKEN];
                        ?>
                        <input type="password" size="34"
                            value="<?php echo Filter::escapeForHtmlAttribute($apiToken);?>"
                            name="<?php echo Configuration::DATA_SOURCE_API_TOKEN;?>" id="apiToken"/>
                        <input type="checkbox" id="showApiToken" style="vertical-align: middle; margin: 0;">
                        <span style="vertical-align: middle;">Show</span>
                    </td>
                    <td>
                        <div id="dialog" style="display:none;" title="Data Source API Token">
                        Test...
                        </div>
                        <!-- <button type="button" id="api-token-button">...</button> -->
                        <script>
                        $(function() {
                            $("#dialog").dialog({
                                autoOpen: false
                            });
                            $("#api-token-button").click(function() {
                                $("#dialog").dialog("open");
                            });
                        });
                        </script>
                    </td>
                </tr>
                <?php } # End - if test mode and super user (admin) ?>
                
                <tr class="localRow" <?php echo $localRowStyle; ?> >
                    <td>API Token - use token of user&nbsp;</td>
                    <td>
                        <select name="<?php echo Configuration::API_TOKEN_USERNAME;?>">
                            <?php

                            echo '<option value=""></option>'."\n";
                            foreach ($apiTokens as $username => $apiToken) {
                                $selected = '';
                                if (strcasecmp($username, $apiTokenUser) === 0) {
                                    $selected = 'selected';
                                }
                                echo '<option '.$selected.' value="'.Filter::escapeForHtmlAttribute($username).'">'
                                    .Filter::escapeForHtml($username).'</option>'."\n";
                            }
                            ?>
                        </select>
                    </td>
                    <td>
                        <a href="#" id="extract-settings-help-link" class="etl-help" style="margin-left: 10px;">?</a>
                        <div id="extract-settings-help" title="Extract Settings" style="display: none; clear: both;">
                            <?php echo Help::getHelpWithPageLink('extract-settings', $module); ?>
                        </div> 
                    </td>
                </tr>
                
                <?php #} # END else ?>



                <tr>
                    <td>
                    API token status
                    </td>
                    <td>
                        <?php

                        echo "<div style=\"border: 1px solid #AAAAAA; margin-bottom: 4px;"
                            ." padding: 4px; border-radius: 4px;\">\n";
                        
                        
                        $apiUrl = $module->getRedCapApiUrl();
                        #--------------------------------------------------------------------------------------------
                        # If the configurations API URL doesn't match the project's API URL -
                        # this case should only be possible for admins in test mode and indicates
                        # admin is entering information token information for a remote system
                        #--------------------------------------------------------------------------------------------
                        if (strcasecmp(trim($properties[Configuration::REDCAP_API_URL]), trim($apiUrl)) !== 0) {
                            echo '<span style="color: navy; font-weight: bold;">?</span>&nbsp;&nbsp;';
                            if (empty($properties[Configuration::DATA_SOURCE_API_TOKEN])) {
                                echo "No REDCap API token specified.";
                            } elseif (empty($properties[Configuration::REDCAP_API_URL])) {
                                echo "No REDCap API URL specified.";
                            } else {
                                echo "Non-local REDCap API URL specified - no API token information available.";
                            }
                        } else {
                            if (count($apiTokens) < 1) {
                                echo '<img alt="X" style="color: red; font-weight: bold;" src='
                                    .APP_PATH_IMAGES.'cross.png>&nbsp;&nbsp;';
                                echo "There are no API tokens for this project that have"
                                    ." the same data export rights as this configuration."
                                    ."<br /><br />"
                                    ."An API token needs to be requested "
                                    ." by a user whose data export rights matches those of the configuration.";
                            } elseif (empty($apiTokenUser)) {
                                echo '<img alt="X" style="color: red; font-weight: bold;" src='
                                    .APP_PATH_IMAGES.'cross.png>&nbsp;&nbsp;';
                                echo "No user's API token has been selected for this project."
                                    ."<br /><br />"
                                    ."You need to select an API token user"
                                    ." (whose API token will be used to access REDCap).";
                            } else {
                                # If there is an API token and it has export permission
                                echo '<img alt="OK" style="color: green; font-weight: bold;" src='
                                    .APP_PATH_IMAGES.'tick.png>&nbsp;&nbsp;';
                                echo 'The API token for user "'.Filter::escapeForHtml($apiTokenUser).'"'
                                    .', which has export permission,'
                                    .' has been selected.';
                            }
                        }

                        echo "</div>\n";
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </fieldset>

    <fieldset class="config">
        <legend>Transform Settings</legend>
        <table>
            <tbody style="padding: 20px;">
                <!-- TRANSFORMATION RULES -->
                <tr>
                    <td style="padding-right: 1em;">Transformation rules</td>
                    <td>
                        <?php
                        $rules = $properties[Configuration::TRANSFORM_RULES_TEXT];
                        $rulesName = Configuration::TRANSFORM_RULES_TEXT;
                        ?>
                        <textarea rows="14" cols="70"
                            style="margin-top: 4px; margin-bottom: 4px;"
                            name="<?php echo $rulesName;?>"><?php echo Filter::escapeForHtml($rules);?></textarea>
                    </td>
                    <td>
                        <p><input type="submit" name="submitValue" value="Auto-Generate"></p>
                        <p>
                            <button type="submit" value="Upload CSV file"
                                    name="submitValue" style="vertical-align: middle;">
                                <img src="<?php echo APP_PATH_IMAGES.'csv.gif';?>" alt=""> Upload CSV file
                            </button>
                            <input type="file" name="uploadCsvFile" id="uploadCsvFile" style="display: inline;">
                        </p>
                        <p>
                            <button type="submit" value="Download CSV file" name="submitValue">
                                <img src="<?php echo APP_PATH_IMAGES.'csv.gif';?>" alt=""
                                    style="vertical-align: middle;">
                                <span  style="vertical-align: middle;"> Download CSV file</span>

                            </button>
                        </p>
                        <p>
                            <button type="submit" id="check-rules-button" value="Check Rules" name="submitValue">
                                <div>
                                <span class="fas fa-check-circle etl-rules-check-icon" 
                                    aria-hidden="true" style="vertical-align: middle;"></span>
                                <span>Check Rules</span>
                                </div>
                            </button>
                        </p>                    
                        <p>
                            <a href="<?php echo $module->getUrl('web/transformation_rules.php');?>" target="_blank">
                                <i class="fas fa-book"></i>
                                Transformation Rules Guide
                            </a>
                        </p>
                    </td>
                </tr>                
            </tbody>
        </table>
    </fieldset>
    
    <fieldset class="config">
        <legend>Load Settings</legend>
       
        <table>
            <tbody style="padding: 20px;">
                <!-- DATABASE HOST -->
                <tr>
                    <td>Database host</td>
                    <td><input type="text" name="<?php echo Configuration::DB_HOST;?>"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_HOST])?>"/>
                    </td>
                    <td>
                        <a href="#" id="load-settings-help-link" class="etl-help" title="help">?</a>
                        <div id="load-settings-help" title="Load Settings" style="display: none; clear: both;">
                            <?php echo Help::getHelpWithPageLink('load-settings', $module); ?>
                        </div> 
                    </td>
                </tr>

                <!-- DATABASE NAME -->
                <tr>
                    <td>Database name</td>
                    <td><input type="text" name="<?php echo Configuration::DB_NAME;?>"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_NAME])?>"></td>
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
                        <input type="checkbox" id="showDbPassword" style="vertical-align: middle; margin: 0;">
                        <span style="vertical-align: middle;">Show</span>
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
        <legend>Post-Processing SQL</legend>        
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
#----------------------------------------------
# Parse Result (for rules check)
#----------------------------------------------

$status = $parseResult[0];
$parseMessages = nl2br($parseResult[1]);

$class = '';
if (strcasecmp($status, 'valid') === 0) {
    $class = ' class="darkgreen" ';
} elseif (strcasecmp($status, 'warn') === 0) {
    $class = ' class="yellow" ';
    $status = 'warning';
} elseif (strcasecmp($status, 'error') === 0) {
    $class = ' class="red" ';
}


echo '<div id ="parse-result" style="display: none;" title="Transformation Rules Check">'."\n";
echo '<div '.$class.'>'."\n";
echo '<strong>Status: '.$status."</strong>\n";
echo '</div><br/>'."\n";
echo $parseMessages."\n";
echo '</div>'."\n";

if (!empty($parseResult)) {
?>

<script>
    $('#parse-result').dialog({dialogClass: 'etl-rules-check', width: '500px'})
        dialog('widget').position({my: 'right', at: 'right', of: '#check-rules-button'})
    ;
</script>

<?php
}  // End if parse result not empty
?>


<?php
#------------------------------------------------------
# End, if configuration is not empty
#------------------------------------------------------
}
?>




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

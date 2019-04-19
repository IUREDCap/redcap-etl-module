<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\REDCapETL\EtlRedCapProject;

use IU\RedCapEtlModule\Authorization;
use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

#--------------------------------------------------------------
# If the user doesn't have permission to access REDCap-ETL for
# this project, redirect them to the access request page which
# should display a link to send e-mail to request permission.
#--------------------------------------------------------------
if (!Authorization::hasEtlProjectPagePermission($module, USERID)) {
    $requestAccessUrl = $module->getUrl('web/request_access.php');
    header('Location: '.$requestAccessUrl);
}

$success = '';
$warning = '';
$error   = '';

#-------------------------------------------------------------------
# Check for test mode (which should only be used for development)
#-------------------------------------------------------------------
$testMode = false;
if (@file_exists(__DIR__.'/../test-config.ini')) {
    $testMode = true;
}

if (array_key_exists('success', $_GET)) {
    $success = $_GET['success'];
}

if (array_key_exists('warning', $_GET)) {
    $warning = $_GET['warning'];
}

$listUrl  = $module->getUrl("web/index.php");
$selfUrl  = $module->getUrl("web/configure.php");
$generateRulesUrl = $module->getUrl('web/generate_rules.php');

$adminConfig = $module->getAdminConfig();

$configuration = null;

/** @var array configurations property map from property name to value */
$properties = array();


$redCapDb = new RedCapDb();


#-------------------------------------------
# Get the configuration
#-------------------------------------------
$configName = '';
$configuration = $module->getConfigurationFromRequest();
if (!empty($configuration)) {
    $configName = $configuration->getName();
    $properties = $configuration->getProperties();
}



if (!empty($configuration)) {
    #--------------------------------------------------------------
    # Get the API tokens for this project with export permission,
    # and the username of user whose API token should be used
    # (if any)
    #--------------------------------------------------------------
    $apiTokens    = $redCapDb->getApiTokensWithSameExportPermissionAsUser(USERID, PROJECT_ID);
    $apiTokenUser = $configuration->getProperty(Configuration::API_TOKEN_USERNAME);
    
    
    #-------------------------
    # Get the submit value
    #-------------------------
    $submitValue = '';
    if (array_key_exists('submitValue', $_POST)) {
        $submitValue = $_POST['submitValue'];
    }
    
    #---------------------------------------------------------------
    # if this is a POST other than Cancel,
    # update the configuration properties with the POST values
    #---------------------------------------------------------------
    if (!empty($submitValue) && strcasecmp($submitValue, 'Cancel')) {
        array_map('trim', $_POST);
        if (!isset($_POST[Configuration::API_TOKEN_USERNAME])) {
            $_POST[Configuration::API_TOKEN_USERNAME] = '';
        }
        $configuration->set($_POST);
        
        # If this is NOT a remote REDCap configuration, set SSL certificate verification
        # to the global value (this can only be set in the configuration for remote
        # REDCap configurations)
        if (!$configuration->isRemoteRedcap()) {
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
            $fileContents = file_get_contents($_FILES['uploadCsvFile']['tmp_name']);
            if ($fileContents === false) {
                $error = 'ERROR: Unable to upload transformation rules file "'
                    .$_FILES['uploadCsvFile']['tmp_name'].'"\n"';
            } else {
                $properties[Configuration::TRANSFORM_RULES_TEXT] = $fileContents;
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
                $dataProject = new \IU\REDCapETL\EtlRedCapProject($apiUrl, $dataToken);
                // ADD ...$sslVerify = true, $caCertFile = null);
                        
                $rulesGenerator = new \IU\REDCapETL\RulesGenerator();
                $rulesText = $rulesGenerator->generate($dataProject);
                $properties[Configuration::TRANSFORM_RULES_TEXT] = $rulesText;
                #print "$rulesText\n";
            }
        }
    } catch (\Exception $exception) {
        $error = 'ERROR: '.$exception->getMessage();
    }
}  // END - if configuration is not empty
?>


<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

?>

<?php


#print "<pre>\n";
#print_r($_POST);
#print "</pre>\n";


#print '<br/>TRANSFORM RULES: '.$properties[Configuration::TRANSFORM_RULES_TEXT]."<br/>\n";
#print "submitValue {$submitValue}\n";
#print "PROJECTS:<br />\n";
#while ($row = db_fetch_assoc($q)) {
#    print $row['project_id']." ".$row['app_title']." ".$row['api_token']."<br />";
#}
#print "Properties text: <pre>\n".$configuration->getRedCapEtlPropertiesText()."</pre>\n";
#print "Transformation rules text: <pre>\n".$configuration->getTransformationRulesText()."</pre>\n";
#print "<pre>_POST\n"; print_r($_POST); print "</pre>\n";
#print "<pre>\n"; print_r($properties); print "</pre>\n";
#print "<pre>_FILES\n"; print_r($_FILES); print "</pre>\n";
#$tmp = $_FILES['uploadCsvFile']['tmp_name'];
#print "tmp file: {$tmp}<br />\n";
#$fileContents = file_get_contents($_FILES['uploadCsvFile']['tmp_name']);
#print "\nCONTENTS: <pre>{$fileContents}</pre>\n\n";

#$rights = $module->getUserRights();
#print "<pre>\n";
#print_r($rights);
#print "</pre>\n";

 
?>

<div class="projhdr"> 
    <img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
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
} elseif (!Authorization::hasEtlConfigurationPermission($module, $configuration, USERID)) {
    echo '<div class="red" style="margin-top:12px;">'
        .'<p style="text-align: center;">You do not have data export permission to access this configuration.</p>'
        .'</div>';
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

// Show/hide local/remote REDCap rows
$(function() {
    $("input[name=<?php echo Configuration::REMOTE_REDCAP;?>]").change(function() {
        if ($(this).is(':checked')) {
            $('.remoteRow').show();
            $('.localRow').hide();
        } else {
            $('.remoteRow').hide();
            $('.localRow').show();
        }
    });
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

    <table style="background-color: #f0f0f0; border: 1px solid #ccc;">
        <tbody style="padding: 20px;">

            <tr>
                <td colspan="3" style="border: 1px solid #ccc; background-color: #ddd;">
                    <span style="font-weight: bold;">Extract</span>
                </td>
            </tr>

            
            <tr>
                <td>REDCap API URL</td>
                <?php if ($testMode && SUPER_USER) { # make API URL editable ?>
                <td>
                    <input type="text" size="60" 
                           value="<?php echo $properties[Configuration::REDCAP_API_URL];?>"
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
                            echo "There are no API tokens with export permission for this project."
                                ."<br /><br />"
                                ."An API token needs to be requested"
                                ." for this project that has export permission.";
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

            <tr>
                <td colspan="3" style="border: 1px solid #ccc; background-color: #ddd;">
                    <span style="font-weight: bold;">Transform</span>
                <td>
            <tr>

            <tr>
                <td>Transformation rules</td>
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
                            <img src="<?php echo APP_PATH_IMAGES.'csv.gif';?>"> Upload CSV file
                        </button>
                        <input type="file" name="uploadCsvFile" id="uploadCsvFile" style="display: inline;">
                    </p>
                    <p>
                        <button type="submit" value="Download CSV file" name="submitValue">
                            <img src="<?php echo APP_PATH_IMAGES.'csv.gif';?>" style="vertical-align: middle;">
                            <span  style="vertical-align: middle;"> Download CSV file</span>
                        </button>
                    </p>
                </td>
            </tr>

            <tr>
                <td colspan="3" style="border: 1px solid #ccc; background-color: #ddd;">
                    <span style="font-weight: bold;">Load</span>
                <td>
            <tr>

            <tr>
                <td>Database host</td>
                <td><input type="text" name="<?php echo Configuration::DB_HOST;?>"
                    value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_HOST])?>"/>
                </td>
            </tr>

            <tr>
                <td>Database name</td>
                <td><input type="text" name="<?php echo Configuration::DB_NAME;?>"
                    value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_NAME])?>"></td>
            </tr>

            <tr>
                <td>Database username</td>
                <td><input type="text" name="<?php echo Configuration::DB_USERNAME;?>"
                    value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_USERNAME])?>"/></td>
            </tr>

            <tr>
                <td>Database password</td>
                <td>
                    <input type="password" name="<?php echo Configuration::DB_PASSWORD;?>"
                        value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_PASSWORD])?>"
                        id="dbPassword"/>
                    <input type="checkbox" id="showDbPassword" style="vertical-align: middle; margin: 0;">
                    <span style="vertical-align: middle;">Show</span>
                </td>
            </tr>

            <tr>
                <td colspan="3" style="border: 1px solid #ccc; background-color: #ddd;">
                    <span style="font-weight: bold;">Processing</span>
                <td>
            </tr>

            <tr>
                <td>Batch size</td>
                <td><input type="text" name="<?php echo Configuration::BATCH_SIZE;?>"
                    value="<?php echo Filter::escapeForHtml($properties[Configuration::BATCH_SIZE]);?>"/></td>
            </tr>

            <tr style="height: 10px;"></tr> 
 
            <tr>
                <td>Table name prefix</td>
                <td><input type="text" name="<?php echo Configuration::TABLE_PREFIX;?>"
                    value="<?php echo Filter::escapeForHtml($properties[Configuration::TABLE_PREFIX]);?>"/></td>
            </tr>
           
            <!--
            <tr>
                <td><hr style="color: blue; border: 1px solid black;"/></td>
            </tr>
            -->
      
            <tr style="height: 10px;"></tr>
      
            <tr>
                <td>Database logging</td>
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
            </tr>
      
            <tr>
                <td>Database log table</td>
                <td><input type="text" name="<?php echo Configuration::DB_LOG_TABLE;?>"
                    value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::DB_LOG_TABLE]);?>"/>
                </td>
            </tr>
            <tr>
                <td>Database event log table</td>
                <?php $dbEventLogTable = $properties[Configuration::DB_EVENT_LOG_TABLE]; ?>
                <td><input type="text" name="<?php echo Configuration::DB_EVENT_LOG_TABLE;?>"
                    value="<?php echo Filter::escapeForHtmlAttribute($dbEventLogTable);?>"/>
                </td>
            </tr>
      
            <tr style="height: 10px;"></tr>
                  
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
                    <!-- <img title="test2" src="<?php echo APP_PATH_IMAGES ?>help.png"> -->
                </td>
            </tr>

            <tr>
                <td>E-mail summary</td>
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
            
            <tr>
                <td>E-mail subject</td>
                <td><input type="text" name="<?php echo Configuration::EMAIL_SUBJECT;?>" size="64"
                    value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::EMAIL_SUBJECT]);?>"
                    />
                </td>
            </tr>
            <tr>
                <td>E-mail to list</td>
                <td><input type="text" name="<?php echo Configuration::EMAIL_TO_LIST;?>" size="64"
                    value="<?php echo Filter::escapeForHtmlAttribute($properties[Configuration::EMAIL_TO_LIST]);?>"
                    />
                </td>
            </tr>
      
            <tr style="height: 10px;"></tr>
            
            <tr>
                <td>Post-Processing SQL</td>
                <td>
                    <?php
                    $sql = $properties[Configuration::POST_PROCESSING_SQL];
                    $sqlName = Configuration::POST_PROCESSING_SQL;
                    ?>
                    <textarea rows="10" cols="70"
                        style="margin-top: 4px; margin-bottom: 4px;"
                        name="<?php echo $sqlName;?>"><?php echo Filter::escapeForHtml($sql);?></textarea>
                </td>
            </tr>
            
            <tr style="height: 10px;"></tr>
            
            <tr>
                <td style="text-align: center;">&nbsp;</td>
                <td style="text-align: center;">
                    <input type="submit" name="submitValue" value="Save" />
                    <input type="submit" name="submitValue" value="Save and Exit" style="margin-left: 24px;"/>
                    <input type="submit" name="submitValue" value="Cancel" style="margin-left: 24px;" />
                </td>
                <td style="text-align: center;">&nbsp;</td>
            </tr>
            
            <tr style="height: 10px;"></tr>
        </tbody>
  </table>
  <!--</div> -->
    <?php Csrf::generateFormToken(); ?>
</form>

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


<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

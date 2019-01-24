<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\REDCapETL\EtlRedCapProject;

use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

$error = '';

$listUrl  = $module->getUrl("web/index.php");
$selfUrl  = $module->getUrl("web/configure.php");

/** @var array configurations property map from property name to value */
$properties = array();


$redCapDb = new RedCapDb();


#-------------------------------------------
# Get the configuration name
#-------------------------------------------
$configName = $_POST['configName'];
if (empty($configName)) {
    $configName = $_GET['configName'];
    if (empty($configName)) {
        $configName = $_SESSION['configName'];
    }
}


if (!empty($configName)) {
    $_SESSION['configName'] = $configName;
    $configuration = $module->getConfiguration($configName);
    if (!empty($configuration)) {
        $properties = $configuration->getProperties();
    } else {
        # May have changed projects, and session config name
        # does not exist in the new project
        $configName = null;
    }
}


#--------------------------------------------------------------
# Get the API tokens for this project with export permission,
# and the username of user whose API token should be used
# (if any)
#--------------------------------------------------------------
if (!empty($configuration)) {
    $apiTokens    = $redCapDb->getApiTokensWithExportPermission(PROJECT_ID);
    $apiTokenUser = $configuration->getProperty(Configuration::API_TOKEN_USERNAME);
}


#-------------------------
# Set the submit value
#-------------------------
$submit = '';
if (array_key_exists('submit', $_POST)) {
    $submit = $_POST['submit'];
}

$submitValue = '';
if (array_key_exists('submitValue', $_POST)) {
    $submitValue = $_POST['submitValue'];
}

if (strcasecmp($submit, 'Auto-Generate') === 0) {
    # Check for API Token (or just let RedCapEtl class handle?)
    if (!empty($properties)) {
        $apiUrl    = $properties[Configuration::REDCAP_API_URL];
        $dataToken = $properties[Configuration::DATA_SOURCE_API_TOKEN];

        try {
            if (empty($apiUrl)) {
                $error = 'ERROR: No REDCap API URL specified.';
            } elseif (empty($dataToken)) {
                $error = 'ERROR: No data source API token specified.';
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
        } catch (Exception $exception) {
            $error = 'ERROR: '.$exception->getMessage();
        }
    }
} elseif (strcasecmp($submit, 'Cancel') === 0) {
    header('Location: '.$listUrl);
} elseif (strcasecmp($submit, 'Save') === 0) {
    try {
        if (!isset($_POST[Configuration::API_TOKEN_USERNAME])) {
            $_POST[Configuration::API_TOKEN_USERNAME] = '';
        } else {
            $_POST[Configuration::API_TOKEN_USERNAME] = trim($_POST[Configuration::API_TOKEN_USERNAME]);
        }

        $apiUrl = $module->getRedCapApiUrl();
                        
        # If the configuration's API URL matches the API URL of the
        # REDCap instance that is running (which should always be
        # the case for non-admin users)
        if (strcasecmp(trim($properties[Configuration::REDCAP_API_URL]), trim($apiUrl)) === 0) {                
            if (empty($_POST[Configuration::API_TOKEN_USERNAME])) {
                # No API token user was specified, set the API token to blank
                $_POST[Configuration::DATA_SOURCE_API_TOKEN] = '';
            } else {
                if (!array_key_exists($_POST[Configuration::API_TOKEN_USERNAME], $apiTokens)) {
                    # The API token user does not have an API token, so set it to blank
                    $_POST[Configuration::API_TOKEN_USERNAME]    = '';
                    $_POST[Configuration::DATA_SOURCE_API_TOKEN] = '';
                } else {
                    $_POST[Configuration::DATA_SOURCE_API_TOKEN] = $apiTokens[$_POST[Configuration::API_TOKEN_USERNAME]];
                }
            }
        }
    
        $configuration->set($_POST);
        $configuration->validate();
        $module->setConfiguration($configuration);
        header('Location: '.$listUrl);
    } catch (\Exception $exception) {
        $error = 'ERROR: '.$exception->getMessage();
    }
} elseif (strcasecmp($submitValue, 'Upload CSV file') === 0) {
    $fileContents = file_get_contents($_FILES['uploadCsvFile']['tmp_name']);
    if ($fileContents === false) {
        $error = 'ERROR: Unable to upload transformation rules file "'.$_FILES['uploadCsvFile']['tmp_name'].'"\n"';
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
} else {
    // this should be a GET request, initialize with existing database values, if any
}



?>

<?php

$success = '';

#-----------------------------------------------------------------
# API token user and token check
#
# If the configuration properties are not empty, then update the
# API token if it has changed, UNLESS the REDCap API URL
# being used does not match the one for the currently running
# REDCap instance (which only admins should be allowed to do)
#-----------------------------------------------------------------
if (!empty($properties)) {
    # Get API URL and currently stored token for this project, if any
    $apiUrl = $module->getRedCapApiUrl();
                        
    # If the configuration's API URL matches the API URL of the
    # REDCap instance that is running (which should always be
    # the case for non-admin users)
    if (strcasecmp(trim($properties[Configuration::REDCAP_API_URL]), trim($apiUrl)) === 0) {
        if (empty($apiTokenUser)) {
            if (!empty($properties[Configuration::DATA_SOURCE_API_TOKEN])) {
                #--------------------------------------------------------------
                # No API token user is specified, but there is an API token,
                # so remove it
                #--------------------------------------------------------------
                $properties[Configuration::DATA_SOURCE_API_TOKEN] = '';
                $configuration->set($properties);
                $module->setConfiguration($configuration, USERID, PROJECT_ID);
            }
        } elseif (!array_key_exists($apiTokenUser, $apiTokens)) {
            #---------------------------------------------------
            # The specified API token user no longer has an API
            # token for this project with export permission, so
            # delete this user as the API token user and clear
            # the API token
            #---------------------------------------------------
            $properties[Configuration::API_TOKEN_USERNAME] = '';
            $properties[Configuration::DATA_SOURCE_API_TOKEN] = '';
            $configuration->set($properties);
            $module->setConfiguration($configuration, USERID, PROJECT_ID);
            $success = 'User "'.$apiTokenUser.'", who was specified as the API token user, no longer'
                .' has an API token for this project with export permissions. This user has been'
                .' removed as the API token user.';
        } else {
            $apiToken = $apiTokens[$apiTokenUser];

            #-----------------------------------------------------------
            # There is an API token for the specified user with export
            # permission, but it has changed, so update the API token
            #-----------------------------------------------------------
            if (strcasecmp(trim($properties[Configuration::DATA_SOURCE_API_TOKEN]), $apiToken) !== 0) {
                $properties[Configuration::DATA_SOURCE_API_TOKEN] = $apiToken;
                $configuration->set($properties);
                $module->setConfiguration($configuration, USERID, PROJECT_ID);
                $success = 'The API token for user "'.$apiTokenUser
                .'" has changed, and has been automatically updated in the configuration.';
            }
        }
    }
}

?>


<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
?>

<?php
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

?>

<div class="projhdr"> 
    <img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>


<?php

$module->renderProjectPageContentHeader($selfUrl, $error, $success);

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
    $values = $module->getConfigurationNames();
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
</form>

<!-- Configuration part (displayed if the configuration name is set) -->
<?php
if (!empty($configName)) {
?>

<!-- Rules overwrite dialog -->
<div id="rules-overwrite-dialog" style="display:none;" title="Overwrite transformation rules?">
  Test...
</div>

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

    <input type="hidden" name="configName" value="<?php echo $configName; ?>" />
    
    <input type="hidden" name="<?php echo Configuration::CONFIG_API_TOKEN; ?>"
           value="<?php echo $properties[Configuration::CONFIG_API_TOKEN]; ?>" />
           
    <input type="hidden" name="<?php echo Configuration::TRANSFORM_RULES_SOURCE; ?>"
           value="<?php echo $properties[Configuration::TRANSFORM_RULES_SOURCE]; ?>" />
         
    <!--<div style="padding: 10px; border: 1px solid #ccc; background-color: #f0f0f0;"> -->

    <table style="background-color: #f0f0f0; border: 1px solid #ccc;">
        <tbody style="padding: 20px;">

            <tr>
                <td colspan="3" style="border: 1px solid #ccc; background-color: #ddd;">
                    <span style="font-weight: bold;">Extract</span>
                </td>
            </tr>

            <?php if (SUPER_USER) { ?>
            <tr>
                <td>REDCap API URL</td>
                <td>
                    <input type="text" size="60" 
                           value="<?php echo $properties[Configuration::REDCAP_API_URL];?>"
                           name="<?php echo Configuration::REDCAP_API_URL?>" />
                </td>
            </tr>

            <tr>
                <td>
                Project API Token
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
      
            <tr>
                <td>
                SSL Certificate Verification&nbsp;
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
            <?php } else { # end if (SUPER_USER) ?>
            <tr>
                <td>REDCap API URL</td>
                <td>
                    <div style="border: 1px solid #AAAAAA; margin: 4px 0px; padding: 4px; border-radius: 4px;">
                    <?php echo Filter::escapeForHtml($properties[Configuration::REDCAP_API_URL]); ?>
                    </div>
                </td>
            </tr>
            <?php } ?>


                  
            <tr>
                <td>API token user:</td>
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
            <tr>
                <td>
                API Token Status
                </td>
                <td>
                    <?php

                    echo "<div style=\"border: 1px solid #AAAAAA; margin-bottom: 4px;"
                        ." padding: 4px; border-radius: 4px;\">\n";
                    
                    
                    $apiUrl = $module->getRedCapApiUrl();
                    # If the configurations API URL doesn't match the project's API URL -
                    # this case should only be possible for admins and indicates
                    # admin is entering information token information for a remote system
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
                            echo 'The API token for user "'.$apiTokenUser.'", which has export permission,'
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
                <td>Transformation Rules&nbsp;</td>
                <td>
                    <?php
                    $rules = $properties[Configuration::TRANSFORM_RULES_TEXT];
                    $rulesName = Configuration::TRANSFORM_RULES_TEXT;
                    ?>
                    <textarea rows="14" cols="70" style="margin-top: 4px; margin-bottom: 4px;"
                              name="<?php echo $rulesName;?>"><?php echo Filter::escapeForHtml($rules);?>
                    </textarea>
                </td>
                <td>
                    <p><input type="submit" name="submit" value="Auto-Generate"></p>
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
                <td><input type="text" name="<?php echo Filter::escapeForHtmlAttribute(Configuration::BATCH_SIZE);?>"
                    value="<?php echo $properties[Configuration::BATCH_SIZE];?>"/></td>
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
            <tr>
                <td style="text-align: center;"><input type="submit" name="submit" value="Save" /></td>
                <td style="text-align: center;"><input type="submit" name="submit" value="Cancel" /></td>
            </tr>
        </tbody>
  </table>
  <!--</div> -->
</form>

<?php } ?>

<?php
// See JSON output of properties for REDCap-ETL
/*
if (isset($configuration)) {
    $json = $configuration->getRedCapEtlJsonProperties();
    $json = json_encode(json_decode($json), JSON_PRETTY_PRINT);
    print "<pre>\n";
    print_r($json);
    print "</pre>\n";
}
*/
?>

<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

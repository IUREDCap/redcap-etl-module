<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__.'/../dependencies/autoload.php';

use IU\REDCapETL\EtlRedCapProject;

use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

$error = '';

$listUrl  = $module->getUrl("web/index.php");
$selfUrl  = $module->getUrl("web/configure.php");


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
}

if (!empty($configName)) {
    $configuration = $module->getConfiguration($configName);
    if (!empty($configuration)) {
        $properties = $configuration->getProperties();
        
        # Set the API token property, if it is empty and the project
        # has an API token for this user.
        if (empty($properties[Configuration::DATA_SOURCE_API_TOKEN]) && !empty(PROJECT_ID)) {
            $redCapDb = new RedCapDb();
            $apiToken = $redCapDb->getApiToken(USERID, PROJECT_ID);
            if (!empty(api_token)) {
                $properties[Configuration::DATA_SOURCE_API_TOKEN] = $apiToken;
            }
        }
    } else {
        # May have changed projects, and session config name
        # does not exist in the new project
        $configName = null;
    }
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
                # print "$rulesText\n";
            }
        } catch (Exception $exception) {
            $error = 'ERROR: '.$exception->getMessage();
        }
    }
} elseif (strcasecmp($submit, 'Cancel') === 0) {
    header('Location: '.$listUrl);
} elseif (strcasecmp($submit, 'Save') === 0) {
    #$configuration = new Configuration($configName);

    try {
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
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
?>

<?php
#print "submitValue {$submitValue}\n";
#print "PROJECTS:<br />\n";
#while ($row = db_fetch_assoc($q)) {
#    print $row['project_id']." ".$row['app_title']." ".$row['api_token']."<br />";
#}
#print "Properties text: <pre>\n".$configuration->getRedCapEtlPropertiesText()."</pre>\n";
#print "Transformation rules text: <pre>\n".$configuration->getTransformationRulesText()."</pre>\n";
#print "<pre>_POST\n"; print_r($_POST); print "</pre>\n";
#print "<pre>_FILES\n"; print_r($_FILES); print "</pre>\n";
#$tmp = $_FILES['uploadCsvFile']['tmp_name'];
#print "tmp file: {$tmp}<br />\n";
#$fileContents = file_get_contents($_FILES['uploadCsvFile']['tmp_name']);
#print "\nCONTENTS: <pre>{$fileContents}</pre>\n\n";

?>

<div class="projhdr"> 
    <img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>


<?php $module->renderUserTabs($selfUrl); ?>

<?php
#----------------------------
# Display error, if any
#----------------------------
if (!empty($error)) { ?>
<div class="red" style="margin:20px 0;font-weight:bold;">
    <img src="/redcap/redcap_v8.5.11/Resources/images/exclamation.png">
    <?php echo $error; ?>
    </div>
<?php } ?>


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
    $values = $module->getUserConfigurationNames();
    array_unshift($values, '');
    foreach ($values as $value) {
        if (strcmp($value, $configName) === 0) {
            echo '<option value="'.$value.'" selected>'.$value."</option>\n";
        } else {
            echo '<option value="'.$value.'">'.$value."</option>\n";
        }
    }
    ?>
    </select>
</form>

<!-- Configuration part (displayed if the configuration name is set) -->
<?php if (!empty($configName)) { ?>
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
<form action="<?php echo $selfUrl;?>" method="post" enctype="multipart/form-data" style="margin-top: 17px;">

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

      <tr>
        <td>REDCap API URL</td>
        <td>
          <input type="text" size="40" 
                 value="<?php echo $properties[Configuration::REDCAP_API_URL];?>"
                 name="<?php echo Configuration::REDCAP_API_URL?>" />
        </td>
      </tr>

      <tr>
        <td>
          Project API Token
        </td>
        <td>
          <input type="password" size="34" value="<?php echo $properties[Configuration::DATA_SOURCE_API_TOKEN];?>"
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
          SSL Certificate Verification
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
        <td colspan="3" style="border: 1px solid #ccc; background-color: #ddd;">
            <span style="font-weight: bold;">Transform</span>
        <td>
      <tr>

      <tr>
        <td>Transformation Rules</td>
        <td>
            <?php
            $rules = $properties[Configuration::TRANSFORM_RULES_TEXT];
            $rulesName = Configuration::TRANSFORM_RULES_TEXT;
            ?>
            <textarea rows="14" cols="70" name="<?php echo $rulesName;?>"><?php echo $rules;?></textarea>
        </td>
        <td>
          <p><input type="submit" name="submit" value="Auto-Generate"></p>
          <p>
          <button type="submit" value="Upload CSV file" name="submitValue" style="vertical-align: middle;">
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
                   value="<?php echo $properties[Configuration::DB_HOST]?>"/></td>
      </tr>

      <tr>
        <td>Database name</td>
        <td><input type="text" name="<?php echo Configuration::DB_NAME;?>"
                   value="<?php echo $properties[Configuration::DB_NAME]?>"></td>
      </tr>

      <tr>
        <td>Database username</td>
        <td><input type="text" name="<?php echo Configuration::DB_USERNAME;?>"
                   value="<?php echo $properties[Configuration::DB_USERNAME]?>"/></td>
      </tr>

      <tr>
        <td>Database password</td>
        <td>
          <input type="password" name="<?php echo Configuration::DB_PASSWORD;?>"
                 value="<?php echo $properties[Configuration::DB_PASSWORD]?>" id="dbPassword"/>
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
                   value="<?php echo $properties[Configuration::BATCH_SIZE];?>"/></td>
      </tr>

      <tr>
        <td>E-mail from address</td>
        <td><input type="text" name="<?php echo Configuration::EMAIL_FROM_ADDRESS;?>" size="44"
                   value="<?php echo $properties[Configuration::EMAIL_FROM_ADDRESS];?>"/></td>
      </tr>
      <tr>
        <td>E-mail subject</td>
        <td><input type="text" name="<?php echo Configuration::EMAIL_SUBJECT;?>" size="64"
                   value="<?php echo $properties[Configuration::EMAIL_SUBJECT];?>"
                   />
        </td>
      </tr>
      <tr>
        <td>E-mail to list</td>
        <td><input type="text" name="<?php echo Configuration::EMAIL_TO_LIST;?>" size="64"
                   value="<?php echo $properties[Configuration::EMAIL_TO_LIST];?>"
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



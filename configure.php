<?php

require_once __DIR__.'/Configuration.php';
require_once __DIR__.'/redcap-etl/dependencies/autoload.php';

use IU\RedCapEtlModule\Configuration;

$error = '';

$redCapEtlModule = new \IU\RedCapEtlModule\RedCapEtlModule();

$listUrl  = $redCapEtlModule->getUrl("index.php");
$userInfo = $redCapEtlModule->getUserInfo();
$selfUrl  = $redCapEtlModule->getUrl("configure.php");


$configName = $_POST['configName'];
if (empty($configName)) {
    $configName = $_GET['configName'];
}

if (!empty($configName)) {
    $configuration = $redCapEtlModule->getConfiguration($configName);
    $properties = $configuration->getProperties();
}

#-------------------------------
# Get API token information
#-------------------------------
$sql = "select p.project_id, p.app_title, ur.api_token "
    . " from redcap_user_rights ur, redcap_projects p"
    . " where ur.project_id = p.project_id "
    . " and ur.username = '".USERID.'"'
    . " and ur.api_export = 1 "
    . " order by p.app_title "
    ;
$q = db_query($sql);

#-------------------------
# Set the submit value
#-------------------------
$submit = '';
if (array_key_exists('submit', $_POST)) {
    $submit = $_POST['submit'];
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
                $dataProject = new \IU\REDCapETL\EtlRedCapProject($apiUrl, $dataToken); // ADD ...$sslVerify = true, $caCertFile = null);
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
    $configuration = new Configuration($configName);

    try {
        $configuration->set($_POST);
        $redCapEtlModule->setConfiguration($configuration);
        header('Location: '.$listUrl);
    } catch (Exception $exception) {
        $error = 'ERROR: '.$exception->getMessage();
    }

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
#print "PROJECTS:<br />\n";
#while ($row = db_fetch_assoc($q)) {
#    print $row['project_id']." ".$row['app_title']." ".$row['api_token']."<br />";
#}
?>

<div class="projhdr"> 
    <img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png">REDCap-ETL
</div>


<?php $redCapEtlModule->renderUserTabs($selfUrl); ?>

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
# Configuration selection form
?>
<form action="<?php echo $selfUrl;?>" method="post" style="padding: 4px; margin-bottom: 0px; border: 1px solid #ccc; background-color: #ccc;">
    <span style="font-weight: bold;">Configuration:</span>
    <select name="configName" onchange="this.form.submit()">
    <?php
    $values = $redCapEtlModule->getUserConfigurationNames();
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
          
<!-- Configuration form -->
<form action="<?php echo $selfUrl;?>" method="post">
  <input type="hidden" name="configName" value="<?php echo $configName; ?>" />
  <input type="hidden" name="<?php echo Configuration::CONFIG_API_TOKEN; ?>"
         value="<?php echo $properties[Configuration::CONFIG_API_TOKEN]; ?>" />
  <input type="hidden" name="<?php echo Configuration::TRANSFORM_RULES_SOURCE; ?>"
         value="<?php echo $properties[Configuration::TRANSFORM_RULES_SOURCE]; ?>" />

  <div style="padding: 10px; border: 1px solid #ccc; background-color: #f0f0f0;">

  <table>
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
          Data Project API Token
        </td>
        <td>
          <input size="32" value="<?php echo $properties[Configuration::DATA_SOURCE_API_TOKEN];?>"
                           name="<?php echo Configuration::DATA_SOURCE_API_TOKEN;?>" type="text" />
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
        <td colspan="3" style="border: 1px solid #ccc; background-color: #ddd;"><span style="font-weight: bold;">Transform</span><td>
      <tr>

      <tr>
        <td>Transformation Rules</td>
        <td>
          <?php $rules = $properties[Configuration::TRANSFORM_RULES_TEXT];?>
          <textarea rows="14" cols="70" name="<?php echo Configuration::TRANSFORM_RULES_TEXT;?>"><?php echo $rules;?></textarea>
        </td>
        <td><input type="submit" name="submit" value="Auto-Generate"</td>
      </tr>

      <tr>
        <td colspan="3" style="border: 1px solid #ccc; background-color: #ddd;"><span style="font-weight: bold;">Load</span><td>
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
        <td><input type="text" name="<?php echo Configuration::DB_PASSWORD;?>"
                   value="<?php echo $properties[Configuration::DB_PASSWORD]?>"/></td>
      </tr>

      <tr>
        <td colspan="3" style="border: 1px solid #ccc; background-color: #ddd;"><span style="font-weight: bold;">Processing</span><td>
      </tr>

      <tr>
        <td>Batch size</td>
        <td><input type="text" name="<?php echo Configuration::BATCH_SIZE;?>"
                   value="<?php echo $properties[Configuration::BATCH_SIZE];?>"/></td>
      </tr>

      <tr>
        <td style="text-align: center;"><input type="submit" name="submit" value="Save" /></td>
        <td style="text-align: center;"><input type="submit" name="submit" value="Cancel" /></td>
      </tr>
    </tbody>
  </table>
  </div>
</form>

<?php } ?>


<?php include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>



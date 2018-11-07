<?php
use IU\RedCapEtlModule\Configuration;

#----------------------------------------------
# WARNING: this page can be accessed directly,
# so don't put any code in here that retrieves
# data from, or stores data to, the database.
#---------------------------------------------- 
?>


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


<!-- Configuration form -->
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
          <input type="text" size="52" 
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
            $checked = '';
            if ($properties[Configuration::SSL_VERIFY]) {
                $checked = ' checked ';
            }
            ?>
          <input type="checkbox" name="<?php echo Configuration::SSL_VERIFY?>" <?php echo $checked;?> >
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
                   value="<?php echo $properties[Configuration::EMAIL_SUBJECT];?>"/></td>
      </tr>
      <tr>
        <td>E-mail to list</td>
        <td><input type="text" name="<?php echo Configuration::EMAIL_TO_LIST;?>" size="64"
                   value="<?php echo $properties[Configuration::EMAIL_TO_LIST];?>"/></td>
      </tr>

      <tr>
        <td style="text-align: center;"><input type="submit" name="submit" value="Save" /></td>
        <td style="text-align: center;"><input type="submit" name="submit" value="Cancel" /></td>
      </tr>
    </tbody>
  </table>
  <!--</div> -->
</form>

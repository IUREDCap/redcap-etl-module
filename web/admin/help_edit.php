<?php

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

#---------------------------------------------
# Check that the user has access permission
#---------------------------------------------
$module->checkAdminPagePermission();

require_once __DIR__.'/../../dependencies/autoload.php';

use \IU\REDCapETL\Version;

use \IU\RedCapEtlModule\Csrf;
use \IU\RedCapEtlModule\Filter;
use \IU\RedCapEtlModule\Help;
use \IU\RedCapEtlModule\RedCapEtlModule;

try {
    $selfUrl     = $module->getUrl(RedCapEtlModule::HELP_EDIT_PAGE);
    
    $helpInfoUrl = $module->getUrl('web/admin/help_info.php');

    $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);

    if (strcasecmp($submitValue, 'Save') === 0) {
        $success = "Help saved.";
    }
} catch (Exception $exception) {
    $error = 'ERROR: '.$exception->getMessage();
}
    
?>

<?php #require_once APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>

<?php
#---------------------------------------------
# Include REDCap's control center page header
#---------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
?>

<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png">REDCap-ETL Admin</h4>


<?php

$module->renderAdminPageContentHeader($selfUrl, $error, $warning, $success);

?>

<?php
#print "<pre>POST:\n"; print_r($_POST); print "</pre>\n";
?>

<form action="<?php echo $selfUrl;?>" method="post">
    

  <fieldset class="server-config" style="margin-top: 12px;">
    <legend>Help</legend>
      Topic:
      <select id="help-select">
        <?php
        $topics = Help::getTopics();
        foreach ($topics as $topic) {
            echo '    <option value="'.$topic.'">'.$topic.'</option>'."\n";
        }
        ?>
    </select>

    <!-- Help text selection -->
    <select>
      <option value="default">Use default text</option>
      <option value="custom">Use custom text</option>
      <option value="replace">Prepend custom text to default</option>
      <option value="replace">Append custom text to default</option>
    </select>
    
    <button id="previewButton" style="float: right;">Preview</button>
    
    <hr style="clear: both;"/>

    <table style="margin-top: 12px; width: 100%;">
      <tr>
        <th style="width: 40%;">Default</th> <th style="width: 40%;">Custom</th>
      </tr>
      <tr style="vertical-align: top;">
        <td>
          <div id="help-text" style="padding: 4px; border: 1px solid black; background-color: #FFFFFF;">
            <?php echo Help::getHelpHtml($topics[0], $module); ?>
          </div>
        </td>
        <td>
          <textarea rows="10" style="width: 100%;">
          </textarea>
        </td>
      </tr>
    </table>
  </fieldset>

  <script type="text/javascript">
      $('#help-select').change(function(event) {
          $.get("<?php echo $helpInfoUrl;?>", { topic: $('#help-select').val() },
              function(data) {
                  $('#help-text').html(data);
              }
        );
    });
  </script>

  <p>
    <input type="submit" name="submitValue" value="Save">
  </p>
    <?php Csrf::generateFormToken(); ?>
</form>

<?php
#print "<pre>\n"; print_r($cronJobs); print "</pre>\n";
?>

<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

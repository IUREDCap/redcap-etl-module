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

$topic = Filter::sanitizeButtonLabel($_GET['topic']);
if (empty($topic)) {
    $topic = Filter::sanitizeButtonLabel($_POST['topic']);
}

$helpSetting = $module->getHelpSetting($topic);
$defaultHelp = Help::getDefaultHelp($topic);
$customHelp  = Help::getCustomHelp($topic, $module);

try {
    $selfUrl     = $module->getUrl(RedCapEtlModule::HELP_EDIT_PAGE);
    
    $helpListUrl = $module->getUrl(RedCapEtlModule::HELP_LIST_PAGE);

    

    $helpInfoUrl = $module->getUrl('web/admin/help_info.php');
    $helpDialogUrl = $module->getUrl('web/help_dialog.php');
    
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

$module->renderAdminPageContentHeader($helpListUrl, $error, $warning, $success);
$module->renderAdminHelpEditSubTabs($selfUrl);

?>

<?php
#print "<pre>POST:\n"; print_r($_POST); print "</pre>\n";
#print "<pre>TOPIC: ".$topic."</pre>"
?>

<!-- TOPIC SELECTION -->
<form action="<?php echo $selfUrl;?>" method="post">
    
  <fieldset class="server-config" style="margin-top: 12px;">
    <legend>Help</legend>
      Topic:
      <select name="topic" onchange="this.form.submit()">
        <?php
        $topics = Help::getTopics();
        array_unshift($topics, '');  # Add blank selection at beginning
        foreach ($topics as $selectTopic) {
            $selected = '';
            if (strcasecmp($selectTopic, $topic) === 0) {
                $selected = "selected";
            }
            echo '    <option value="'.Filter::escapeForHtml($selectTopic).'" '.$selected.'>'
                .Filter::escapeForHtml($selectTopic).'</option>'."\n";
        }
        ?>
    </select>
  </fieldset>
    <?php Csrf::generateFormToken(); ?>
</form>

<form action="<?php echo $selfUrl;?>" method="post">

  <fieldset class="server-config" style="margin-top: 12px;">
    <!-- Help setting selection -->
    <select>
      <option value="<?php echo Help::DEFAULT_TEXT; ?>">Use default text</option>
      <option value="<?php echo Help::CUSTOM_TEXT; ?>">Use custom text</option>
      <option value="<?php echo Help::PREPEND_CUSTOM_TEXT; ?>">Prepend custom text to default</option>
      <option value="<?php echo Help::APPEND_CUSTOM_TEXT; ?>">Append custom text to default</option>
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
            <?php echo Help::getHelpHtml($topic, $module); ?>
          </div>
        </td>
        <td>
          <textarea rows="10" style="width: 100%;"></textarea>
        </td>
      </tr>
    </table>
  </fieldset>

  <script type="text/javascript">
      $('#topicSelect').change(function(event) {
          $.get("<?php echo $helpInfoUrl;?>", { topic: $('#topicSelect').val() },
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


<div id="previewDialog" title="<?php echo 'Preiview: '.$topic; ?>" style="display: none;">
    <?php echo Help::getHelp($topic, $module); ?>
</div>

                    
<script>
    // Help dialog events
    /**
    $(document).ready(function() {
        $( function() {
            $('#previewButton').click(function () {
                var $topic = $('#topicSelect').val();
                var $url = '<?php echo $helpDialogUrl; ?>' + '&topic=' + $topic;
                var $dialog;
                $dialog = $('<div></div>').load($url).dialog();
                
                //alert($url);
                
                $dialog.dialog({title: $topic, dialogClass: 'redcap-etl-help'})
                    .dialog('open')
                    //.position({my: 'left top', at: 'right+20 top', of: $(this)})
                ;
                                
                //$('#previewDialog').dialog({dialogClass: 'redcap-etl-help'})
                //    .dialog('widget').position({my: 'left top', at: 'right+20 top', of: $(this)})
                //    .data("topic", $('#topicSelect').val())
                //;
                return false;
            });
        });
    });
    **/
</script>
<?php
#print "<pre>\n"; print_r($cronJobs); print "</pre>\n";
?>

<div id="helpPreview" title="<?php echo Help::getTitle($topic); ?>" style="display: none;">
    <?php echo Help::getHelp($topic, $module); ?>
</div>
                    
<script>
    // Help dialog events
    $(document).ready(function() {
        $( function() {
            $('#previewButton').click(function () {
                $('#helpPreview').dialog({dialogClass: 'redcap-etl-help'})
                    .dialog('widget').position({my: 'left top', at: 'right+20 top', of: $(this)})
                    ;
                return false;
            });
        });
    });
</script>                    
<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

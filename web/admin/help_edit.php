<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

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
    $helpListUrl = $module->getUrl(RedCapEtlModule::HELP_LIST_PAGE);

    $helpInfoUrl = $module->getUrl('web/admin/help_info.php');
    $helpPreviewUrl = $module->getUrl('web/admin/help_preview.php');
    
    #------------------------------------------------------
    # Get and process the help topic
    #------------------------------------------------------
    $topic = Filter::sanitizeButtonLabel($_GET['topic']);
    if (empty($topic)) {
        $topic = Filter::sanitizeButtonLabel($_POST['topic']);
    }

    if (!Help::isValidTopic($topic)) {
        $topic = '';
    }
    
    $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);

    if (strcasecmp($submitValue, 'Save') === 0) {
        if (empty($topic)) {
            $error = 'No help topic specified.';
            throw new Exception($error);
        } else {
            $helpSetting = Filter::sanitizeInt($_POST['helpSetting']);
            $defaultHelp = Help::getDefaultHelp($topic);
            $customHelp  = Filter::sanitizeHelp($_POST['customHelp']);
            
            if (Help::isValidSetting($helpSetting)) {
                $module->setHelpSetting($topic, $helpSetting);
                $module->setCustomHelp($topic, $customHelp);
                $success = "Help saved.";
            } else {
                $error = 'Invalid help setting specified.';
                throw new Exception($error);
            }
        }
    } if (strcasecmp($submitValue, 'Preivew') === 0) {
        $helpSetting = Filter::sanitizeInt($_POST['helpSetting']);
        $defaultHelp = Help::getDefaultHelp($topic);
        $customHelp = Filter::sanitizeHelp($_POST['customHelp']);
    } elseif (!empty($topic)) {
        $helpSetting = $module->getHelpSetting($topic);
        $defaultHelp = Help::getDefaultHelp($topic);
        $customHelp  = Help::getCustomHelp($topic, $module);
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

<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png" alt="">REDCap-ETL Admin</h4>


<?php

$module->renderAdminPageContentHeader($helpListUrl, $error, $warning, $success);
$module->renderAdminHelpEditSubTabs($selfUrl);

?>


<!-- TOPIC SELECTION -->
<form action="<?php echo $selfUrl;?>" method="post">
    
  <fieldset class="server-config" style="margin-top: 12px;">
    <legend>Help Topic</legend>
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
                .Filter::escapeForHtml(Help::getTitle($selectTopic)).'</option>'."\n";
        }
        ?>
    </select>
  </fieldset>
  
  <fieldset class="server-config" style="margin-top: 12px;">
    <legend>Allowed HTML Tags</legend>
        <?php echo Filter::escapeForHtml(Filter::getAllowedHelpTagsString()); ?>
  </fieldset>
  
    <?php Csrf::generateFormToken(); ?>
</form>



<form action="<?php echo $selfUrl;?>" method="post">

  <fieldset class="server-config" style="margin-top: 12px;">
    <legend>Help Text</legend>
    <input type="hidden" name="topic" value="<?php echo $topic; ?>">
    
    <!-- Help setting selection -->
    <select id="helpSetting" name="helpSetting">
        <?php
        $selected = '';
        if (empty($helpSetting) || $helpSetting === Help::DEFAULT_TEXT) {
            $selected = ' selected ';
        }
        ?>
        <option value="<?php echo Help::DEFAULT_TEXT; ?>" <?php echo $selected; ?>>Use default text</option>
      
        <?php
        $selected = '';
        if ($helpSetting === Help::CUSTOM_TEXT) {
            $selected = ' selected ';
        }
        ?>      
        <option value="<?php echo Help::CUSTOM_TEXT; ?>" <?php echo $selected; ?>>Use custom text</option>
      
        <?php
        $selected = '';
        if ($helpSetting === Help::PREPEND_CUSTOM_TEXT) {
            $selected = ' selected ';
        }
        ?>
        <option value="<?php echo Help::PREPEND_CUSTOM_TEXT; ?>"
            <?php echo $selected; ?>>Prepend custom text to default</option>
      
        <?php
        $selected = '';
        if ($helpSetting === Help::APPEND_CUSTOM_TEXT) {
            $selected = ' selected ';
        }
        ?>   
        <option value="<?php echo Help::APPEND_CUSTOM_TEXT; ?>"
            <?php echo $selected; ?>>Append custom text to default</option>
    </select>
    
    <button id="previewButton" style="float: right;">Preview</button>
    
    <hr style="clear: both;"/>

    <table style="margin-top: 12px; width: 100%;">
      <tr>
        <th style="width: 40%;"><strong>Default Help Text</strong></th>
        <th style="width: 40%;"><strong>Custom Help Text</strong></th>
      </tr>
      <tr style="vertical-align: top;">
        <td>
          <div id="defaultHelp" style="padding: 4px; border: 1px solid black; background-color: #FFFFFF;">
            <?php echo htmlspecialchars($defaultHelp); ?>
          </div>
        </td>
        <td>
          <textarea id="customHelp" name="customHelp" rows="10"
              style="width: 100%;"><?php echo $customHelp; ?></textarea>
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
    $(document).ready(function() {
        $( function() {
            $('#previewButton').click(function () {
                var $topic = "<?php echo Help::getTitle($topic); ?>";
                var $url = '<?php echo $helpPreviewUrl; ?>';
                var $dialog;
                $dialog = $('<div></div>')
                    .load($url, {
                        setting: $('#helpSetting').val(),
                        defaultHelp: "<?php echo $defaultHelp; ?>",
                        customHelp: $('#customHelp').val(),
                        <?php echo Csrf::TOKEN_NAME; ?>: "<?php echo Csrf::getToken(); ?>"
                    }).dialog();
                
                //alert($url);
                
                $dialog.dialog({title: $topic, dialogClass: 'redcap-etl-help', width: 700, maxHeight: 400})
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
</script>
<?php
#print "<pre>\n"; print_r($cronJobs); print "</pre>\n";
?>

<div id="helpPreview" title="<?php echo Help::getTitle($topic); ?>" style="display: none;">
    <?php echo Help::getHelp($topic, $module); ?>
</div>
                    
<script>
    /*
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
    */
</script>                    
<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>

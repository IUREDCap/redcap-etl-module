<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

require_once __DIR__ . '/../dependencies/autoload.php';

use IU\REDCapETL\Database\DbConnectionFactory;

use IU\RedCapEtlModule\Configuration;
use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\Help;
use IU\RedCapEtlModule\RedCapDb;
use IU\RedCapEtlModule\RedCapEtlModule;

$error   = '';
$warning = '';
$success = '';

$newTaskKey = null;
$addProjectId = null;

$deleteTaskKey = null;
$deleteProjectId = null;

$renameTaskKey = null;
$renameProjectId = null;
$renameNewTaskName = null;

$moveTaskKey = null;

$etlConfig = null;
$etlTaskKey = null;
$etlProjectId = null;

$username = USERID;
$superUser = SUPER_USER;

#$workflowName = Filter::escapeForHtml($_GET['workflowName']);
$workflowName = Filter::stripTags($_POST['workflowName']);
if (empty($workflowName)) {
    $workflowName = Filter::stripTags($_GET['workflowName']);
    if (empty($workflowName)) {
        $workflowName = Filter::stripTags($_SESSION['workflowName']);
    }
}
$_SESSION['workflowName'] = $workflowName;



#Get projects that this user has access to
$db = new RedCapDb();
$userProjects = $db->getUserProjects($username);
$availableUserProjects = $userProjects;
array_unshift($availableUserProjects, '');

$selfUrl      = $module->getUrl('web/workflow_configure.php');
                   #. '&workflowName=' . Filter::escapeForUrlParameter($workflowName);
$workflowsUrl = $module->getUrl('web/workflows.php');
$configureUrl = $module->getUrl('web/configure.php');
$taskConfigUrl = $module->getUrl('web/task_configure.php');
$globalPropertiesUrl = $module->getUrl('web/workflow_global_properties.php')
                   . '&workflowName=' . Filter::escapeForUrlParameter($workflowName);

try {
    #-----------------------------------------------------------
    # Check that the user has permission to access this page
    #-----------------------------------------------------------
    $module->checkUserPagePermission(USERID);

    #-----------------------------------------------------------------
    # Process form submissions
    #-----------------------------------------------------------------
    $submitValue = Filter::sanitizeButtonLabel($_POST['submitValue']);

    if (strcasecmp($submitValue, 'add') === 0) {
        #--------------------------------------
        # Add task
        #--------------------------------------
        if (!array_key_exists('newTask', $_POST) || !isset($_POST['newTask'])) {
            $error = 'ERROR: No project was selected.';
        } else {
            $newTaskKey = Filter::stripTags($_POST['newTask']);
            $addProjectId = $availableUserProjects[$newTaskKey];
            $module->addProjectToWorkflow($workflowName, $addProjectId, $username);
        }
    } elseif ((strcasecmp($submitValue, 'up') === 0) || (strcasecmp($submitValue, 'down') === 0)) {
        #----------------------------------------------
        # Move task
        #----------------------------------------------
        $moveTaskKey = Filter::stripTags($_POST['moveTaskKey']);
        if (!empty($moveTaskKey) || ($moveTaskKey == 0)) {
            $module->moveWorkflowTask($workflowName, $submitValue, $moveTaskKey);
        }
    } elseif (strcasecmp($submitValue, 'delete') === 0) {
        #----------------------------------------------
        # Delete task
        #----------------------------------------------
        if (empty($warning) && empty($error)) {
            $deleteTaskKey = $_POST['deleteTaskKey'];
            $deleteProjectId = $_POST['deleteProjectId'];
            if (isset($deleteTaskKey)) {
                $module->deleteTaskfromWorkflow($workflowName, $deleteTaskKey, $deleteProjectId, $username);
            }
        }
    } elseif (strcasecmp($submitValue, 'Workflow Global Properties') === 0) {
        if (empty($warning) && empty($error)) {
            #----------------------------------------------
            # Update properties
            #----------------------------------------------
            header('Location: ' . $globalPropertiesUrl);
            exit();
        }
    } elseif (strcasecmp($submitValue, 'etlConfig') === 0) {
        if (empty($warning) && empty($error)) {
            #----------------------------------------------
            # Specify ETL Config
            #----------------------------------------------
            $etlConfig = $_POST['projectEtlConfigSelect'];
            $etlTaskKey = $_POST['etlTaskKey'];
            $etlProjectId = $_POST['etlProjectId'];
            if (isset($etlTaskKey)) {
                $module->assignWorkflowTaskEtlConfig(
                    $workflowName,
                    $etlProjectId,
                    $etlTaskKey,
                    $etlConfig,
                    $username
                );
            }
        }
    } elseif (strcasecmp($submitValue, 'rename') === 0) {
        if (empty($warning) && empty($error)) {
            #----------------------------------------------
            # Rename task
            #----------------------------------------------
            $renameTaskKey = $_POST['renameTaskKey'];
            $renameProjectId = $_POST['renameProjectId'];
            $renameNewTaskName = $_POST['renameNewTaskName'];
  
            if (isset($renameTaskKey)) {
                #check to see if the new task name is the same as an ETL property name
                $matchFound = $module->checkWorkflowTaskNameAgainstEtlPropertyNames(
                    $renameProjectId,
                    $renameNewTaskName
                );
                if ($matchFound) {
                    $error = 'ERROR: Task new name cannot be set to the name of an existing ETL property. i'
                    . 'Please enter another name for the task.';
                } else {
                    $module->renameWorkflowTask(
                        $workflowName,
                        $renameTaskKey,
                        $renameNewTaskName,
                        $renameProjectId,
                        $username
                    );
                }
            }
        }
    }
} catch (\Exception $exception) {
    $error = 'ERROR: ' . $exception->getMessage();
}

$workflowStatus = $module->getWorkflowStatus($workflowName);

#Get the workflow's updated tasks list
$tasks = $module->getWorkflowTasks($workflowName);
$taskProjectIds = array_column($tasks, 'projectId');
?>

<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="' . $cssFile . '" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    " . $link . "\n</head>", $buffer);
echo $buffer;
?>

<div class="projhdr"> <!--h4 style="color:#800000;margin:0 0 10px;"> -->
<img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>database_table.png" alt="">REDCap-ETL
</div>

<?php
$module->renderProjectPageContentHeader($taskConfigUrl, $error, $warning, $success);
$module->renderUserConfigSubTabs($selfUrl);
?>

<form action="<?php echo $selfUrl;?>" method="post" 
      style="padding: 4px; margin-bottom: 0px; border: 1px solid #ccc; background-color: #ccc;">
    <div>
        <span style="font-weight: bold;">ETL Workflow Configuration:</span>

        <?php
        $excludeIncomplete = false;
        $projectWorkflows = $module->getProjectAvailableWorkflows($pid, $excludeIncomplete);
        array_unshift($projectWorkflows, '');  # make first (default) option blank
        ?>

        <select name="workflowName" onchange="this.form.submit()">
        <?php
        foreach ($projectWorkflows as $value) {
            if (strcmp($value, $workflowName) === 0) {
                echo '<option value="' . Filter::escapeForHtmlAttribute($value) . '" selected>'
                    . Filter::escapeForHtml($value) . "</option>\n";
            } else {
                echo '<option value="' . Filter::escapeForHtmlAttribute($value) . '">'
                    . Filter::escapeForHtml($value) . "</option>\n";
            }
        }
        ?>
        </select>
    </div>
    
    <div>
    <span style="font-weight: bold;">Workflow Status:</span>
        <?php echo $workflowStatus ?>
    </div>
    <?php Csrf::generateFormToken(); ?>
</form>

<script>
    $( function() {
        $( "#accordion" ).accordion({
            active: false,
            collapsible: true,
            heightStyle: "content"
        });
    } );
</script>

<div id="accordion" style="margin-bottom: 22px; width: 50%;">
  <span style="font-weight: bold;">Global Properties</span>
  <div>
    <p>This is a test</p>
    <!-- ====================================
    Global Properties Form
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

  </div>
</div>

<?php
#------------------------------------------------------------
# Add-task form
#------------------------------------------------------------
if (!empty($availableUserProjects)) {
    ?>
    <form action="<?php echo $selfUrl;?>" method="post" style="margin-bottom: 12px;" 
        name="addTaskForm" id="addTaskForm">

    <label for="newTask">REDCap project:</label>
    <select name="newTask" id="newTask">
    <?php
    foreach ($availableUserProjects as $key => $userProject) {
        if ($userProject) {
            echo '<option value="' . Filter::escapeForHtmlAttribute($key) . '">'
                . Filter::escapeForHtml($userProject['project_id'] . '-' . $userProject['app_title']) . "</option>\n";
        } else {
            echo '<option value=""></option>' . "\n";
        }
    }
    ?>
    </select>
    <input type="submit" name="submitValue" value="Add" />
    <br />&nbsp;
    <div>
        <input type="submit" name="submitValue" value="Workflow Global Properties" />
    </div>
    <?php Csrf::generateFormToken(); ?>
</form>
<?php } ?>

<?php
#-----------------------------------------------
# Workflow configuration form
#-----------------------------------------------
?>
<form action="<?php echo $selfUrl;?>" method="post" name="updateWorkflowForm" id="updateWorkflowForm"> 
<input type="hidden" name="username" value="<?php echo Filter::escapeForHtmlAttribute($username);?>">
<input type="hidden" name="workflowName" value="<?php echo Filter::escapeForHtmlAttribute($workflowName);?>">

<hr />

<?php
   $numTasks = count($tasks);
?>

<p>WORKFLOW TASKS</p>
<table class="user-projects">
    <thead>
        <tr>
            <?php
            if ($numTasks > 1) {
                echo "<th></th>";
            }
            ?>
            <th>Task Name</th><th>PID</th><th>Project</th>
            <th>Project ETL Config</th><th>Rename<br/>Task</th>
            <th>Specify<br/>ETL Config</th><th>Delete<br/>Task</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $row = 1;
        foreach ($tasks as $taskKey => $task) {
            $projectId = $task['projectId'];
            if (empty($projectId) && $projectId !== 0) {
                $projectId = "No project Id";
            }
            $taskName = $task['taskName'];
            $pKey = array_search($projectId, array_column($userProjects, 'project_id'));
            $isAssignedUser = false;
            $hasPermissionToExport = false;
            $projectName = null;

            if ($pKey || $pKey === 0) {
                $isAssignedUser = true;
                $hasPermissionToExport = $userProjects[$pKey]['data_export_tool'] == 1 ? true : false;
                $projectName = $userProjects[$pKey]['app_title'];
                $projectEtlConfig = $task['projectEtlConfig'] ? $task['projectEtlConfig'] : "None specified";
            } else {
                $projectName = "(You are not a listed user on this project)";
                $projectEtlConfig = null;
            }

            if ($superUser) {
                $hasPermissionToExport = true;
                $projectName = $projectName ? $projectName : $db->getProjectName($projectId);
                $projectEtlConfig = $task['projectEtlConfig'] ? $task['projectEtlConfig'] : "None specified";
            }

            if ($row % 2 == 0) {
                echo '<tr class="even-row">' . "\n";
            } else {
                echo '<tr class="odd-row">' . "\n";
            }
            
            if ($numTasks > 1) {
                    echo '<td style="text-align:left;"> '
                        . '<input type="image" src="' . APP_PATH_IMAGES . 'arrow_down.png" alt="MOVE DOWN"'
                        . ' class="deleteConfig" style="cursor: pointer;"'
                        . ' id="moveTaskDown' . $row . '"/>';
                    echo'<input type="image" src="' . APP_PATH_IMAGES . 'arrow_up2.png" alt="MOVE UP"'
                        . ' class="deleteConfig" style="margin-left: 5px; cursor: pointer;"'
                        . ' id="moveTaskUp' . $row . '"/> ';
                    echo "</td>\n";
            }

            echo "<td>" . Filter::escapeForHtml($taskName) . "</td>\n";
            echo "<td>" . Filter::escapeForHtml($projectId) . "</td>\n";
            echo "<td>" . Filter::escapeForHtml($projectName) . "</td>\n";
            echo "<td>" . Filter::escapeForHtml($projectEtlConfig) . "</td>\n";

            #-----------------------------------------------------------
            # RENAME TASK BUTTON - disable if user does not have the needed
            # data export permission to access the project
            #-----------------------------------------------------------
            if ($hasPermissionToExport) {
                echo '<td style="text-align:center;">'
                    . '<input type="image" src="' . APP_PATH_IMAGES . 'page_white_edit.png" alt="RENAME TASK"'
                    . ' style="cursor: pointer;"'
                    . ' id="renameTask' . $row
                    . '"/>'
                    . "</td>\n";
            } else {
                echo '<td style="text-align:center;">'
                    . '<img src="' . APP_PATH_IMAGES . 'gear.png" alt="ETL GLOBAL VARIABLES" class="disabled" />'
                    . "</td>";
            }

            #-----------------------------------------------------------
            # SPECIFY ETL CONFIG BUTTON - disable if user does not have the needed
            # data export permission to access the project
            #-----------------------------------------------------------
            if ($hasPermissionToExport) {
                $values = $module->getAccessibleConfigurationNames($projectId);
                echo '<td style="text-align:center;">'
                    . '<input type="image" src="' . APP_PATH_IMAGES . 'page_white_edit.png" alt="RENAME TASK"'
                    . ' style="cursor: pointer;"'
                    . ' id="specifyEtlConfig' . $row
                    . '"/>'
                    . "</td>\n";
            } else {
                echo '<td style="text-align:center;">'
                    . '<img src="' . APP_PATH_IMAGES . 'gear.png" alt="ETL GLOBAL VARIABLES" class="disabled" />'
                    . "</td>";
            }

            #-----------------------------------------------------------
            # DELETE BUTTON - disable if user does not have the needed
            # data export permission to access the project
            #-----------------------------------------------------------
            if ($hasPermissionToExport) {
                echo '<td style="text-align:center;">'
                    . '<input type="image" src="' . APP_PATH_IMAGES . 'delete.png" alt="DELETE"'
                    . ' class="deleteConfig" style="cursor: pointer;"'
                    . ' id="deleteTask' . $row
                    . '"/>'
                    . "</td>\n";
            } else {
                echo '<td style="text-align:center;">'
                    . '<img src="' . APP_PATH_IMAGES . 'delete.png" alt="DELETE" class="disabled" />'
                    . "</td>\n";
            }
            echo "</tr>\n";
            $row++;
        }
        ?>
    </tbody>
</table>
<hr />
<input type="hidden" name="moveTaskKey" id="moveTaskKey" value="">
<input type="hidden" name="submitValue" id="moveTask" value="">
<p>
<div style="clear: both;"></div>
</p>
<?php Csrf::generateFormToken(); ?>
</form>


<?php
#--------------------------------------
# Move task up
#--------------------------------------
?>
<script>
   
    <?php
    # Set up click event handlers for the Move Task Up buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        echo '$("#moveTaskUp' . $row . '").click({key: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($key)
            . '", projectId: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            . '"}, moveTaskUp);' . "\n";
        $row++;
    }
    ?>

    function moveTaskUp(event) {
        var key = event.data.key;
        var submitValue = document.getElementsByName("submitValue");
        event.preventDefault();
        $('#moveTaskKey').val(key);
        $('#moveTask').val('"up"');
        $("#updateWorkflowForm").submit();
        //console.log(document.querySelectorAll("input"));
    }

</script>

<?php
#--------------------------------------
# Move task down
#--------------------------------------
?>
<script>
   
    <?php
    # Set up click event handlers for the Move Task Down buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        echo '$("#moveTaskDown' . $row . '").click({key: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($key)
            . '", projectId: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            . '"}, moveTaskDown);' . "\n";
        $row++;
    }
    ?>

    function moveTaskDown(event) {
        var key = event.data.key;
        var submitValue = document.getElementsByName("submitValue");
        event.preventDefault();
        $('#moveTaskKey').val(key);
        $('#moveTask').val('"down"');
        $("#updateWorkflowForm").submit();
    }
</script>


<?php
#--------------------------------------
# Rename task dialog
#--------------------------------------
?>
<script>
$(function() {
    // Rename workflow task form
    renameForm = $("#renameForm").dialog({
        autoOpen: false,
        height: 220,
        width: 450,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Rename task": function() {renameForm.submit();}
        },
        title: "Rename task"
    });

    <?php
    # Set up click event handlers for the Rename Task  buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        echo '$("#renameTask' . $row . '").click({key: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($key)
            . '", projectId: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            . '"}, renameTask);' . "\n";
        $row++;
    }
    ?>
    
    function renameTask(event) {
        var key = event.data.key;
        var projectId = event.data.projectId;
        event.preventDefault();

        $("#taskToRename").text('"'+projectId+'"');
        $('#renameProjectId').val(projectId);
        $('#renameTaskKey').val(key);
        $("#renameForm").dialog("open");
    }
});
</script>
<div id="renameDialog"
    title="Task Rename"
    style="display: none;"
    >
    <form id="renameForm" action="<?php echo $selfUrl;?>" method="post">
    To rename the task for Project ID <span id="taskToRename" style="font-weight: bold;"></span>,
    enter the new name below, and click on the
    <span style="font-weight: bold;">Rename task</span> button.
    <p>
    <span style="font-weight: bold;">New task name:</span>
    <input type="text" name="renameNewTaskName" id="renameNewTaskName" size="50">
    </p>
    <input type="hidden" name="renameTaskKey" id="renameTaskKey" value="">
    <input type="hidden" name="renameProjectId" id="renameProjectId" value="">
    <input type="hidden" name="submitValue" value="rename">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>


<?php
#--------------------------------------
# Specify ETL config dialog
#--------------------------------------
?>
<script>
$(function() {
    // Specify ETL config form
    etlConfigForm = $("#etlConfigForm").dialog({
        autoOpen: false,
        height: 220,
        width: 450,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Specify ETL": function() {etlConfigForm.submit();}
        },
        title: "Specify ETL"
    });

    <?php
    # Set up click event handlers for the Specify ETL Config  buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        $etlConfigs = $module->getAccessibleConfigurationNames($projectId);
        array_unshift($etlConfigs, '');
        echo '$("#specifyEtlConfig' . $row . '").click({key: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($key)
            . '", projectId: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            . '", etlConfigs: ['
            . "'"
            . implode("','", $etlConfigs)
            . "'"
            . ']}, specifyEtlConfig);' . "\n";
        $row++;
    }
    ?>
    
    function specifyEtlConfig(event) {
        var key = event.data.key;
        var projectId = event.data.projectId;
        var etlConfigs = event.data.etlConfigs;

        event.preventDefault();

        if (etlConfigs.length === 1) {
            etlConfigs[0] = 'No ETL configurations have been set up for this project';
            $(".ui-dialog-buttonpane button:contains('Specify')").button("disable");
        } else {
            //button needs to be enabled in case it was disabled via some other ETL task
            $(".ui-dialog-buttonpane button:contains('Specify')").button("enable");
        }
       
        $("#projectEtlConfigSelect").empty();
        for (const val of etlConfigs) {
            $("#projectEtlConfigSelect").append($(document.createElement('option')).prop({
                value: val,
                text: val
            }))
        }

        $("#taskEtlPid").text('"'+projectId+'"');
        $('#etlProjectId').val(projectId);
        $('#etlTaskKey').val(key);
        $("#etlConfigForm").dialog("open");
    }
});
</script>
<div id="specifyEtlConfigDialog"
    title="Specify ETL configuration"
    style="display: none;"
    >

    <form id="etlConfigForm" action="<?php echo $selfUrl;?>" method="post">
    To specify an ETL configuration for this task, select one of the ETL configurations for Project ID 
    <span id="taskEtlPid" style="font-weight: bold;"></span>, and click on the
    <span style="font-weight: bold;">Specify ETL</span> button. 
    <p>
    <span style="font-weight: bold;">ETL Configurations:</span>
    <select name="projectEtlConfigSelect" id="projectEtlConfigSelect">
    </select>

    </p>
    <input type="hidden" name="etlTaskKey" id="etlTaskKey" value="">
    <input type="hidden" name="etlProjectId" id="etlProjectId" value="">
    <input type="hidden" name="submitValue" value="etlConfig">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>

<?php
#--------------------------------------
# Delete workflow task dialog
#--------------------------------------
?>
<script>
$(function() {
    // Delete workflow task form
    deleteForm = $("#deleteForm").dialog({
        autoOpen: false,
        height: 170,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Delete task": function() {deleteForm.submit();}
        },
        title: "Delete Task"
    });
  
    <?php
    # Set up click event handlers for the Delete Task buttons
    $row = 1;
    foreach ($tasks as $key => $task) {
        $projectId = $task['projectId'];
        echo '$("#deleteTask' . $row . '").click({key: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($key)
            . '", projectId: "'
            . Filter::escapeForJavaScriptInDoubleQuotes($projectId)
            . '"}, deleteTask);' . "\n";
        $row++;
    }
    ?>
    
    function deleteTask(event) {
        var key = event.data.key;
        var projectId = event.data.projectId;
        event.preventDefault();
        $("#projectToDelete").text('"'+projectId+'"');
        $("#deleteProjectId").val(projectId);
        $('#deleteTaskKey').val(key);
        $("#deleteForm").dialog("open");
    }
});
</script>
<div id="deleteDialog"
    title="Task Delete"
    style="display: none;"
    >
    <form id="deleteForm" action="<?php echo $selfUrl;?>" method="post">
    To delete this task for Project Id <span id="projectToDelete" style="font-weight: bold;"></span>,
    click on the <span style="font-weight: bold;">Delete project</span> button.
    <input type="hidden" name="deleteTaskKey" id="deleteTaskKey" value="">
    <input type="hidden" name="deleteProjectId" id="deleteProjectId" value="">
    <input type="hidden" name="submitValue" value="delete">
    <?php Csrf::generateFormToken(); ?>
    </form>
</div>

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>

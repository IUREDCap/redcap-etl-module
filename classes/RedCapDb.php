<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

/**
 * Class for methods that access the REDCap database directly.
 */
class RedCapDb
{
    /**
     * Returns information on all REDCap users that have not been suspended who have the
     * specified term in their username, first name, last name or (first) e-mail.
     */
    public function getUserSearchInfo($term)
    {
        $users = array();
        $sql = "select ui_id as id, "
            ." concat(username, ' (', user_firstname, ' ', user_lastname, ') - ', user_email) as value, username "
            ." from redcap_user_information "
            ." where user_suspended_time is null and "
            ."     (username like '%".Filter::escapeForMysql($term)."%' "
            ."     or user_firstname like '%".Filter::escapeForMysql($term)."%'"
            ."     or user_lastname like '%".Filter::escapeForMysql($term)."%'"
            ."     or user_email like '%".Filter::escapeForMysql($term)."%'".")";

        $result = db_query($sql);
        while ($row = db_fetch_assoc($result)) {
            array_push($users, $row);
        }

        return $users;
    }

    /**
     * Gets the projects that that the specified user has permission
     * to access.
     *
     * @param string username the username whose projects are returned.
     *
     * @return array array of maps from field name to field value.
     */
    public function getUserProjects($username)
    {
        $projects = array();
        $sql = "select u.username, p.project_id, p.app_title, "
            ." if(u.api_token is null, 0, 1) as has_api_token, u.api_export "
            .", u.data_export_tool "
            ." from redcap_projects p, redcap_user_rights u "
            ." where u.username = '".Filter::escapeForMysql($username)."' "
            ." and p.project_id = u.project_id and p.date_deleted is null"     // @codeCoverageIgnore
            ;

        $result = db_query($sql);
        while ($row = db_fetch_assoc($result)) {
            array_push($projects, $row);
        }
        return $projects;
    }
    
    
    /**
     * Gets the API tokens for the specified project and data export right
     * for users who have API export permissions and not in a DAG (Data Access Group).
     *
     * @param int $projectId the ID for the REDCap project for which the API tokens
     *     are being retrieved.
     * @param in $exportRight the data export right used for selecting the API
     *     tokens that are returned.
     * @return array map from username to API tokens for the specified project that have
     *     the specified data export right (e.g., "Full Data Set").
     */
    public function getApiTokens($projectId, $exportRight)
    {
        $tokens = array();
        $apiToken = null;
        $isExport = false;
        $isImport = false;
        
        $sql = "select username, api_token from redcap_user_rights "
            ." where project_id = ".((int) $projectId)." "
            ." and api_export = 1 "                          // @codeCoverageIgnore
            ." and api_token is not null "                   // @codeCoverageIgnore
            ." and data_export_tool = ".((int) $exportRight) // @codeCoverageIgnore
            ." and group_id is null"                         // @codeCoverageIgnore
            ;
        
        $queryResult = db_query($sql);
        while ($row = db_fetch_assoc($queryResult)) {
            $username = $row['username'];
            $apiToken = $row['api_token'];
            $tokens[$username] = $apiToken;
        }
        return $tokens;
    }

    /**
     * Gets all the ETL configuration settings.
     *
     * @return array array of maps that contain keys 'project_id' and 'value'. The
     *     'value' key value contains the configuration data in JSON format.
     */
    public function getEtlConfigurationsSettings($module)
    {
        $dirName = $module->getModuleDirectoryName();

        $etlConfigs = array();
        $sql = "select rems.project_id, rems.value "
            ." from redcap_external_modules rem, redcap_external_module_settings rems "
            ." where rem.external_module_id = rems.external_module_id "
            ." and '".Filter::escapeForMySql($dirName)."' like concat(rem.directory_prefix, '%') "
            ." and `key` like 'configuration:%'" // @codeCoverageIgnore
            ;
        $queryResult = db_query($sql);
        while ($row = db_fetch_assoc($queryResult)) {
            $etlConfigs[] = $row;
        }
        return $etlConfigs;
    }

    /**
     * Retrieves the a project's name
     */
    public function getProjectName($projectId)
    {
        $projectName = array();
        $sql = "select p.app_title "
            ." from redcap_projects p "
            ." where project_id = ".((int) $projectId)." "
            ;

        $queryResult = db_query($sql);
        $projectName = db_fetch_assoc($queryResult);

       return $projectName['app_title'];
    }

    /**
     * Starts a database transaction.
     */
    public function startTransaction()
    {
        db_query("SET AUTOCOMMIT=0");
        db_query("BEGIN");
    }
    
    /**
     * Ends a database transaction.
     *
     * @param boolean $commit indicates if the transaction should be committed.
     */
    public function endTransaction($commit)
    {
        try {
            if ($commit) {
                db_query("COMMIT");
            } else {
                db_query("ROLLBACK");
            }
        } catch (\Exception $exception) {
            ;
        }
        db_query("SET AUTOCOMMIT=1");
    }
}

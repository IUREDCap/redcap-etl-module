<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

/**
 * Authorization class for determining who has permission
 * to use and/or request use of REDCap-ETL.
 */
class Authorization
{
    /**
     * Indicates if the current user has permission to access
     * REDCap-ETL project pages for the current project.
     *
     * @param RedCapEtlModule $module REDCap-ETL external module.
     *
     * @return boolean true if the user has permission, and
     *     false otherwise.
     */
    public static function hasEtlProjectPagePermission($module)
    {
        $hasPermission = false;

        $projectId = $module->getProjectId();

        if ($module->isSuperUser()) {
            $hasPermission = true;
        } elseif (!empty($projectId)) {     // @codeCoverageIgnore
            if (self::hasRedCapUserRightsForEtl($module)) {
                $userEtlProjects = $module->getUserEtlProjects();
                if (in_array($projectId, $userEtlProjects)) {
                    $hasPermission = true;
                }
            }
        }
        return $hasPermission;
    }


    /**
     * Indicates if the current user has permission to access the configuration
     * with the specified configuration name for the specified project.
     *
     * @param RedCapEtlModule $module the REDCap-ETL external module.
     * @param string $configName the name of the configuration for the permission check.
     * @param int $projectId the project ID for the permission check.
     *
     * @return boolean true if the current user has access to the specified configuration,
     *     and false otherwise.
     */
    public static function hasEtlConfigNamePermission($module, $configName, $projectId)
    {
        $configuration = $module->getConfiguration($configName, $projectId);
        $hasPermission = self::hasEtlConfigurationPermission($module, $configuration);
        return $hasPermission;
    }


    /**
     * Indicates if the current user has permission to access the specified configuration.
     */
    public static function hasEtlConfigurationPermission($module, $configuration)
    {
        $hasPermission = false;
        if ($module->isSuperUser()) {
            $hasPermission = true;
        } else {
            # $configExportRight = $configuration->getProperty(Configuration::DATA_EXPORT_RIGHT);
            $userExportRight   = $module->getDataExportRight();
            #if (!empty($configExportRight) && !empty($userExportRight)) {
            if (!empty($userExportRight)) {
                if ($userExportRight == 1) {
                    # User has full data set export permission
                    $hasPermission = true;
                #} elseif ($userExportRight == 3 && $configExportRight != 1) {
                #    # User cannot see tagged identifier fields, and configuration
                #    # is NOT "full data set"
                #    $hasPermission = true;
                #} elseif ($userExportRight == 2 && ($configExportRight == 2 || $configExportRight == 0)) {
                #    # User and configuration export permissions are both "de-identified"
                #    # (the most restrive access other than "no access")
                #    $hasPermission = true;
                }
            }
        }
        return $hasPermission;
    }

    /**
     * Indicates if the current user has the REDCap user rights
     * to access ETL for the current project (admins always have access).
     *
     * @return boolean true if the user has permission (or is an admin), and
     *     false otherwise.
     */
    public static function hasRedCapUserRightsForEtl($module)
    {
        $hasPermission = false;

        if ($module->isSuperUser()) {
            $hasPermission = true;
        } else {
            $rights = $module->getUserRights();

            # Users need to have project design permission and "full data set" data export permission
            # and not belong to a data access group (DAG)
            if ($rights['design'] && $rights['data_export_tool'] == 1 && empty($rights['group_id'])) {
                $hasPermission = true;
            }
        }
        return $hasPermission;
    }
}

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
            $canExportAllData = false;

            # If this is a newer REDCap version that has data export permissions
            # specified at the instrument (form) level, check that the user has export
            # permission for all instruments.
            #
            # Example value for data_export_instruments:
            #     "[enrollment,1][contact_information,1][emergency_contacts,1][weight,1][cardiovascular,1]"
            #

            if ($rights['design'] && self::canExportAllInstruments($rights) && empty($rights['group_id'])) {
                $hasPermission = true;
            }
        }
        return $hasPermission;
    }

    /**
     * Gets individual instrument export rights, if defined.
     *
     * @param array $rights a user's REDCap project rights.
     */
    public static function getInstrumentExportRights($rights)
    {
        $instrumentExportRights = null;

        if (array_key_exists('data_export_instruments', $rights)) {
            $dataExportRights = $rights['data_export_rights'];
            $dataExportRights = trim($dataExportRights, '[]');
            $dataExportRights = explode('][', $dataExportRights);

            foreach ($dataExportRights as $dataExportRight) {
                $commaIndex = strrpos($dataExportRight, ',');

                $instrument  = substr($dataExportRight, 0, $commaIndex);
                $accessLevel = substr($dataExportRight, $commaIndex + 1);

                $instrumentExportRightss[$instrument] = $accessLevel;
            }
        }
        return $instrumentExportRights;
    }

    public static function canExportAllInstruments($rights)
    {
        $canExportAllInstruments = false;

        if (array_key_exists('data_export_instruments', $rights)) {
            $canExportAllInstruments = true;
            $instrumentExportRights = self::getInstrumentExportRights($rights);
            foreach ($instrumentExportRights as $instrument => $accessLevel) {
                if ($accessLevel !== '1') {
                    $canExportAllInstruments = false;
                    break;
                }
            }
        } elseif ($rights['data_export_tool'] == 1) {
            $canExportAllInstruments = true;
        }

        return $canExportAllInstruments;
    }
}

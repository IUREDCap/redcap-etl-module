<?php

namespace IU\RedCapEtlModule;

/**
 * Authorization class for determining who has permission
 * to use and/or request use of REDCap-ETL.
 */
class Authorization
{
    /**
     * Indicates if the user has permission to access
     * REDCap-ETL project pages for the current project.
     *
     * @return boolean true if the user has permission, and
     *     false otherwise.
     */
    public static function hasEtlProjectPagePermission($module, $username)
    {
        $hasPermission = false;

        $projectId = $module->getProjectId();
        
        if ($module->isSuperUser()) {
            $hasPermission = true;
        } elseif (!empty($projectId) && !empty($username)) {
            if (self::hasRedCapUserRightsForEtl($module, $username)) {
                $userEtlProjects = $module->getUserEtlProjects($username);
                if (in_array($projectId, $userEtlProjects)) {
                    $hasPermission = true;
                }
            }
        }
        return $hasPermission;
    }
    
    /**
     * Indicates if the user has the right to request permission to
     * use ETL on the current project.
     *
     * @return boolean true if the user has permission, and
     *     false otherwise.
     */
    public static function hasEtlRequestPermission($module, $username)
    {
        $hasPermission = false;

        $projectId = $module->getProjectId();
        
        if ($module->isSuperUser()) {
            $hasPermission = true;
        } elseif (!empty($projectId) && !empty($username)) {
            if (self::hasRedCapUserRightsForEtl($module, $username)) {
                $hasPermission = true;
            }
        }
        return $hasPermission;
    }
    
    /**
     * Indicates if the specified non-admin user has the REDCap user rights
     * to access ETL for the current project (admins always have access).
     *
     * @return boolean true if the user has permission, and
     *     false otherwise.
     */
    public static function hasRedCapUserRightsForEtl($module, $username)
    {
        $hasPermission = false;

        if (!empty($username)) {
            $rights = $module->getUserRights($username);
            if ($rights[$username]['design']) {
                $hasPermission = true;
            }
        }
        return $hasPermission;
    }
}

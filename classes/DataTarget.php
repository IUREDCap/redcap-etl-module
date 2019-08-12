<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

/**
 * Data target class.
 */
class DataTarget
{
    #----------------------------------------------------------------
    # Data targets of ETL process
    #----------------------------------------------------------------
    const DB          = 'db';
    const CSV_ZIP     = 'csv_zip';
    const SQLITE_FILE = 'sqlite_file';
    
    /**
     * Indicates if the specified property is a valid configuration
     * property.
     *
     * @param string $property the property to check for validity.
     *
     * @return boolean true if the specified property is valid, and
     *     false otherwise.
     */
    public static function isValid($property)
    {
        $isValid = false;

        if ($property != null) {
            $property = trim($property);
            
            $properties = self::getProperties();

            foreach ($properties as $name => $value) {
                if ($property === $value) {
                    $isValid = true;
                    break;
                }
            }
        }
        return $isValid;
    }
    
    /**
     * Gets the property names and values.
     *
     * @return array a map from property name to property value for
     *     all the configuration properties.
     */
    public static function getProperties()
    {
        $reflection = new \ReflectionClass(self::class);
        $properties = $reflection->getConstants();
        return $properties;
    }
}

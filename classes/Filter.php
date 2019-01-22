<?php

namespace IU\RedCapEtlModule;

/**
 * Class for filtering/escaping user input.
 */
class Filter
{
    /**
     * Escape text for displaying as HTML.
     * This method only works within REDCap context.
     *
     * @param string $value the text to display.
     */
    public static function escapeForHtml($value)
    {
        return \REDCap::escapeHtml($value);
    }
    
    public static function escapeForHtmlAttribute($value)
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }

    /**
     * Escape value for use as URL parameters.
     */
    public static function escapeForUrlParameter($value)
    {
        return urlencode($value);
    }
    
    public static function escapeForJavaScriptInSingleQuotes($value)
    {
        # REDCap's JavaScript escape function for single quotes
        return js_escape($value);
    }
    
    public static function escapeForJavaScriptInDoubleQuotes($value)
    {
        # REDCap's JavaScript escape function for double quotes
        return js_escape2($value);
    }
    
    public static function escapeForMysql($value)
    {
        return db_escape($value);
    }
    
    public static function stripTags($value)
    {
        return strip_tags($value);
    }
    
    public function isEmail($value)
    {
        # Use REDCap's function
        return isEmail($value);
    }
    
    public function isUrl($value)
    {
        # Use REDCap's function
        return isUrl($value);
    }
}

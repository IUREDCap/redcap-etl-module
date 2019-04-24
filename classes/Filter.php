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
    
    public function sanitizeInt($value)
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Removes tags and invalid characters for labels
     * (internal string values used for submit buttons, etc.).
     */
    public function sanitizeLabel($value)
    {
        $flags = FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK;
        return filter_var($value, FILTER_SANITIZE_STRING, $flags);
    }
    
    /**
     * Removes tags and invalid characters for strings.
     */
    public function sanitizeString($value)
    {
        $flags = FILTER_FLAG_STRIP_LOW;
        return filter_var($value, FILTER_SANITIZE_STRING, $flags);
    }
}

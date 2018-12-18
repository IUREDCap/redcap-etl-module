<?php

namespace IU\RedCapEtlModule;

/**
 * Class for filtering/escaping user input.
 */
class Filter
{
    /**
     * Escape a text for displaying as HTML.
     * This method only works within REDCap context.
     *
     * @param string $value the text to display.
     */
    public static function escapeForHtml($value)
    {
        return \REDCap::escapeHtml($value);
    }

    public static function escapeForJavaScriptInSingleQuotes($value)
    {
        return js_escape($value);
    }
    
    public static function escapeForJavaScriptInDoubleQuotes($value)
    {
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
}

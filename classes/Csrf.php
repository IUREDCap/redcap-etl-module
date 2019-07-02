<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

/**
 * Cross-Site Request Forgery (CSRF) class for protecting against
 * CSRF attacks
 */
class Csrf
{
    const TOKEN_NAME = 'etl_csrf_token';
    
    public static function getToken()
    {
        $tokenId = null;

        if (empty($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(openssl_random_pseudo_bytes(32));
        }
        $tokenId = $_SESSION[self::TOKEN_NAME];
        return $tokenId;
    }
    
    /**
     * Generates a hidden input that contains the CSRF token.
     */
    public static function generateFormToken()
    {
        echo '<input type="hidden" name="'.self::TOKEN_NAME.'" value="'.self::getToken().'"'.">\n";
    }

    /**
     * Indicates if a POST has a valid CSRF token.
     */
    public static function isValid()
    {
        $isValid = false;
        if (array_key_exists(self::TOKEN_NAME, $_POST) && array_key_exists(self::TOKEN_NAME, $_SESSION)) {
            if (!empty($_SESSION[self::TOKEN_NAME]) && $_POST[self::TOKEN_NAME] === $_SESSION[self::TOKEN_NAME]) {
                $isValid = true;
            }
        }
        return $isValid;
    }
    
    /**
     * Indicates if a request is valid from a CSRF perspective. If the request is a POST
     * then it has to have an ETL CSRF token in the request that corresponds to an
     * ETL CSRF token in the user's session.
     */
    public static function isValidRequest()
    {
        $isValid = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isValid = self::isValid();
        }
        return $isValid;
    }
}

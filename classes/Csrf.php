<?php

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

    public static function isValid()
    {
        $isValid = false;
        if (array_key_exists(self::TOKEN_NAME, $_POST) && array_key_exists(self::TOKEN_NAME, $_SESSION)) {
            if (!empty($_SESSION[self::TOKEN_NAME]) && $POST[self::TOKEN_NAME] === $_SESSION[self::TOKEN_NAME]) {
                $isValid = true;
            }
        }
        return $isValid;
    }
}

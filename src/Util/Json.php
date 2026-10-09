<?php

namespace tingyu\HttpRequest\Util;

class Json
{
    public static function IsJsonStr($str) {
        if (!is_string($str)) {
            return false;
        }

        $decode = json_decode($str);
        if(json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return is_array($decode) || is_object($decode);
    }
}
<?php

namespace app\utils;

class Validator
{
    public static $integerPattern = '/^\s*[+-]?\d+\s*$/';
    
    public static $numberPattern = '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';
    
    public static function isEmpty($v)
    {
        return $v === null || $v === [] || $v === '';
    }

    public static function isString($v)
    {
        return is_scalar($v) && !is_bool($v);
    }
    
    public static function isMobile($v)
    {
        return is_scalar($v) && preg_match('/^1\d{10}$/', $v);
    }
    
    public static function isCode($v)
    {
        return is_scalar($v) && preg_match('/^\d{6}$/', $v);
    }

    public static function isToken($v)
    {
        if (empty($v) || !is_scalar($v) ||  strlen($v) != 32)
        {
            return false;
        }
        return true;
    }
}
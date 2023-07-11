<?php

namespace RedooCalendar\Helper;

trait Translator
{
    static $MODULE_NAME = 'RedooCalendar';

    /**
     * @param string $string
     * @return string
     */
    public static function t(string $string): string
    {
        return vtranslate($string, self::$MODULE_NAME);
    }
}
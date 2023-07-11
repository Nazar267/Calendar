<?php


namespace RedooCalendar\Base\Source;

use RedooCalendar\Config;
use RedooCalendar\Helper\Translator;

abstract class BaseSource implements SourceInterface
{
    use Translator;

    const data = [];

    /**
     * @param bool $translate
     * @return array
     */
    public static function getData(bool $translate = true): array
    {
        $ignore = [];
        $googleClientToken = Config::get('google_clientid', null);
        if (empty($googleClientToken)) {
            $ignore[] = 'google';
        }

        $return = static::data;
        foreach ($return as $key => &$value) {
            if (in_array($key, $ignore)) {
                unset($return[$key]);
            };
        }

        if ($translate) {
            foreach ($return as $key => &$value) {
                $return[$key] = self::t($value);
            }
        }

        return static::data;
    }

    /**
     * @param bool $translate
     * @return array
     */
    public static function getOptionsData(bool $translate = true): array
    {
        $ignore = [];
        $googleClientToken = Config::get('google_clientid', null);
        if (empty($googleClientToken)) {
            $ignore[] = 'google';
        }

        $return = static::data;

        foreach ($return as $key => &$value) {
            if (in_array($key, $ignore)) {
                unset($return[$key]);
            }
        }

        if ($translate) {
            foreach ($return as $key => &$value) {
                $return[$key] = self::t($value);
            }
        }

        return $return;
    }

    public static function getKeys(): array
    {
        return array_keys(static::data);
    }

    public function __get($name)
    {
        return static::$name();
    }
}

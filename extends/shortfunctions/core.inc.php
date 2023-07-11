<?php
class CoreShortfunctions {

    public static function sf_now($context, $interval = 0, $format = null) {
        $date = \DateTimeField::convertToUserTimeZone(date('Y-m-d H:i:s'));
        $time = strtotime($date->format('Y-m-d H:i:s'));

        if($format === null) {
            $format = "Y-m-d";
        }

        if(is_numeric($interval)) {
            $time += (intval($interval) * 86400);
        } else {
            $time = strtotime($interval, $time);
        }

        return date($format, $time);
    }

    public static function sf_currency($context, $value) {
        return \CurrencyField::convertToUserFormat($value);
    }

}
\Workflow\Shortfunctions::register('now', array('CoreShortfunctions', 'sf_now'), true);
\Workflow\Shortfunctions::register('currency', array('CoreShortfunctions', 'sf_currency'), true);

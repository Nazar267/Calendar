<?php

namespace RedooCalendar\Updater;


class Link
{
    public static function addDetailsButton($moduleName, $label, $action, $linkType = 'DETAILVIEWBASIC')
    {

        $moduleInstance = \Vtiger_Module::getInstance($moduleName);

        $moduleInstance->addLink(
            $linkType,
            $label,
            $action,
            '','',
            array());
    }

    public static function addLink($moduleName, $label, $action)
    {
        $linkType = 'DETAILVIEWBASIC';
        self::addDetailsButton($moduleName, $label, $action, $linkType);
    }

    public static function remove($moduleName, $type, $label, $url = false) {

        \Vtiger_Link::deleteLink(getTabid($moduleName), $type, $label, $url);

    }
}

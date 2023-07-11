<?php

namespace RedooCalendar\Updater;
use \Vtiger_Module;

class Module
{
    public static function delete($moduleName) {

        $moduleInstance = Vtiger_Module::getInstance($moduleName);

        if ($moduleInstance) {
            $moduleInstance->delete();
        }
    }
}
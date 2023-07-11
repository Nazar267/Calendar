<?php
namespace RedooCalendar\Updater\Database;

use RedooCalendar\Updater\MODDBCheck;

class Table
{
    public static function exist($tableName) {
        global $adb;
        $tables = $adb->get_tables();

        foreach($tables as $table) {
            if($table == $tableName)
                return true;
        }

        return false;
    }

    public static function create($tablename, $sql, $callbackIfNew = false) {

        if(MODDBCheck::existTable($tablename) === false) {
            MODDBCheck::query($sql);

            if($callbackIfNew !== false && is_callable($callbackIfNew)) {
                $callbackIfNew();
            }
        }

    }

    public static function isEmpty($tablename, $callbackIsEmpty) {
        if(MODDBCheck::existTable($tablename) === false) {
            return false;
        }

        $sql = 'SELECT COUNT(*) as num FROM '.$tablename;
        $result = MODDBCheck::query($sql);

        if(MODDBCheck::query_result($result, 0, 'num') == 0) {
            if($callbackIsEmpty !== false && is_callable($callbackIsEmpty)) {
                $callbackIsEmpty();
            }
        }

    }

}

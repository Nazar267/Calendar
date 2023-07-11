<?php
namespace RedooCalendar\Updater;

class MODDBCheck {
    public static function existTable($tableName) {
        global $adb;
        $tables = $adb->get_tables();

        foreach($tables as $table) {
            if($table == $tableName)
                return true;
        }

        return false;
    }
    public static function checkColumn(
        $table,
        $colum,
        $type,
        $default = false,
        $callbackIfNew = false,
        $resetType = false,
        $nullable = false
    ) {
        global $adb;

        if(!self::existTable($table)) {
            return false;
        }

        $result = $adb->query("SHOW COLUMNS FROM `".$table."` LIKE '".$colum."'");
        $exists = ($adb->num_rows($result))?true:false;

        if($exists == false) {
            //echo "Add column '".$table."'.'".$colum."'<br>";
            $adb->query("ALTER TABLE `".$table."` ADD `".$colum."` ".$type.' '.($nullable === false? "NOT ":'')." NULL".($default !== false?" DEFAULT  '".$default."'":""), false);

            if($callbackIfNew !== false && is_callable($callbackIfNew)) {
                $callbackIfNew($adb);
            }
        } elseif($resetType == true) {
            $existingType = strtolower(html_entity_decode($adb->query_result($result, 0, 'type'), ENT_QUOTES));
            $existingType = str_replace(' ', '', $existingType);
            if($existingType != strtolower(str_replace(' ', '', $type))) {
                $sql = "ALTER TABLE  `".$table."` CHANGE  `".$colum."`  `".$colum."` ".$type.";";
                $adb->query($sql);
            }
        }

        return $exists;
    }
    public static function pquery($query, $parameters) {
        global $adb;

        return $adb->pquery($query, $parameters, true);
    }
    public static function query($query) {
        global $adb;

        return $adb->query($query, true);
    }
    public static function numRows($result) {
        global $adb;

        return $adb->num_rows($result);
    }
    public static function getUniqueID($tableName) {
        $adb = \PearDatabase::getInstance();

        return $adb->getUniqueID($tableName);
    }
    public static function query_result($result, $index, $field) {
        $adb = \PearDatabase::getInstance();

        return $adb->query_result($result, $index, $field);
    }
    public static function fetchByAssoc($result) {
        $adb = \PearDatabase::getInstance();

        return $adb->fetchByAssoc($result);
    }
}

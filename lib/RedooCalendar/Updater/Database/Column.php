<?php
namespace RedooCalendar\Updater\Database;

use RedooCalendar\Updater\Database\Table;
use RedooCalendar\Updater\MODDBCheck;

class Column {

    /**
     * Check if colum exist in table and remove them, if existing
     *
     * @param string $tablename Within which table the column will be removed
     * @param string $column the name of the column
     */
    public static function drop($tablename, $column) {
        $db = \PearDatabase::getInstance();

        // Remove unused column from user table
        $columns = $db->getColumnNames($tablename);

        if (in_array($column, $columns)) {
            $db->pquery('ALTER TABLE '.$tablename.' DROP COLUMN '.$column, array());
        }
    }

    /**
     * Check if a column in a table exists and create the column if not existing
     *
     * @param string $table Within which table the column will be created
     * @param string $colum The name of the column, compatible to mysql naming schema
     * @param string $type The sql type of the column, compatible to mysql naming schema
     * @param boolean $default [optional] The default value of this column (default = false)
     * @param callable $callbackIfNew [optional] Function, which will called, then column is added (default = false)
     * @param boolean $resetType [optional] Change the type to this, when column already exist with another type (default = false)
     * @param boolean $nullable [optional] Is the column nullable  (default = false)
     * @return bool
     */
    public static function check(
        $table,
        $colum,
        $type,
        $default = false,
        $callbackIfNew = false,
        $resetType = false,
        $nullable = false
    ) {
        global $adb;

        if(!MODDBCheck::existTable($table)) {
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

}

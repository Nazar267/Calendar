<?php

namespace RedooCalendar;

/*
CREATE TABLE `vtiger_modulename_config` (
  `key` varchar(128) NOT NULL,
  `value` text NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB
 */

class Config
{
    private static $_TableName = '';

    /**
     * @return string
     */
    public static function getTableName()
    {
        if (empty(self::$_TableName)) {
            $namespace = strtolower(__NAMESPACE__);
            self::$_TableName = 'vtiger_' . $namespace . '_config';
        }

        return self::$_TableName;
    }

    public static function get($key, $default = -1)
    {
        $tableName = self::getTableName();

        $adb = \PearDatabase::getInstance();

        $sql = 'SELECT value FROM ' . $tableName . ' WHERE `key` = ?';
        $result = $adb->pquery($sql, array($key), true);

        if ($adb->num_rows($result) == 0) {
            return $default;
        }

        $value = html_entity_decode($adb->query_result($result, 0, 'value'));
        return unserialize($value);
    }

    public static function set($key, $value)
    {
        $tableName = self::getTableName();

        $adb = \PearDatabase::getInstance();

        $value = serialize($value);

        $sql = 'REPLACE INTO ' . $tableName . ' SET `key` = ?, `value` = ?';
        $result = $adb->pquery($sql, array($key, $value), true);
    }
}

<?php
/**
 * Available variables:
 * $moduleName
 */

return function () {
    global $adb, $dbconfig;
    $findFieldSql = "SELECT 
        column_name 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA=? 
        AND TABLE_NAME=?";

    $result = $adb->pquery($findFieldSql, [$dbconfig['db_name'],'vtiger_redoocalendar_generated_calendar']);
    while ($row = $adb->fetchByAssoc($result)) {
      if ($row['column_name'] == 'access_mode') {
        return;
      }
    }
    $alterTableSql = "ALTER TABLE vtiger_redoocalendar_generated_calendar ADD access_mode enum('private','public', 'share') not null";
    $adb->query($alterTableSql);
};

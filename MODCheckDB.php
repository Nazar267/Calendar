<?php

namespace RedooCalendar {

    use phpDocumentor\Parser\Exception;
    use Settings_MenuEditor_Module_Model;

    class MODDBCheck
    {
        public static function existTable($tableName)
        {
            global $adb;
            $tables = $adb->get_tables();

            foreach ($tables as $table) {
                if ($table == $tableName)
                    return true;
            }

            return false;
        }

        public static function checkColumn($table, $colum, $type, $default = false, $callbackIfNew = false, $resetType = false)
        {
            global $adb;

            if (!self::existTable($table)) {
                return false;
            }

            $result = $adb->query("SHOW COLUMNS FROM `" . $table . "` LIKE '" . $colum . "'");
            $exists = ($adb->num_rows($result)) ? true : false;

            if ($exists == false) {
                if ($type !== false) {
                    //echo "Add column '".$table."'.'".$colum."'<br>";
                    $adb->query("ALTER TABLE `" . $table . "` ADD `" . $colum . "` " . $type . " NOT NULL" . ($default !== false ? " DEFAULT  '" . $default . "'" : ""), false);

                    if ($callbackIfNew !== false && is_callable($callbackIfNew)) {
                        $callbackIfNew($adb);
                    }
                }
            } elseif ($resetType == true) {
                $existingType = strtolower(html_entity_decode($adb->query_result($result, 0, 'type'), ENT_QUOTES));
                $existingType = str_replace(' ', '', $existingType);
                if ($existingType != strtolower(str_replace(' ', '', $type))) {
                    $sql = "ALTER TABLE  `" . $table . "` CHANGE  `" . $colum . "`  `" . $colum . "` " . $type . ";";
                    $adb->query($sql);
                }
            }

            return $exists;
        }

        public static function query($query)
        {
            global $adb;

            return $adb->query($query, true);
        }

        public static function pquery($query, $params)
        {
            global $adb;

            return $adb->pquery($query, $params, true);
        }

        public static function fetchByAssoc($result)
        {
            global $adb;

            return $adb->fetchByAssoc($result);
        }

        public static function checkIndex($table, $indexName, $sql)
        {
            global $adb, $dbconfig;

            if (!MODDBCheck::existTable($table)) {
                return false;
            }

            $result = $adb->query('SELECT INDEX_NAME
                FROM information_schema.statistics
                WHERE TABLE_SCHEMA = "' . $dbconfig['db_name'] . '"
                    AND TABLE_NAME = "' . $table . '" AND INDEX_NAME = "' . $indexName . '"');
            $exists = ($adb->num_rows($result)) ? true : false;

            if ($exists == false) {
                echo "Add Index to table '" . $table . "'.'" . $indexName . "'<br>";
                $adb->query($sql, false);
                return $exists;
            }

            return $exists;
        }

        public static function dropIndex($table, $indexName)
        {
            global $adb, $dbconfig;

            if (!MODDBCheck::existTable($table)) {
                return false;
            }

            $result = $adb->query('SHOW INDEX FROM ' . $table . ' WHERE KEY_NAME = \'' . $indexName . '\'');
            $exists = ($adb->num_rows($result)) ? true : false;

            if ($exists == true) {
                echo "Remove Index to table '" . $table . "'.'" . $indexName . "'<br>";
                $adb->query('ALTER TABLE `' . $table . '` DROP INDEX `' . $indexName . '`;', false);
                return $exists;
            }

            return $exists;
        }

        public static function dropColumn($tableName, $column)
        {
            global $adb;

            if (self::checkColumn($tableName, $column, false)) {
                $adb->query('ALTER TABLE `' . $tableName . '` DROP `' . $column . '`');
            }
        }
    }

    Settings_MenuEditor_Module_Model::addModuleToApp('RedooCalendar', 'INVENTORY');


    if (!MODDBCheck::existTable('vtiger_redoocalendar_connections')) {
        MODDBCheck::query('
            create table vtiger_redoocalendar_connections
            (
                id         int auto_increment
                    primary key,
                user_id    int                                 not null,
                title      varchar(255)                        not null,
                code       varchar(255)                        not null,
                accessmode enum (\'private\', \'share\', \'public\') not null,
                connector  varchar(128)                        not null,
                settings   text                                not null,
                `default`  tinyint(1)                          null
            );
        ');
    }

    if (!MODDBCheck::existTable('vtiger_redoocalendar_connection_permission')) {
        MODDBCheck::query('
            create table vtiger_redoocalendar_connection_permission
            (
                id           int auto_increment
                    primary key,
                type         varchar(6) not null,
                typeid       varchar(5) not null,
                connectionid int        not null,
                dirty        tinyint    not null
            );
        ');
    }

    MODDBCheck::checkIndex('vtiger_redoocalendar_connection_permission', 'type', '
        create index type
            on vtiger_redoocalendar_connection_permission (type, typeid);
    ');


    MODDBCheck::checkIndex('vtiger_redoocalendar_connection_permission', 'viewid', '
        create index viewid
            on vtiger_redoocalendar_connection_permission (connectionid);
    ');

    MODDBCheck::checkIndex('vtiger_redoocalendar_connections', 'user_id', '
        create index user_id
            on vtiger_redoocalendar_connections (user_id);
    ');

    if (!MODDBCheck::existTable('vtiger_redoocalendar_calendar')) {
        MODDBCheck::query('
            create table vtiger_redoocalendar_calendar
            (
                id         int unsigned auto_increment
                    primary key,
                connection_id int not null,
                access_mode enum(\'private\',\'public\', \'share\') not null,
                hide_event_content bool default false not null,
                title      varchar(255)                        not null,
                color      varchar(255)                        not null,
                visible    tinyint(1)                          null,
                updated_at timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
                created_at timestamp default CURRENT_TIMESTAMP not null
            );
        ');
    }

    if (!MODDBCheck::existTable('vtiger_redoocalendar_config')) {
        MODDBCheck::query('
            CREATE TABLE `vtiger_redoocalendar_config` (
  `key` varchar(128) NOT NULL,
  `value` text NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB');
    }

    if (!MODDBCheck::existTable('vtiger_redoocalendar_event')) {
        MODDBCheck::query('
            create table vtiger_redoocalendar_event
            (
                id          int unsigned auto_increment
                    primary key,
                calendar_id int unsigned                        not null,
                updated_at  timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
                created_at  timestamp default CURRENT_TIMESTAMP not null,
                constraint vtiger_redoocalendar_event_vtiger_redoocalendar_calendar_id_fk
                    foreign key (calendar_id) references vtiger_redoocalendar_calendar (id)
                        on delete cascade
            );
        ');
    }

    if (!MODDBCheck::existTable('vtiger_redoocalendar_event_attribute')) {
        MODDBCheck::query('
            create table vtiger_redoocalendar_event_attribute
            (
                id    int unsigned auto_increment
                    primary key,
                label varchar(255) not null,
                code  varchar(255) not null
            );
        ');
    }

    if (!MODDBCheck::existTable('vtiger_redoocalendar_event_value')) {
        MODDBCheck::query('
            create table vtiger_redoocalendar_event_value
            (
                id           int unsigned auto_increment
                    primary key,
                attribute_id int unsigned not null,
                event_id     int unsigned not null,
                value        blob         null,
                constraint redoo_event_value_redoo_event_attribute_id_fk
                    foreign key (attribute_id) references vtiger_redoocalendar_event_attribute (id)
                        on update cascade on delete cascade,
                constraint redoo_event_value_vtiger_redoocalendar_event_id_fk
                    foreign key (event_id) references vtiger_redoocalendar_event (id)
                        on update cascade on delete cascade
            );
        ');
    }

    MODDBCheck::checkIndex('vtiger_redoocalendar_event_value', 'redoocalendar_event_value_redoocalendar_event_id_fk', '
        create index redoocalendar_event_value_redoocalendar_event_id_fk
            on vtiger_redoocalendar_event_value (event_id);
    ');

    if (!MODDBCheck::existTable('vtiger_redoocalendar_subscribe')) {
        MODDBCheck::query('
            create table vtiger_redoocalendar_subscribe
            (
                id            int unsigned auto_increment
                    primary key,
                connection_id int                                 not null,
                calendar_id   text                                not null,
                owner         tinyint(1)                          not null,
                visible       bool default false                  not null,
                updated_at    timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
                created_at    timestamp default CURRENT_TIMESTAMP not null,
                constraint vt_redoocalendar_subscribe_vt_users_id_fk
                    foreign key (connection_id) references vtiger_redoocalendar_connections (id)
                        on delete cascade
            );
        ');
    }

    if (!MODDBCheck::existTable('vtiger_redoocalendar_calendar_share')) {
        MODDBCheck::query('
            create table vtiger_redoocalendar_calendar_share
            (
                id          int auto_increment
                    primary key,
                user_id     int          null,
                calendar_id int unsigned null,
                constraint vt_redoocalendar_calendar_share_vt_redoocalendar_calendar__fk
                    foreign key (calendar_id) references vtiger_redoocalendar_calendar (id)
                        on update cascade on delete cascade,
                constraint vtiger_redoocalendar_calendar_share_vtiger_users_id_fk
                    foreign key (user_id) references vtiger_users (id)
                        on update cascade on delete cascade
            );
        ');
    }

    if (!MODDBCheck::existTable('vtiger_redoocalendar_generated_calendar')) {
        MODDBCheck::query('
            create table if not exists vtiger_redoocalendar_generated_calendar
            (
                id int auto_increment
                    primary key,
                `sql` text not null,
                user_id int not null,
                access_mode enum(\'private\',\'public\', \'share\') not null,
                title varchar(255) null,
                config text null,
                field_config text null
            );

        ');
    }

    MODDBCheck::checkIndex('vtiger_redoocalendar_event_value', 'redoocalendar_event_value_redoocalendar_event_id_fk', '
        create index redoocalendar_event_value_redoocalendar_event_id_fk
            on vtiger_redoocalendar_event_value (event_id);
    ');

    if (MODDBCheck::checkColumn('vtiger_redoocalendar_connections', 'settings', false)) {
        MODDBCheck::query('alter table vtiger_redoocalendar_connections modify settings text null;');
    }

    if (MODDBCheck::checkColumn('vtiger_redoocalendar_subscribe', 'owner', false)) {
        MODDBCheck::query('alter table vtiger_redoocalendar_subscribe modify owner tinyint(1) null;');
    }

    if (MODDBCheck::checkColumn('vtiger_redoocalendar_calendar', 'hide_event_content', false)) {
        MODDBCheck::query('alter table vtiger_redoocalendar_calendar change hide_event_content hide_event_details tinyint(1) default 0 not null;');
    }

    MODDBCheck::checkColumn('vtiger_redoocalendar_event', 'date_start', 'datetime');
    MODDBCheck::checkColumn('vtiger_redoocalendar_event', 'date_end', 'datetime');
    MODDBCheck::checkColumn('vtiger_redoocalendar_event', 'title', 'varchar(255)');
    MODDBCheck::checkColumn('vtiger_redoocalendar_event', 'description', 'text');

    MODDBCheck::checkColumn('vtiger_redoocalendar_subscribe', 'color', 'varchar(255)');

    $moduleName = basename(dirname(__FILE__));

    try {
        require_once('lib' . DIRECTORY_SEPARATOR . $moduleName.DIRECTORY_SEPARATOR.'VtUtils.php');
        require_once('lib' . DIRECTORY_SEPARATOR . $moduleName.DIRECTORY_SEPARATOR.'Updater.php');
        $updater = new \RedooCalendar\Updater($moduleName);
        $updater->update();
    } catch (\Exception $exp) {
        echo 'Error during Setup of ' . $moduleName.': ' . $exp->getMessage();
    }

    $obj = \CRMEntity::getInstance($moduleName);
    $obj->initialize_module();    
}

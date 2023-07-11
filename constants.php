<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 24.07.14 14:59
 * You must not use this file without permission.
 */

//define('SCHEDULER_PATH', 'modules/RedooCalendar/views/resources/dhtmlxScheduler_v4.4.0/');

define('CALENDAR_PATH', 'modules/RedooCalendar/views/resources/dhtmlxCalendar_v51/');

define('FULLCALENDAR_PATH', 'modules/RedooCalendar/views/resources/fullcalendar-3.5.1/');

define('CONTEXTMENU_PATH', 'modules/RedooCalendar/views/resources/jQuery-contextMenu-2.6.2/');

define('REDOOCAL_TMP', 'test');


define('REDOOCALENDAR_PERMISSION_NONE', 0);
define('REDOOCALENDAR_PERMISSION_BLOCKED', 1);
define('REDOOCALENDAR_PERMISSION_READ', 2);
define('REDOOCALENDAR_PERMISSION_EDIT', 3);
define('REDOOCALENDAR_PERMISSION_DELETE', 4);

define('REDOOCALENDAR_ROOTPATH', vglobal('root_directory') . DS . 'modules' . DS . 'RedooCalendar' . DS);

if(!defined('OAUTH_CALLBACK_ADD')) {
    define('OAUTH_CALLBACK_ADD', 'https://oauth.redoo-networks.com/a.php');
}

if(!defined('OAUTH_CALLBACK_REQUEST')) {
    define('OAUTH_CALLBACK_REQUEST', 'https://oauth.redoo-networks.com/request.php');
}

if(!defined('OAUTH_CALLBACK_REFRESH')) {
    define('OAUTH_CALLBACK_REFRESH', 'https://oauth.redoo-networks.com/refresh.php');
}

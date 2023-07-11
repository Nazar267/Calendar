<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL & ~E_NOTICE);

global $root_directory;
require_once(dirname(__FILE__) . "/../../libraries/google-api-php-client/autoload.php");
require_once(dirname(__FILE__) . "/RedooCalendar.php");
require_once(dirname(__FILE__) . "/autoloader.php");

\RedooCalendar\Autoload::registerDirectory("~/modules/RedooCalendar/lib");


class_alias("RedooCalendar\\OAuth", "RedooCalendar_OAuth");

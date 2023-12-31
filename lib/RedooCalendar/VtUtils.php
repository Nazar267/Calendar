<?php
/**
This File was developed by Stefan Warnat <vtiger@stefanwarnat.de>

It belongs to the ModuleNamePlaceholder module and must not be distributed without complete extension
 * @version 1.0
 * @updated xxxx-xx-xx
 **/
/**
Changelog

2017-04-02 - add parseJSON for optional JSON Parser
2017-04-02 - add addModuleReferenceField function
2020-12-25 - Update getContentFromURL by Communication Center Modifications
2020-12-25 - add GetAdditionalPath
2021-02-21 - fix array_merge_recursive_distinct
2021-02-26 - add Function to convert UTC to user timezone
 */
namespace RedooCalendar;

use \Vtiger_Module;
use \Vtiger_Block;
use \Vtiger_Field;
use \stdClass;

if(!defined(__NAMESPACE__ . '_ROOTPATH')) {
    /**
     *
     */
    define(__NAMESPACE__ . '_ROOTPATH', dirname(dirname(dirname(__FILE__))));
}

/**
 * Class VtUtils
 * @package ModuleNamePlaceholder
 */
class VtUtils
{
    /**
     * @var
     */
    protected static $UITypesName;

    /**
     * @var string[]
     */
    public static $InventoryModules = array('SalesOrder', 'Invoice', 'Quotes', 'PurchaseOrder');

    /**
     * get all mandatory fields for one tabID
     *
     * @param int $tabid
     * @return array
     */
    public static function getMandatoryFields($tabid) {
        global $adb;

        $sql = "SELECT * FROM vtiger_field WHERE tabid = ".intval($tabid)." AND typeofdata LIKE '%~M%'";
        $result = $adb->pquery($sql);

        $mandatoryFields = array();
        while($row = $adb->fetchByAssoc($result)) {
            $typeofData = explode("~", $row["typeofdata"]);

            if($typeofData[1] == "M") {
                $mandatoryFields[] = $row;
            }
        }

        return $mandatoryFields;
    }

    /**
     * Check if string is JSON formatted
     *
     * @param $string
     * @return bool true if string is valid json
     */
    public static function is_json($string) {
        if(preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $string)) return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive,
     * this happens (documented behavior):
     *
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('org value', 'new value'));
     *
     * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('new value'));
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * @param array $array1
     * @param array $array2
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     */
    public static function array_merge_recursive_distinct ( array &$array1, array &$array2 )
    {
        $merged = $array1;

        foreach ( $array2 as $key => &$value )
        {
            if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
            {
                $merged [$key] = self::array_merge_recursive_distinct ( $merged [$key], $value );
            }
            else
            {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Create a SQL query to find records
     *
     * @param $moduleName string Search within this module
     * @param $search array with [fieldname -> value1, fieldname2 -> value2, ...]
     * @return string returns the sql query
     */
    public static function getFindRecordSql($moduleName, $search) {
        $selectFields = array();
        $module = \CRMEntity::getInstance($moduleName);

        $selectClause = "SELECT";
        $selectFields[] = 'crmid';

        $fromClause = " FROM $module->table_name";

        if($moduleName !== 'Users') {
            $fromClause .= " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $module->table_name.$module->table_index";
        }

        if($module->tab_name) {
            foreach($module->tab_name as $tableName) {
                if($tableName != 'vtiger_crmentity' && $tableName != $module->table_name && $tableName != 'vtiger_inventoryproductrel' && $tableName != 'vtiger_invoice_recurring_info') {
                    if($module->tab_name_index[$tableName]) {
                        $fromClause .= " ".($moduleName !== 'Users' ?'INNER':'LEFT')." JOIN " . $tableName . " ON " . $tableName . '.' . $module->tab_name_index[$tableName] .
                            " = $module->table_name.$module->table_index";
                    }
                }
            }
        }

        $whereClause = " WHERE ";

        if($moduleName !== 'Users') {
            $whereClause .= 'vtiger_crmentity.deleted = 0';
        } else {
            $whereClause .= 'vtiger_users.status = "Active"';
        }

        $params = array();
        $tabid = getTabid($moduleName);
        foreach($search as $field => $value) {
            $columnName = VtUtils::getColumnName($field, $tabid);
            $whereClause .= ' AND `'.$columnName.'` = ?';
            $params[] = $value;
        }

        if($moduleName == 'Leads') {
            $whereClause .= ' AND converted = 0';
        }

        $selectClause .= ' '.implode(',', $selectFields);

        if($moduleName !== 'Users') {
            $query = $selectClause . $fromClause .
                $whereClause .
                " GROUP BY vtiger_crmentity.crmid";
        } else {
            $query = $selectClause . $fromClause .
                $whereClause .
                " GROUP BY vtiger_users.id";

        }

        return $query;
    }

    /**
     * Search record Ids for a given condition
     *
     * @param $moduleName string Search within this module
     * @param $search array with [fieldname -> value1, fieldname2 -> value2, ...]
     * @return array
     */
    public static function findRecordIds($moduleName, $search) {
        $adb = \PearDatabase::getInstance();
        $query = self::getFindRecordSql($moduleName, $search);

        $params = array();
        foreach($search as $field => $value) {
            $params[] = $value;
        }

        $result = $adb->pquery($query, $params);

        $return = array();
        if($adb->num_rows($result) > 0) {
            while($row = $adb->fetchByAssoc($result)) {
                $return[] = $row['crmid'];
            }

        }

        return $return;
    }

    /**
     * Create all tables and create a SQL query from them
     *
     * @param $moduleName string Create SQL Tables for this module
     * @return string
     */
    public static function getModuleTableSQL($moduleName) {
        /**
         * @var $obj \CRMEntity
         */
        $obj = \CRMEntity::getInstance($moduleName);
        $sql = array();
        $sql[] = "FROM ".$obj->table_name;

        $relations = $obj->tab_name_index;
        $pastJoinTables = array($obj->table_name);
        foreach($relations as $table => $index) {
            if(in_array($table, $pastJoinTables)) {
                continue;
            }

            $postJoinTables[] = $table;
            if($table == "vtiger_crmentity") {
                $join = "INNER";
            } else {
                $join = "LEFT";
            }

            $sql[] = $join." JOIN `".$table."` ON (`".$table."`.`".$index."` = `".$obj->table_name."`.`".$obj->table_index."`)";
        }

        return implode("\n", $sql);

    }

    /**
     * Replace only first occurance
     *
     * @param $search
     * @param $replace
     * @param $subject
     * @return string|string[]
     */
    public static function str_replace_first($search, $replace, $subject) {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

    /**
     * Return current User ID, in a Workflow Designer compatible way
     * @return int current User
     */
    public static function getCurrentUserId() {
        global $current_user, $oldCurrentUser;

        if(!empty($oldCurrentUser)) {
            return $oldCurrentUser->id;
        } elseif(!empty($current_user)) {
            return $current_user->id;
        } else {
            return 0;
        }
    }

    /**
     * Return the last auto incremental ID
     * @return mixed
     */
    public static function LastDBInsertID() {
        $adb = \PearDatabase::getInstance();

        $return = $adb->getLastInsertID();

        if(empty($return)) {
            if ($adb->isMySQL()) {
                $sql = 'SELECT LAST_INSERT_ID() as id';
                $result = $adb->query($sql, true);
                $return = $adb->query_result($result, 0, 'id');
            }
        }

        return $return;
    }

    /**
     * Generate a strong password, without missleading chars
     *
     * @param int $length
     * @return false|string
     */
    public static function generate_password($length = 20){
        $a = str_split("abcdefghijkmnopqrstuvwxyABCDEFGHIJKLMNPQRSTUVWXY0123456789-_]}[{-_]}[{-_]}[{");
        shuffle($a);
        return substr( implode($a), 0, $length );
    }


    /**
     * Decrypt an encrypted text
     * @param $value
     * @param string $key
     * @deprecated Use Crypt::encrypt
     * @return string
     */
    public static function decrypt($value, $key = '') {
        return Crypt::decrypt($value, $key);
    }

    /**
     * Encrypt a given text with a given or automated generated key
     * @param $value
     * @param string $key
     * @deprecated Use Crypt::encrypt
     * @return string
     */
    public static function encrypt($value, $key = '') {
        return Crypt::encrypt($value, $key);
    }

    /**
     * Runs a Database Query
     *
     * @param $sql
     * @return mixed
     * @throws \Exception
     */
    public static function query($sql) {
        $adb = \PearDatabase::getInstance();

        if($_COOKIE['stefanDebug'] == '1') {
            $debug = true;
        } else {
            $debug = false;
        }

        $return = $adb->query($sql, $debug);

        self::logSQLError($adb->database->errorMsg(), $sql);

        return $return;
    }

    /**
     * Runs a Database Query by using prepared statements
     *
     * @param $sql string
     * @param array $params
     * @return mixed
     */
    public static function pquery($sql, $params = array()) {
        if(!is_array($params)) {
            $args = func_get_args();
            array_shift($args);
            $params = $args;
        }

        $adb = \PearDatabase::getInstance();
        if($_COOKIE['stefanDebug'] == '1') {
            $debug = true;
        } else {
            $debug = false;
        }

        $return = $adb->pquery($sql, $params, $debug);

        self::logSQLError($adb->database->errorMsg(), $adb->convert2Sql($sql, $params));

        return $return;
    }

    /**
     * Check for Database error and throw Exception
     *
     * @param $error
     * @param string $sqlQuery
     * @throws \Exception
     */
    public static function logSQLError($error, $sqlQuery = '') {
        if(!empty($error)) {
            throw new \Exception('Database Error in Query '.$sqlQuery.' - '.$error);
        }
    }

    /**
     * Returns number of rows a database query generate
     *
     * @param $result
     * @return mixed
     */
    public static function num_rows($result) {
        $adb = \PearDatabase::getInstance();
        return $adb->num_rows($result);
    }

    /**
     * Returns all rows a prepared SQL statement returns within an assoc array
     *
     * @param $sql
     * @param array $params
     * @param array $params2, ... [optional]
     * @return array
     */
    public static function fetchRows($sql, $params = array()) {
        if(!is_array($params)) {
            $args = func_get_args();
            array_shift($args);
            $params = $args;
        }

        $return = array();
        $result = self::pquery($sql, $params);

        while($row = self::fetchByAssoc($result)) {
            $return[] = $row;
        }
        return $return;
    }

    /**
     * Returns first row a prepared SQL statement returns within an assoc array
     * @param $result
     * @param array $params
     * @return mixed
     */
    public static function fetchByAssoc($result, $params = array()) {
        if(is_string($result)) {
            if(!is_array($params)) {
                $args = func_get_args();
                array_shift($args);
                $params = $args;
            }

            $result = self::pquery($result, $params);
        }

        $adb = \PearDatabase::getInstance();
        return $adb->fetchByAssoc($result);
    }

    /**
     * multibyte version of basename() function to support chinese chars in filenames
     *
     * @param $path
     * @return string|string[]
     */
    public static function mb_basename($path)
    {
        $separator = " qq ";
        $path = preg_replace("/[^ ]/u", $separator."\$0".$separator, $path);
        $base = basename($path);
        $base = str_replace($separator, "", $base);
        return $base;
    }

    /**
     * multibyte version of dirname() function to support chinese chars in dirnames
     * @param $path
     * @return string|string[]
     */
    public static function mb_dirname($path)
    {
        $separator = " qq ";
        $path = preg_replace("/[^ ]/u", $separator."\$0".$separator, $path);
        $base = basename($path);
        $base = str_replace($separator, "", $base);
        return $base;
    }

    /**
     * Returns max possible upload size by PHP configuration
     *
     * @return int|string
     */
    public static function getMaxUploadSize() {
        $max_upload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);

        $val = trim($upload_mb).'m';
        $last = strtolower($val[strlen($val)-1]);

        switch($last)
        {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Returns pseudo secure hash of current Module installation
     *
     * @param $value
     * @return string
     */
    public static function getSecureHash($value) {
        return sha1("ökj".sha1("ökj".md5($value."ökj".(dirname(__FILE__)))));
    }

    /**
     * generate ColumnName from FieldName and tabid
     *
     * @param string $fieldname
     * @param int $tabid [optional]
     * @return mixed|string
     */
    public static function getColumnName($fieldname, $tabid = null) {
        global $adb;
        $sql = "SELECT columnname FROM vtiger_field WHERE fieldname = ?" . (!empty($tabid) ? ' AND tabid = '.$tabid : '');
        $result = $adb->pquery($sql, array($fieldname), true);

        if($adb->num_rows($result) == 0) {
            return $fieldname;
        }

        return $adb->query_result($result, 0, "columnname");
    }

    /**
     * generate ColumnName from FieldName and tabid
     *
     * @param string $fieldname
     * @param int $tabid [optional]
     * @return mixed|string
     */
    public static function getFieldName($columnname, $tabid = null) {
        $adb = \PearDatabase::getInstance();

        $sql = 'SELECT fieldname FROM vtiger_field WHERE columnname = ?' . (!empty($tabid) ? ' AND tabid = '.$tabid : '');
        $result = $adb->pquery($sql, array($columnname));

        if($adb->num_rows($result) == 0) {
            return $columnname;
        }

        return $adb->query_result($result, 0, 'fieldname');
    }

    /**
     * Calculate white/black text color based on a given background Color in hex format
     * @param $backgroundColor
     * @return string
     */
    public static function getTextColor($backgroundColor) {
        $brightness = self::getTextBrightness($backgroundColor);
        return ($brightness < 140) ? "#FFFFFF" : "#000000";
    }

    /**
     * calculate color brightness value
     * @param $hexColor
     * @return float
     */
    public static function getTextBrightness($hexColor) {
        $rgb = self::hex2RGB($hexColor);
        $brightness = sqrt(
            $rgb["red"] * $rgb["red"] * .299 +
            $rgb["green"] * $rgb["green"] * .587 +
            $rgb["blue"] * $rgb["blue"] * .114);
//var_dump($backgroundColor, $brightness);
//        return $brightness;
        return $brightness;
    }

    /**
     * @param $hexStr
     * @param bool $returnAsString
     * @param string $seperator
     * @return array|bool|string
     */
    private static function hex2RGB($hexStr, $returnAsString = false, $seperator = ',') {
        $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
        $rgbArray = array();
        if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
            $colorVal = hexdec($hexStr);
            $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
            $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
            $rgbArray['blue'] = 0xFF & $colorVal;
        } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
            $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
            $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
            $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
        } else {
            return false; //Invalid hex color code
        }
        return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
    }

    /**
     * Faster version to simple get fields for module and the fieldTypename if no uitype is filtered
     *
     * @param $module_name
     * @param bool $uitype [optional] If set, only return fields with this uitype and not fieldtype is returned
     * @return mixed
     */
    public static function getFieldsWithTypes($module_name, $uitype = false) {
        $adb = \PearDatabase::getInstance();

        if($uitype !== false && !is_array($uitype)) {
            $uitype = array($uitype);
        }

        $query = "SELECT columnname, uitype, fieldname, typeofdata FROM vtiger_field WHERE tabid = ? ".($uitype !== false ? ' AND uitype IN ('.implode(',', $uitype).')': '')." ORDER BY sequence";
        $queryParams = Array(getTabid($module_name));

        $result = $adb->pquery($query, $queryParams);
        $fields = array();

        while($valuemap = $adb->fetchByAssoc($result)) {
            $tmp = new \stdClass();

            $tmp->name = $valuemap['fieldname'];
            $tmp->column = $valuemap['columnname'];

            if($uitype === false) {
                $tmp->type->name = self::getFieldTypeName(intval($valuemap['uitype']), $valuemap['typeofdata']);
            }

            $fields[$tmp->name] = $tmp;
        }

        return $fields;
    }

    /**
     * Function returns all fielddata to the field from parameters
     *
     * @param string $fieldname The FieldName (NOT Columnname)
     * @param int [$tabid]
     * @return array|bool|null
     */
    public static function getFieldInfo($fieldname, $tabid = null) {
        global $adb;

        if($fieldname == 'crmid') {
            return array(
                'tablename' => 'vtiger_crmentity',
                'columnname' => 'crmid',
                'fieldlabel' => 'Record ID',
                'fieldname' => 'crmid'
            );
        }

        $sql = "SELECT * FROM vtiger_field WHERE fieldname = ?" . (!empty($tabid) ? ' AND tabid = '.$tabid : '');
        $result = $adb->pquery($sql, array($fieldname), true);

        if($adb->num_rows($result) == 0) {
            return false;
        }

        return $adb->fetchByAssoc($result);
    }

    /**
     * @var array
     */
    private static $_FieldCache = array();

    /**
     * @param $module_name
     * @param bool $uitype
     * @return mixed
     */
    public static function getFieldsForModule($module_name, $uitype = false) {
        global $current_language;

        if($uitype !== false && !is_array($uitype)) {
            $uitype = array($uitype);
        }

        $cacheKey = md5(serialize($uitype).$module_name);

        if(isset(self::$_FieldCache[$cacheKey])) {
            return unserialize(serialize(self::$_FieldCache[$cacheKey]));
        }

        $adb = \PearDatabase::getInstance();
        $query = "SELECT * FROM vtiger_field WHERE tabid = ? ORDER BY sequence";
        $queryParams = Array(getTabid($module_name));

        $result = $adb->pquery($query, $queryParams);
        $fields = array();

        while($valuemap = $adb->fetchByAssoc($result)) {
            $tmp = new \stdClass();
            $tmp->id = $valuemap['fieldid'];
            $tmp->name = $valuemap['fieldname'];
            $tmp->label= $valuemap['fieldlabel'];
            $tmp->column = $valuemap['columnname'];
            $tmp->table  = $valuemap['tablename'];
            $tmp->uitype = intval($valuemap['uitype']);
            $tmp->typeofdata = $valuemap['typeofdata'];
            $tmp->helpinfo = $valuemap['helpinfo'];
            $tmp->masseditable = $valuemap['masseditable'];
            $tmp->displaytype   = $valuemap['displaytype'];
            $tmp->generatedtype = $valuemap['generatedtype'];
            $tmp->readonly      = $valuemap['readonly'];
            $tmp->presence      = $valuemap['presence'];
            $tmp->defaultvalue  = $valuemap['defaultvalue'];
            $tmp->quickcreate = $valuemap['quickcreate'];
            $tmp->sequence = $valuemap['sequence'];
            $tmp->summaryfield = $valuemap['summaryfield'];

            $fields[] = $tmp;
        }

        $module = $module_name;
        if($module != "Events") {
//            $modLang = return_module_language($current_language, $module);
        }
        $moduleFields = array();

        /*
                // Fields in this module
                include_once("vtlib/Vtiger/Module.php");

                   #$alle = glob(dirname(__FILE__).'/functions/*.inc.php');
                   #foreach($alle as $datei) { include $datei; }


                   $instance = Vtiger_Module::getInstance($module);
                   //$blocks = Vtiger_Block::getAllForModule($instance);



                $fields = Vtiger_Field::getAllForModule($instance);
        */
        //$blocks = Vtiger_Block::getAllForModule($instance);
        if(is_array($fields)) {

            foreach($fields as $field) {
                if($uitype !== false && !in_array($field->uitype, $uitype)) {
                    continue;
                }

                $field->label = getTranslatedString((isset($modLang[$field->label])?$modLang[$field->label]:$field->label), $module_name);
                $field->type = new StdClass();
                $field->type->name = self::getFieldTypeName($field->uitype, $field->typeofdata);

                if($field->type->name == 'reference') {
                    $modules = self::getModuleForReference($field->block->module->id, $field->name, $field->uitype);

                    $field->type->refersTo = $modules;
                }
                if($field->type->name == 'picklist' || $field->type->name == 'multipicklist') {
                    $language = \Vtiger_Language_Handler::getModuleStringsFromFile($current_language, $field->block->module->name);
                    if(empty($language)) {
                        $language = \Vtiger_Language_Handler::getModuleStringsFromFile('en_us', $field->block->module->name);
                    }

                    if($field->uitype == 98) {
                        $query = "select * from vtiger_role";
                        $result = $adb->pquery($query, array());

                        while ($row = $adb->fetchByAssoc($result)) {
                            $field->type->picklistValues[$row['roleid']] = str_repeat('&nbsp;&nbsp;', $row['depth']).$row['rolename'];
                        }
                    } else {
                        switch ($field->name) {
                            case 'hdnTaxType':
                                $field->type->picklistValues = array(
                                    'group' => 'Group',
                                    'individual' => 'Individual',
                                );
                                break;
                            case 'region_id':
                                $field->type->picklistValues = array();
                                break;
                            case 'email_flag':
                                $field->type->picklistValues = array(
                                    'SAVED' => 'SAVED',
                                    'SENT' => 'SENT',
                                    'MAILSCANNER' => 'MAILSCANNER',
                                );
                                break;
                            case 'currency_id':
                                $field->type->picklistValues = array();
                                $currencies = getAllCurrencies();
                                foreach ($currencies as $currencies) {
                                    $field->type->picklistValues[$currencies['currency_id']] = $currencies['currencylabel'];
                                }

                                break;
                            default:
                                $field->type->picklistValues = getAllPickListValues($field->name, $language['languageStrings']);
                                break;
                        }
                    }

                    //$field->type->picklistValues = getAllPickListValues($field->name, $language['languageStrings']);
                }

                $moduleFields[] = $field;

            }

            if($uitype === false) {
                $crmid = new StdClass();
                $crmid->name = 'crmid';
                $crmid->label = 'ID';
                $crmid->type = 'string';
                $moduleFields[] = $crmid;
            }

        }

        self::$_FieldCache[$cacheKey] = $moduleFields;
//7f18c166060f17d0ce582a4359ad1cbc
        return unserialize(serialize($moduleFields));
    }

    /**
     * @param $module_name
     * @return array
     */
    public static function getReferenceFieldsForModule($module_name) {
        global $adb;
        $relations = array();

        $sql = "SELECT tabid, fieldname, fieldlabel, uitype, fieldid FROM vtiger_field WHERE tabid = ".getTabID($module_name)." AND (uitype = 10 OR uitype = 51 OR uitype = 101 OR uitype = 52 OR uitype = 53 OR uitype = 57 OR uitype = 58 OR uitype = 59 OR uitype = 73 OR uitype = 75 OR uitype = 76 OR uitype = 78 OR uitype = 80 OR uitype = 81 OR uitype = 68)";
        $result = $adb->query($sql);

        while($row = $adb->fetchByAssoc($result)) {
            switch ($row["uitype"]) {
                case "51":
                    $row["module"] = "Accounts";
                    $relations[] = $row;
                    break;
                case "52":
                    $row["module"] = "Users";
                    $relations[] = $row;
                    break;
                case "1024":
                    $row["module"] = "Users";
                    $relations[] = $row;
                    break;
                case "53":
                    $row["module"] = "Users";
                    $relations[] = $row;
                    break;
                case "57":
                    $row["module"] = "Contacts";
                    $relations[] = $row;
                    break;
                case "58":
                    $row["module"] = "Campaigns";
                    $relations[] = $row;
                    break;
                case "59":
                    $row["module"] = "Products";
                    $relations[] = $row;
                    break;
                case "73":
                    $row["module"] = "Accounts";
                    $relations[] = $row;
                    break;
                case "75":
                    $row["module"] = "Vendors";
                    $relations[] = $row;
                    break;
                case "81":
                    $row["module"] = "Vendors";
                    $relations[] = $row;
                    break;
                case "76":
                    $row["module"] = "Potentials";
                    $relations[] = $row;
                    break;
                case "78":
                    $row["module"] = "Quotes";
                    $relations[] = $row;
                    break;
                case "101":
                    $row["module"] = "Users";
                    $relations[] = $row;
                    break;
                case "80":
                    $row["module"] = "SalesOrder";
                    $relations[] = $row;
                    break;
                case "68":
                    $row["module"] = "Accounts";
                    $relations[] = $row;
                    $row["module"] = "Contacts";
                    break;
                case "10": # Possibly multiple relations
                    $resultTMP = VtUtils::pquery('SELECT relmodule FROM `vtiger_fieldmodulerel` WHERE fieldid = ?', array($row["fieldid"]));
                    while ($data = $adb->fetch_array($resultTMP)) {
                        $row["module"] = $data["relmodule"];
                        $relations[] = $row;
                    }
                    break;
            }
        }

        return $relations;
    }

    /**
     * @var int[]
     */
    public static $referenceUitypes = array(51,52,53,57,58,59,73,75,66,81,76,78,80,68,10,1024);

    /**
     * @param $tabid
     * @param $fieldname
     * @param $uitype
     * @return array
     */
    public static function getModuleForReference($tabid, $fieldname, $uitype) {
        $addReferences = array();

        switch ($uitype) {
            case "51":
                $addReferences[] = "Accounts";
                break;
            case "1024":
            case "52":
                $addReferences[] = "Users";
                break;
            case "53":
                $addReferences[] = "Users";
                break;
            case "57":
                $addReferences[] = "Contacts";
                break;
            case "58":
                $addReferences[] = "Campaigns";
                break;
            case "59":
                $addReferences[] = "Products";
                break;
            case "66":
                $addReferences[] = "Accounts";
                $addReferences[] = "Leads";
                $addReferences[] = "Potentials";
                $addReferences[] = "HelpDesk";
                $addReferences[] = "Campaigns";
                break;
            case "73":
                $addReferences[] = "Accounts";
                break;
            case "75":
                $addReferences[] = "Vendors";
                break;
            case "81":
                $addReferences[] = "Vendors";
                break;
            case "76":
                $addReferences[] = "Potentials";
                break;
            case "78":
                $addReferences[] = "Quotes";
                break;
            case "80":
                $addReferences[] = "SalesOrder";
                break;
            case "68":
                $addReferences[] = "Accounts";
                $addReferences[] = "Contacts";
                break;
            case "10": # Possibly multiple relations
                global $adb;

                $sql = "SELECT fieldid FROM vtiger_field WHERE tabid = ".intval($tabid)." AND fieldname = ?";
                $result = $adb->pquery($sql, array($fieldname), true);

                $fieldid = $adb->query_result($result, 0, "fieldid");

                $result = VtUtils::pquery('SELECT relmodule FROM `vtiger_fieldmodulerel` WHERE fieldid = ?', array($fieldid));
                while ($data = $adb->fetch_array($result)) {
                    $addReferences[] = $data["relmodule"];
                }
                break;
        }

        return $addReferences;
    }

    /**
     * @param $uitype
     * @param bool $typeofdata
     * @return string
     * @throws \Exception
     */
    public static function getFieldTypeName($uitype, $typeofdata = false) {
        global $adb;
        switch($uitype) {
            case 117:
            case 115:
            case 15:
            case 16:
            case 98:
                return 'picklist';
                break;
            case 5:
            case 70:
            case 23:
                return 'date';
                break;
            case 6:
                return 'datetime';
                break;
            case 1024:
                return 'reference';
                break;
        }

        if(empty(self::$UITypesName)) {
            $result = self::query("select * from vtiger_ws_fieldtype");

            while($row = $adb->fetchByAssoc($result)) {
                self::$UITypesName[$row['uitype']] = $row['fieldtype'];
            }
        }

        if(!empty(self::$UITypesName[$uitype])) {
            return self::$UITypesName[$uitype];
        }

        $type = explode('~', $typeofdata);
        switch($type[0]){
            case 'T': return "time";
            case 'D':
            case 'DT': return "date";
            case 'E': return "email";
            case 'N':
            case 'NN': return "double";
            case 'P': return "password";
            case 'I': return "integer";
            case 'V':
            default: return "string";
        }

    }

    /**
     * @param $fields
     * @param array $fieldTypes
     * @param bool $flat
     * @return array
     * @throws \Exception
     */
    public static function filterByFieldtypes($fields, $fieldTypes = array(), $flat = false) {
        $results = array();
        $fieldTypes = array_flip($fieldTypes);

        if($flat === true) {
            foreach($fields as $index => $field) {
                if(is_object($field)) {
                    $uitype = $field->uitype;
                    $typeofdata = $field->typeofdata;
                } else {
                    $uitype = $field['uitype'];
                    $typeofdata = $field['typeofdata'];
                }
                $typename = VtUtils::getFieldTypeName($uitype, $typeofdata);

                if(isset($fieldTypes[$typename])) {
                    $results[] = $field;
                }
            }
        } else {
            foreach($fields as $blockLabel => $blockFields) {
                $results[$blockLabel] = self::filterByFieldtypes($blockFields, array_flip($fieldTypes), true);
            }
        }

        return $results;
    }

    /**
     * @param $module_name
     * @param bool $references
     * @param string $refTemplate
     * @param string $activityType
     * @return array
     * @throws \Exception
     */
    public static function getFieldsWithBlocksForModule($module_name, $references = false, $refTemplate = "([source]: ([module]) [destination])", $activityType = 'Event') {
        global $current_language, $adb, $app_strings;
        \Vtiger_Cache::$cacheEnable = false;

        $start = microtime(true);
        if(empty($refTemplate) && $references == true) {
            $refTemplate = "([source]: ([module]) [destination])";
        }
        //////echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
        // Fields in this module
        include_once("vtlib/Vtiger/Module.php");

        #$alle = glob(dirname(__FILE__).'/functions/*.inc.php');
        #foreach($alle as $datei) { include $datei;		 }

        if($module_name == 'Calendar' && $activityType == 'Task') {
            $module_name = 'Events';
        }

        $tmpEntityModules = VtUtils::getEntityModules(false);
        $entityModules = array();
        foreach($tmpEntityModules as $tabid => $data) {
            $entityModules[$data[0]] = $data[0];
        }
        $module = $module_name;
        $instance = Vtiger_Module::getInstance($module);
        $blocks = Vtiger_Block::getAllForModule($instance);

        ////echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
        if($module != "Events") {
            $langModule = $module;
        } else {
            $langModule = "Calendar";
        }

        $modLang = return_module_language($current_language, $langModule);
        //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
        $moduleFields = array();

        $addReferences = array();
        $referenceFields = array();

        if(is_array($blocks)) {
            foreach($blocks as $block) {

                $fields = Vtiger_Field::getAllForBlock($block, $instance);

                //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
                if(empty($fields) || !is_array($fields)) {
                    continue;
                }

                foreach($fields as $field) {
                    if($field->name == 'eventstatus' && $module_name != 'Events') continue;
                    if($field->name == 'taskstatus' && $module_name != 'Calendar') continue;

                    $field->label = getTranslatedString($field->label, $langModule);
                    $field->type = new StdClass();

                    $field->type->name = self::getFieldTypeName($field->uitype, $field->typeofdata);

                    if($field->type->name == 'picklist' || $field->type->name == 'multipicklist') {
                        if($field->uitype == 98) {
                            $query = "select * from vtiger_role";
                            $result = $adb->pquery($query, array());

                            while ($row = $adb->fetchByAssoc($result)) {
                                $field->type->picklistValues[$row['roleid']] = str_repeat('&nbsp;', $row['depth']).$row['rolename'];
                            }
                        } else {
                            switch ($field->name) {
                                case 'hdnTaxType':
                                    $field->type->picklistValues = array(
                                        'group' => 'Group',
                                        'individual' => 'Individual',
                                    );
                                    break;
                                case 'email_flag':
                                    $field->type->picklistValues = array(
                                        'SAVED' => 'SAVED',
                                        'SENT' => 'SENT',
                                        'MAILSCANNER' => 'MAILSCANNER',
                                    );
                                    break;
                                case 'region_id':
                                    $field->type->picklistValues = array();
                                    break;
                                case 'currency_id':
                                    $field->type->picklistValues = array();
                                    $currencies = getAllCurrencies();
                                    foreach ($currencies as $currencies) {
                                        $field->type->picklistValues[$currencies['currency_id']] = $currencies['currencylabel'];
                                    }

                                    break;
                                default:
                                    $language = \Vtiger_Language_Handler::getModuleStringsFromFile($current_language, $field->block->module->name);
                                    if (empty($language)) {
                                        $language = \Vtiger_Language_Handler::getModuleStringsFromFile('en_us', $field->block->module->name);
                                    }

                                    $field->type->picklistValues = getAllPickListValues($field->name, $language['languageStrings']);
                                    break;
                            }
                        }
                    }

                    if($field->uitype == 26) {
                        $field->type->name = 'picklist';

                        $sql = 'SELECT * FROM vtiger_attachmentsfolder ORDER BY foldername';
                        $result = $adb->query($sql);

                        $field->type->picklistValues = array();
                        while($row = $adb->fetchByAssoc($result)) {
                            $field->type->picklistValues[$row['folderid']] = $row['foldername'];
                        }
                    }

                    if(in_array($field->uitype, self::$referenceUitypes)) {
                        $modules = self::getModuleForReference($field->block->module->id, $field->name, $field->uitype);

                        $field->type->refersTo = $modules;
                    }

                    if($field->type->name == 'reference') {
                        $field->label .= ' ID';
                        $referenceFields[] = $field;
                    }

                    if($references !== false) {

                        switch ($field->uitype) {
                            case "51":
                                $addReferences[] = array($field, "Accounts");
                                break;
                            case "52":
                                $addReferences[] = array($field, "Users");
                                break;
                            case "53":
                                $addReferences[] = array($field, "Users");
                                break;
                            case "57":
                                $addReferences[] = array($field, "Contacts");
                                break;
                            case "58":
                                $addReferences[] = array($field,"Campaigns");
                                break;
                            case "59":
                                $addReferences[] = array($field,"Products");
                                break;
                            case "73":
                                $addReferences[] = array($field,"Accounts");
                                break;
                            case "75":
                                $addReferences[] = array($field,"Vendors");
                                break;
                            case "81":
                                $addReferences[] = array($field,"Vendors");
                                break;
                            case "76":
                                $addReferences[] = array($field,"Potentials");
                                break;
                            case "78":
                                $addReferences[] = array($field,"Quotes");
                                break;
                            case "80":
                                $addReferences[] = array($field,"SalesOrder");
                                break;
                            case "66":
                                $addReferences[] = array($field,"Accounts");
                                $addReferences[] = array($field,"Leads");
                                $addReferences[] = array($field,"Potentials");
                                $addReferences[] = array($field,"HelpDesk");
                                $addReferences[] = array($field,"Campaigns");
                                break;
                            case "68":
                                $addReferences[] = array($field,"Accounts");
                                $addReferences[] = array($field,"Contacts");
                                break;
                            case "10": # Possibly multiple relations
                                $result = self::pquery('SELECT relmodule FROM `vtiger_fieldmodulerel` WHERE fieldid = ?', array($field->id));
                                while ($data = $adb->fetch_array($result)) {
                                    $addReferences[] = array($field,$data["relmodule"]);
                                }
                                break;
                        }
                    }

                    $moduleFields[getTranslatedString($block->label, $langModule)][] = $field;
                }
            }

            $crmid = new StdClass();
            $crmid->name = 'crmid';
            $crmid->label = 'ID';
            $crmid->type = 'string';
            reset($moduleFields);
            $first_key = key($moduleFields);
            $moduleFields[$first_key] = array_merge(array($crmid), $moduleFields[$first_key]);

            if($module == 'Products' || $module == 'Services') {
                $currency_details = getAllCurrencies('all');
                foreach($currency_details as $currency) {
                    if($currency['currencylabel'] == vglobal('currency_name')) continue;
                    $field = new StdClass();
                    $field->name = "curname".$currency['curid'];
                    $field->label = getTranslatedString('Unit Price', $langModule).' '.$currency['currencycode'];
                    $field->type = 'currency';

                    array_push($moduleFields[getTranslatedString('LBL_PRICING_INFORMATION', $langModule)], $field);
                }
            }

            if(in_array($module, self::$InventoryModules)) {
                $crmid = new StdClass();
                $crmid->name = 'hdnS_H_Amount';
                $crmid->label = getTranslatedString("Shipping & Handling Charges", $module);
                $crmid->type = 'string';
                reset($moduleFields);
                $first_key = key($moduleFields);
                $moduleFields[$first_key] = array_merge($moduleFields[$first_key], array($crmid));
            }



        }
        //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
        $rewriteFields = array(
            "assigned_user_id" => "smownerid"
        );

        if($references !== false) {
            $field = new StdClass();
            $field->name = "current_user";
            $field->label = getTranslatedString("LBL_CURRENT_USER", "Workflow2");
            $addReferences[] = array($field, "Users");

            if(!empty($referenceFields)) {
                foreach($referenceFields as $refField) {
                    $crmid = new StdClass();
                    $crmid->name = str_replace(array("[source]", "[module]", "[destination]"), array($refField->name, 'ModuleName', 'ModuleName'), $refTemplate);
                    $crmid->label = $refField->label.' / Modulename';
                    $crmid->type->name = 'picklist';
                    $crmid->type->picklistValues = $entityModules;
                    reset($moduleFields);
                    $first_key = key($moduleFields);
                    $moduleFields[$first_key] = array_merge($moduleFields[$first_key], array($crmid));
                }
            }

        }

        if(is_array($addReferences)) {

            foreach($addReferences as $refField) {
                //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
                $fields = self::getFieldsForModule($refField[1]);

                foreach($fields as $field) {
                    $field->label = "(".(isset($app_strings[$refField[1]])?$app_strings[$refField[1]]:$refField[1]).") ".$field->label;

                    if(!empty($rewriteFields[$refField[0]->name])) {
                        $refField[0]->name = $rewriteFields[$refField[0]->name];
                    }
                    $name = str_replace(array("[source]", "[module]", "[destination]"), array($refField[0]->name, $refField[1], $field->name), $refTemplate);
                    $field->name = $name;

                    $moduleFields["References (".$refField[0]->label.")"][] = $field;
                }
            }
        }

        \Vtiger_Cache::$cacheEnable = true;
        return $moduleFields;
    }

    /**
     * @return mixed
     */
    public static function getAdminUser() {
        return \Users::getActiveAdminUser();
    }

    /**
     * @param $arr
     * @param $col
     * @param int $dir
     */
    public static function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
        $sort_col = array();
        foreach ($arr as $key=> $row) {
            $sort_col[$key] = strtolower($row[$col]);
        }

        array_multisort($sort_col, $dir, $arr);
    }

    /**
     * @var array
     */
    private static $entityModulesCache = array();

    /**
     * @param bool $sorted
     * @return array
     */
    public static function getEntityModules($sorted = false) {
        if(!empty(self::$entityModulesCache)) {
            $module = self::$entityModulesCache;

            if($sorted == true) {
                asort($module);
            }

            return $module;
        }

        $tmpModules = \Vtiger_Module_Model::getEntityModules();
        $module = array();
        foreach($tmpModules as $tmp) {
            if($tmp->isEntityModule()) {
                $sql = 'SELECT fieldid FROM vtiger_field WHERE tabid = ? LIMIT 1';
                $result = self::fetchByAssoc($sql, array($tmp->id));

                if(empty($result)) continue;

                if($tmp->name == 'Calendar') {
                    $label = 'LBL_TASK';
                } else {
                    $label = $tmp->label;
                }

                $module[$tmp->id] = array($tmp->name, getTranslatedString($label, $tmp->name));
            }
        }

        self::$entityModulesCache = $module;
        if($sorted == true) {
            asort($module);
        }
        return $module;
    }

    /**
     * @param $viewer
     * @return mixed
     */
    public static function initViewer($viewer) {

        return $viewer;
    }

    /**
     * @param $params
     * @param $smarty
     * @return string
     */
    public static function Smarty_HelpURL($params, &$smarty) {
        if(empty($params["height"])) {
            $params["height"] = 18;
        } else {
            $params["height"] = intval($params["height"]);
        }
        return "<a href='http://support.stefanwarnat.de/en:extensions:".$params["url"]."' target='_blank'><img src='https://shop.stefanwarnat.de/help.png' style='margin-bottom:-".($params["height"] - 18)."px' border='0'></a>";
    }

    /**
     * @param $module_name
     * @return array
     */
    public static function getRelatedModules($module_name) {
        global $adb, $current_user, $app_strings;

        require('user_privileges/user_privileges_' . $current_user->id . '.php');

        $sql = "SELECT vtiger_relatedlists.related_tabid,vtiger_relatedlists.label, vtiger_relatedlists.name, vtiger_tab.name as module_name FROM
                vtiger_relatedlists
                    INNER JOIN vtiger_tab ON(vtiger_tab.tabid = vtiger_relatedlists.related_tabid)
                WHERE vtiger_relatedlists.tabid = '".getTabId($module_name)."' AND related_tabid not in (SELECT tabid FROM vtiger_tab WHERE presence = 1) ORDER BY sequence, vtiger_relatedlists.relation_id";
        $result = $adb->query($sql);

        $relatedLists = array();
        while($row = $adb->fetch_array($result)) {

            // Nur wenn Zugriff erlaubt, dann zugreifen lassen
            if ($profileTabsPermission[$row["related_tabid"]] == 0) {
                if ($profileActionPermission[$row["related_tabid"]][3] == 0) {
                    $relatedLists[] = array(
                        "related_tabid" => $row["related_tabid"],
                        "module_name" => $row["module_name"],
                        "action" => $row["name"],
                        "label" => isset($app_strings[$row["label"]])?$app_strings[$row["label"]]:$row["label"],
                    );
                }
            }

        }

        return $relatedLists;
    }

    /**
     * @param $tabid
     * @return mixed
     */
    public static function getModuleName($tabid) {
        global $adb;

        $sql = "SELECT name FROM vtiger_tab WHERE tabid = ".intval($tabid);
        $result = $adb->query($sql);

        return $adb->query_result($result, 0, "name");
    }

    /**
     * @param $date
     * @return mixed
     */
    public static function formatUserDate($date) {
        return \DateTimeField::convertToUserFormat($date);
    }

    /**
     * @param $size
     * @param string[] $sizes
     * @return string
     */
    public static function formatFilesize($size, $sizes = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'))
    {
        if ($size == 0) return('n/a');
        return (round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $sizes[$i]);
    }

    /**
     * @param $date
     * @return mixed
     */
    public static function convertToUserTZ($date) {
        if(class_exists("DateTimeField")) {
            $return = \DateTimeField::convertToUserTimeZone($date);
            return $return->format("Y-m-d H:i:s");
        } else {
            return $date;
        }
    }

    /**
     * @param $moduleName
     * @param bool $loadReferences
     * @param string $nameFormat
     * @return array
     */
    public static function describeModule($moduleName, $loadReferences = false, $nameFormat = "###") {
        global $current_user;
        $columnsRewrites = array(
            "assigned_user_id" => "smownerid"
        );
        $loadedRefModules = array();

        require_once("include/Webservices/DescribeObject.php");
        $refFields = array();
        $return = array();
        $describe = vtws_describe($moduleName, $current_user);

        $return["crmid"] = array(
            "name" => "crmid",
            "label" => "ID",
            "mandatory" => false,
            "type" => array("name" => "string"),
            "editable" => false
        );

        /** Current User mit aufnehmen! */
        $describe["fields"][] = array ( 'name' => 'current_user', 'label' => 'current user ', 'mandatory' => false, 'type' => array ( 'name' => 'reference', 'refersTo' => array ( 0 => 'Users' ) ) );

        foreach($describe["fields"] as $field) {
            if(!empty($columnsRewrites[$field["name"]])) {
                $field["name"] = $columnsRewrites[$field["name"]];
            }
            if($field["name"] == "smownerid") {
                $field["type"]["name"] = "reference";
                $field["type"]["refersTo"] = array("Users");
            }

            if($field["type"]["name"] == "reference" && $loadReferences == true) {
                foreach($field["type"]["refersTo"] as $refModule) {
                    #if(!empty($loadedRefModules[$refModule])) continue;

                    $refFields = array_merge($refFields, self::describeModule($refModule, false, "(".$field["name"].": (".$refModule.") ###)"));

                    #var_dump($refFields);
                    $loadedRefModules[$refModule] = "1";
                }
            }

            $fieldName = str_replace("###", $field["name"], $nameFormat);

            $return[$fieldName] = $field;

        }

        /** Assigned Users */
        global $adb;
        $sql = "SELECT id,user_name,first_name,last_name FROM vtiger_users WHERE status = 'Active'";
        $result = $adb->query($sql);
        while($user = $adb->fetchByAssoc($result)) {
            $user["id"] = "19x".$user["id"];
            $availUser["user"][] = $user;
        }
        $sql = "SELECT * FROM vtiger_groups ORDER BY groupname";
        $result = $adb->query($sql);
        while($group = $adb->fetchByAssoc($result)) {
            $group["groupid"] = "20x".$group["groupid"];
            $availUser["group"][] = $group;
        }
        /** Assigned Users End */

        $return["assigned_user_id"]["type"]["name"] = "picklist";
        $return["assigned_user_id"]["type"]["picklistValues"] = array();

        $return["assigned_user_id"]["type"]["picklistValues"][] = array("label" => '$currentUser', "value" => '$current_user_id');

        for($a = 0; $a < count($availUser["user"]); $a++) {
            $return["assigned_user_id"]["type"]["picklistValues"][] = array("label" => $availUser["user"][$a]["user_name"], "value" => $availUser["user"][$a]["id"]);
        }
        for($a = 0; $a < count($availUser["group"]); $a++) {
            $return["assigned_user_id"]["type"]["picklistValues"][] = array("label" => "Group: " . $availUser["group"][$a]["groupname"], "value" => $availUser["group"][$a]["groupid"]);
        }

        $return["smownerid"] = $return["assigned_user_id"];


        $return = array_merge($return, $refFields);

        return $return;
    }

    /**
     * @param $tableName
     * @return bool
     */
    public static function existTable($tableName) {
        global $adb;
        $tables = $adb->get_tables();

        foreach($tables as $table) {
            if($table == $tableName)
                return true;
        }

        return false;
    }

    /**
     * @param $table
     * @param $colum
     * @param $type
     * @param bool $default
     * @param bool $callbackIfNew
     * @param bool $resetType
     * @return bool
     */
    public static function checkColumn($table, $colum, $type, $default = false, $callbackIfNew = false, $resetType = false) {
        global $adb;

        if(!self::existTable($table)) {
            return false;
        }

        $result = $adb->query("SHOW COLUMNS FROM `".$table."` LIKE '".$colum."'");
        $exists = ($adb->num_rows($result))?true:false;

        if($exists == false) {
            echo "Add column '".$table."'.'".$colum."'<br>";
            $adb->query("ALTER TABLE `".$table."` ADD `".$colum."` ".$type." NOT NULL".($default !== false?" DEFAULT  '".$default."'":""), false);

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

    /**
     * @param $str
     * @return bool
     */
    public static function is_utf8($str){
        $strlen = strlen($str);
        for($i=0; $i<$strlen; $i++){
            $ord = ord($str[$i]);
            if($ord < 0x80) continue; // 0bbbbbbb
            elseif(($ord&0xE0)===0xC0 && $ord>0xC1) $n = 1; // 110bbbbb (exkl C0-C1)
            elseif(($ord&0xF0)===0xE0) $n = 2; // 1110bbbb
            elseif(($ord&0xF8)===0xF0 && $ord<0xF5) $n = 3; // 11110bbb (exkl F5-FF)
            else return false; // ungültiges UTF-8-Zeichen
            for($c=0; $c<$n; $c++) // $n Folgebytes? // 10bbbbbb
                if(++$i===$strlen || (ord($str[$i])&0xC0)!==0x80)
                    return false; // ungültiges UTF-8-Zeichen
        }
        return true; // kein ungültiges UTF-8-Zeichen gefunden
    }

    /**
     * @param $expression
     * @return string|string[]|null
     */
    public static function decodeExpressions($expression) {
        $expression = preg_replace_callback('/\\$\{(.*)\}\}&gt;/s', array("VtUtils", "_decodeExpressions"), $expression);

        return $expression;
    }

    /**
     * @param $expression
     * @return string|string[]|null
     */
    public static function maskExpressions($expression) {
        $expression = preg_replace_callback('/\\$\{(.*)\}\}>/s', array("VtUtils", "_maskExpressions"), $expression);

        return $expression;
    }

    /**
     * @param $match
     * @return string
     */
    protected static function _maskExpressions($match) {
        return '${ ' . htmlentities(($match[1])) . ' }}>';
    }

    /**
     * @param $match
     * @return string
     */
    protected static function _decodeExpressions($match) {
        return '${ ' . html_entity_decode(htmlspecialchars_decode($match[1])) . ' }}>';
    }

    /**
     * @param $url
     * @param array $params
     * @param string $method
     * @param array $options
     * @return bool|false|string
     * @throws \Exception
     */
    public static function getContentFromUrl($url, $params = array(), $method = 'auto', $options = array(), &$responseHeaders = null) {
        $method = strtolower($method);
        $userpwd = $bearer = false;
        $header = array();
        if(!empty($options['headers'])) {
            $header = $options['headers'];
        }

        if(!empty($options['auth']['user']) && !empty($options['auth']['password'])) {
            $userpwd = $options['auth']['user'].':'.$options['auth']['password'];
        }
        if(!empty($options['auth']['bearer'])) {
            $authorization = "Authorization: Bearer ".$options['auth']['bearer'];
            $header[] = $authorization;
        }

        if(empty($options['successcode'])) $options['successcode'] = array(200);
        if(!is_array($options['successcode'])) {
            $options['successcode'] = array($options['successcode']);
        }
        if(!empty($_COOKIE['CONTENT_DEBUG'])) {
            $options['debug'] = true;
        }

        if (function_exists('curl_version')) {
            $curl = curl_init();
            curl_setopt($curl, 	CURLOPT_HEADER, 1);

            if($method == 'get' && !empty($params)) {
                $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
                $url .= '?'.$query;
            }

            curl_setopt($curl, 	CURLOPT_URL, $url);
            curl_setopt($curl, 	CURLOPT_RETURNTRANSFER, 1);

            if($method == 'auto' || $method == 'post') {
                curl_setopt($curl, CURLOPT_POST, 1);

                $newparams = array();
                self::http_build_query_for_curl($params, $newparams);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $newparams);
            } elseif($method == 'put') {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");

                $newparams = array();
                self::http_build_query_for_curl($params, $newparams);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $newparams);
            } elseif($method == 'delete') {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            }

            if(!empty($options['cainfo'])) {
                curl_setopt($curl, 	CURLOPT_CAINFO, $options['cainfo']);
            }
            if(!empty($options['debug'])) {
                curl_setopt($curl, 	CURLOPT_VERBOSE, 1);

                $verbose = fopen('php://temp', 'w+');
                curl_setopt($curl, CURLOPT_STDERR, $verbose);

            }

            if($userpwd !== false) {
                curl_setopt($curl,	CURLOPT_USERPWD, $userpwd);
            }

            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, 	CURLOPT_FOLLOWLOCATION, true);

            $content = curl_exec($curl);

            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $http_response_header = substr($content, 0, $header_size);
            $header = self::parseHeaders(explode("\r", $http_response_header));

            if($responseHeaders !== null) {
                $responseHeaders = $header;
            }

            $content = substr($content, $header_size);

            if(!empty($options['debug'])) {
                echo '<pre>';
                var_dump('URL: '.$url);
                var_dump('Parameters: ', $params);
                var_dump('Headers: ', $header);
                echo 'Response:'.PHP_EOL;
                var_dump($content);

                rewind($verbose);
                $verboseLog = stream_get_contents($verbose);

                echo "Verbose information:\n", htmlspecialchars($verboseLog), "</pre>\n";
                unlink($verbose);
            }
            $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if(!empty($responseCode) && !in_array($responseCode, $options['successcode'])) {
                throw new \Exception('Error Code '.$responseCode.' - '.$content, $responseCode);
            }

            curl_close($curl);

            if(!empty($header['content-type'])) {
                $parts = explode(';', $header['content-type']);

                if($parts[0] == 'application/json') {
                    $textcontent = $content;
                    try {
                        $content = self::json_decode($textcontent);
                    } catch (\Exception $exp) {
                        echo $textcontent;
                        exit();
                    }

                    if(empty($content) && strlen($textcontent) > 3) {
                        $content = $textcontent;
                    }
                }
            }

        }
        else if (file_get_contents(__FILE__) && ini_get('allow_url_fopen') && ($method == 'auto' || $method == 'get'))
        {
            if(count($params) > 0) {
                $query = http_build_query($params);
                if(strpos($url, '?') === false) {
                    $url .= '?'.$query;
                } else {
                    $url .= '&'.$query;
                }
            }
            if(!empty($userpwd)) {
                $header[] = "Authorization: Basic " . base64_encode($userpwd);
            }

            $context = stream_context_create(array(
                'http' => array(
                    'header'  => $header
                )
            ));

            $content = file_get_contents($url, false, $context);
            if(!empty($options['debug'])) {
                echo 'Response:'.PHP_EOL;
                var_dump($content);
            }

            $header = self::parseHeaders($http_response_header);

            if(!empty($header['content-type'])) {
                $parts = explode(';', $header['content-type']);
                if($parts[0] == 'application/json') {
                    $textcontent = $content;
                    $content = VtUtils::json_decode($textcontent);

                    if(empty($content) && strlen($textcontent) > 3) {
                        $content = $textcontent;
                    }
                }
            }
            if(!in_array($header['response_code'], $options['successcode'])) {
                throw new \Exception('Error Code '.$header['response_code'].' - '.$context, $header['response_code']);
            }
        }
        else
        {
            throw new \Exception('You have neither cUrl installed nor allow_url_fopen activated. Please setup one of those!');
        }

        return $content;
    }

    // copyright MangaII  http://php.net/manual/en/reserved.variables.httpresponseheader.php
    public static function parseHeaders( $headers )
    {
        $head = array();
        foreach( $headers as $k=>$v )
        {
            $t = explode( ':', $v, 2 );
            if( isset( $t[1] ) )
                $head[ strtolower(trim($t[0])) ] = trim( $t[1] );
            else
            {
                $head[] = $v;
                if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
                    $head['response_code'] = intval($out[1]);
            }
        }
        return $head;
    }

    /**
     * @param $filepath
     * @param bool $filename
     * @return array
     */
    public static function createAttachment($filepath, $filename = false) {
        $adb = \PearDatabase::getInstance();
        $current_user = \Users_Record_Model::getCurrentUserModel();

        $upload_file_path = decideFilePath();

        $next_id = $adb->getUniqueID("vtiger_crmentity");

        if(empty($filename)) {
            $filename = basename($filepath);
        }

        rename($filepath, $upload_file_path . $next_id . "_" . $filename);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $filetype = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        $sql1 = "insert into vtiger_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) values(?, ?, ?, ?, ?, ?, ?)";
        $params1 = array($next_id, $current_user->id, $current_user->id, "Documents Attachment",'Documents Attachment', date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));

        $adb->pquery($sql1, $params1);

        $sql2 = "insert into vtiger_attachments(attachmentsid, name, description, type, path) values(?, ?, ?, ?, ?)";
        $params2 = array($next_id, $filename, '', $filetype, $upload_file_path);
        $adb->pquery($sql2, $params2, true);

        return array(
            'id' => $next_id,
            'path' => $upload_file_path,
            'filename' => $next_id . "_" . $filename,
            'mime' => $filetype
        );
    }

    /**
     * @param $value
     * @return false|string
     */
    public static function json_encode($value) {
        $result = json_encode($value);

        if(empty($result) && !empty($value) > 4) {
            \Zend_Json::$useBuiltinEncoderDecoder = true;
            $result = \Zend_Json::encode($value);
        }

        if(empty($result) && !empty($value) > 4) {
            \Zend_Json::$useBuiltinEncoderDecoder = false;
            $result = \Zend_Json::encode($value);
        }

        return $result;
    }

    /**
     * @param $value
     * @return mixed
     */
    public static function json_decode($value) {
        $result = json_decode($value, true);

        if(empty($result) && strlen($value) > 4) {
            \Zend_Json::$useBuiltinEncoderDecoder = false;
            $result = \Zend_Json::decode($value);
        }

        if(empty($result) && strlen($value) > 4) {
            \Zend_Json::$useBuiltinEncoderDecoder = true;
            $result = \Zend_Json::decode($value);
        }

        if(empty($result) && strlen($value) > 4) {
            // Decode HTML Entities
            $value = html_entity_decode($value, ENT_QUOTES);

            \Zend_Json::$useBuiltinEncoderDecoder = true;
            $result = \Zend_Json::decode($value);
        }
        if(empty($result) && strlen($value) > 4) {
            \Zend_Json::$useBuiltinEncoderDecoder = false;
            $result = \Zend_Json::decode($value);
        }

        return $result;
    }

    /**
     * @param $url
     * @param $targetPath
     */
    public static function downloadRequiredPart($url, $targetPath) {
        set_time_limit(0);

        @mkdir(DASHBOARD_TMP_DIR);
        if(substr($url, -4) == '.tar') {
            $phar = new \PharData($url);
            $phar->extractTo($targetPath); // extract all files
        }
        if(substr($url, -4) == '.zip') {

            $extract = function($file, $targetPath) {
                include_once('vtlib/Vtiger/Unzip.php');
                $unzip = new \Vtiger_Unzip($file, true);

                $unzip->unzipAll( $targetPath );
            };
        }
        $file = DASHBOARD_TMP_DIR.'tmpfile.zip';

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FILE           => fopen($file, 'w+'),
            CURLOPT_TIMEOUT        => 50,

            CURLOPT_CONNECTTIMEOUT        => 0,
            CURLOPT_USERAGENT      => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        if(!file_exists($targetPath)) {
            @mkdir($targetPath, 0755, true);
        }
        if(file_exists($file) && filesize($file) > 0) {
            $extract($file, $targetPath);
        }

        unlink($file);
    }


    /**
     * @param $module_name
     * @param bool $references
     * @param string $refTemplate
     * @return array
     * @throws \Exception
     */
    public static function getFieldModelsWithBlockDataForModule($module_name, $references = false, $refTemplate = "([source]: ([module]) [destination])") {
        global $current_language, $adb, $app_strings;
        \Vtiger_Cache::$cacheEnable = false;

        $start = microtime(true);
        if(empty($refTemplate) && $references == true) {
            $refTemplate = "([source]: ([module]) [destination])";
        }

        $module = $module_name;
        $instance = \Vtiger_Module_Model::getInstance($module);
        $blocks = \Vtiger_Block_Model::getAllForModule($instance);
        ////echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
        if($module != "Events") {
            $langModule = $module;
        } else {
            $langModule = "Calendar";
        }
        $modLang = return_module_language($current_language, $langModule);
        //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
        $moduleFields = array();

        $addReferences = array();


        if(is_array($blocks)) {
            foreach($blocks as $block) {
                $fields = $block->getFields();
                //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
                if(empty($fields) || !is_array($fields)) {
                    continue;
                }

                $tmpBlock = array(
                    'id' => $block->id,
                    'label' => getTranslatedString($block->label, $langModule),
                    'fields' => array(),
                );

                foreach($fields as $field) {
                    $field->label = getTranslatedString($field->label, $langModule);
                    $field->type = new StdClass();
                    $field->type->name = self::getFieldTypeName($field->uitype, $field->typeofdata);

                    if($field->type->name == 'picklist') {
                        $language = \Vtiger_Language_Handler::getModuleStringsFromFile($current_language, $field->block->module->name);
                        if(empty($language)) {
                            $language = \Vtiger_Language_Handler::getModuleStringsFromFile('en_us', $field->block->module->name);
                        }

                        switch($field->name) {
                            case 'hdnTaxType':
                                $field->type->picklistValues = array(
                                    'group' => 'Group',
                                    'individual' => 'Individual',
                                );
                                break;
                            case 'email_flag':
                                $field->type->picklistValues = array(
                                    'SAVED' => 'SAVED',
                                    'SENT' => 'SENT',
                                    'MAILSCANNER' => 'MAILSCANNER',
                                );
                                break;
                            case 'currency_id':
                                $field->type->picklistValues = array();
                                $currencies = getAllCurrencies();
                                foreach($currencies as $currencies) {
                                    $field->type->picklistValues[$currencies['currency_id']] = $currencies['currencylabel'];
                                }

                                break;
                            default:
                                $field->type->picklistValues = getAllPickListValues($field->name, $language['languageStrings']);
                                break;
                        }

                    }
                    if(in_array($field->uitype, self::$referenceUitypes)) {
                        $modules = self::getModuleForReference($field->block->module->id, $field->name, $field->uitype);

                        $field->type->refersTo = $modules;
                    }

                    if($references !== false) {

                        switch ($field->uitype) {
                            case "51":
                                $addReferences[] = array($field, "Accounts");
                                break;
                            case "52":
                                $addReferences[] = array($field, "Users");
                                break;
                            case "53":
                                $addReferences[] = array($field, "Users");
                                break;
                            case "57":
                                $addReferences[] = array($field, "Contacts");
                                break;
                            case "58":
                                $addReferences[] = array($field,"Campaigns");
                                break;
                            case "59":
                                $addReferences[] = array($field,"Products");
                                break;
                            case "73":
                                $addReferences[] = array($field,"Accounts");
                                break;
                            case "75":
                                $addReferences[] = array($field,"Vendors");
                                break;
                            case "81":
                                $addReferences[] = array($field,"Vendors");
                                break;
                            case "76":
                                $addReferences[] = array($field,"Potentials");
                                break;
                            case "78":
                                $addReferences[] = array($field,"Quotes");
                                break;
                            case "80":
                                $addReferences[] = array($field,"SalesOrder");
                                break;
                            case "68":
                                $addReferences[] = array($field,"Accounts");
                                $addReferences[] = array($field,"Contacts");
                                break;
                            case "10": # Possibly multiple relations
                                $result = $adb->pquery('SELECT relmodule FROM `vtiger_fieldmodulerel` WHERE fieldid = ?', array($field->id));
                                while ($data = $adb->fetch_array($result)) {
                                    $addReferences[] = array($field,$data["relmodule"]);
                                }
                                break;
                        }
                    }

                    $tmpBlock['fields'][] = $field;
                }

                $moduleFields[$block->label] = $tmpBlock;
            }

            $crmid = new StdClass();
            $crmid->name = 'crmid';
            $crmid->label = 'ID';
            $crmid->type = 'string';
            reset($moduleFields);
            $first_key = key($moduleFields);

            array_unshift($moduleFields[$first_key]['fields'], $crmid);

            //$moduleFields[$first_key] = array_merge(array($crmid), $moduleFields[$first_key]['fields']);

        }
        //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
        $rewriteFields = array(
            "assigned_user_id" => "smownerid"
        );

        /*if($references !== false) {
            $field = \Vtiger_Field_Model::getAllForModule()
            $field->name = "current_user";
            $field->label = getTranslatedString("LBL_CURRENT_USER", "CloudFile");
            $addReferences[] = array($field, "Users");
        }*/
        if(is_array($addReferences)) {

            foreach($addReferences as $refField) {
                //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
                $fields = self::getFieldsForModule($refField[1]);

                foreach($fields as $field) {
                    $field->label = "(".(isset($app_strings[$refField[1]])?$app_strings[$refField[1]]:$refField[1]).") ".$field->label;

                    if(!empty($rewriteFields[$refField[0]->name])) {
                        $refField[0]->name = $rewriteFields[$refField[0]->name];
                    }
                    $name = str_replace(array("[source]", "[module]", "[destination]"), array($refField[0]->name, $refField[1], $field->name), $refTemplate);
                    $field->name = $name;

                    $moduleFields["References (".$refField[0]->label.")"][] = $field;
                }
            }
        }

        \Vtiger_Cache::$cacheEnable = true;
        return $moduleFields;
    }


    /**
     * @param $module_name
     * @param bool $references
     * @param string $refTemplate
     * @return array
     * @throws \Exception
     */
    public static function getFieldModelsWithBlocksForModule($module_name, $references = false, $refTemplate = "([source]: ([module]) [destination])") {
        global $current_language, $adb, $app_strings;
        \Vtiger_Cache::$cacheEnable = false;

        $start = microtime(true);
        if(empty($refTemplate) && $references == true) {
            $refTemplate = "([source]: ([module]) [destination])";
        }

        $module = $module_name;
        $instance = \Vtiger_Module_Model::getInstance($module);
        $blocks = \Vtiger_Block_Model::getAllForModule($instance);
        ////echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
        if($module != "Events") {
            $langModule = $module;
        } else {
            $langModule = "Calendar";
        }
        $modLang = return_module_language($current_language, $langModule);
        //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
        $moduleFields = array();

        $addReferences = array();


        if(is_array($blocks)) {
            foreach($blocks as $block) {
                $fields = \Vtiger_Field_Model::getAllForBlock($block, $instance);
                //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
                if(empty($fields) || !is_array($fields)) {
                    continue;
                }

                foreach($fields as $field) {
                    $field->label = getTranslatedString($field->label, $langModule);
                    $field->type = new StdClass();
                    $field->type->name = self::getFieldTypeName($field->uitype, $field->typeofdata);

                    if($field->type->name == 'picklist') {
                        $language = \Vtiger_Language_Handler::getModuleStringsFromFile($current_language, $field->block->module->name);
                        if(empty($language)) {
                            $language = \Vtiger_Language_Handler::getModuleStringsFromFile('en_us', $field->block->module->name);
                        }

                        switch($field->name) {
                            case 'hdnTaxType':
                                $field->type->picklistValues = array(
                                    'group' => 'Group',
                                    'individual' => 'Individual',
                                );
                                break;
                            case 'email_flag':
                                $field->type->picklistValues = array(
                                    'SAVED' => 'SAVED',
                                    'SENT' => 'SENT',
                                    'MAILSCANNER' => 'MAILSCANNER',
                                );
                                break;
                            case 'currency_id':
                                $field->type->picklistValues = array();
                                $currencies = getAllCurrencies();
                                foreach($currencies as $currencies) {
                                    $field->type->picklistValues[$currencies['currency_id']] = $currencies['currencylabel'];
                                }

                                break;
                            default:
                                $field->type->picklistValues = getAllPickListValues($field->name, $language['languageStrings']);
                                break;
                        }

                    }
                    if(in_array($field->uitype, self::$referenceUitypes)) {
                        $modules = self::getModuleForReference($field->block->module->id, $field->name, $field->uitype);

                        $field->type->refersTo = $modules;
                    }

                    if($references !== false) {

                        switch ($field->uitype) {
                            case "51":
                                $addReferences[] = array($field, "Accounts");
                                break;
                            case "52":
                                $addReferences[] = array($field, "Users");
                                break;
                            case "53":
                                $addReferences[] = array($field, "Users");
                                break;
                            case "57":
                                $addReferences[] = array($field, "Contacts");
                                break;
                            case "58":
                                $addReferences[] = array($field,"Campaigns");
                                break;
                            case "59":
                                $addReferences[] = array($field,"Products");
                                break;
                            case "73":
                                $addReferences[] = array($field,"Accounts");
                                break;
                            case "75":
                                $addReferences[] = array($field,"Vendors");
                                break;
                            case "81":
                                $addReferences[] = array($field,"Vendors");
                                break;
                            case "76":
                                $addReferences[] = array($field,"Potentials");
                                break;
                            case "78":
                                $addReferences[] = array($field,"Quotes");
                                break;
                            case "80":
                                $addReferences[] = array($field,"SalesOrder");
                                break;
                            case "68":
                                $addReferences[] = array($field,"Accounts");
                                $addReferences[] = array($field,"Contacts");
                                break;
                            case "10": # Possibly multiple relations
                                $result = $adb->pquery('SELECT relmodule FROM `vtiger_fieldmodulerel` WHERE fieldid = ?', array($field->id));
                                while ($data = $adb->fetch_array($result)) {
                                    $addReferences[] = array($field,$data["relmodule"]);
                                }
                                break;
                        }
                    }

                    $moduleFields[getTranslatedString($block->label, $langModule)][] = $field;
                }
            }
            $crmid = new StdClass();
            $crmid->name = 'crmid';
            $crmid->label = 'ID';
            $crmid->type = 'string';
            reset($moduleFields);
            $first_key = key($moduleFields);
            $moduleFields[$first_key] = array_merge(array($crmid), $moduleFields[$first_key]);

        }
        //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
        $rewriteFields = array(
            "assigned_user_id" => "smownerid"
        );

        /*if($references !== false) {
            $field = \Vtiger_Field_Model::getAllForModule()
            $field->name = "current_user";
            $field->label = getTranslatedString("LBL_CURRENT_USER", "CloudFile");
            $addReferences[] = array($field, "Users");
        }*/
        if(is_array($addReferences)) {

            foreach($addReferences as $refField) {
                //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
                $fields = self::getFieldsForModule($refField[1]);

                foreach($fields as $field) {
                    $field->label = "(".(isset($app_strings[$refField[1]])?$app_strings[$refField[1]]:$refField[1]).") ".$field->label;

                    if(!empty($rewriteFields[$refField[0]->name])) {
                        $refField[0]->name = $rewriteFields[$refField[0]->name];
                    }
                    $name = str_replace(array("[source]", "[module]", "[destination]"), array($refField[0]->name, $refField[1], $field->name), $refTemplate);
                    $field->name = $name;

                    $moduleFields["References (".$refField[0]->label.")"][] = $field;
                }
            }
        }

        \Vtiger_Cache::$cacheEnable = true;
        return $moduleFields;
    }

    /**
     * @var array
     */
    private static $_FieldModelCache = array();

    /**
     * @param $module_name
     * @param bool $uitype
     * @return mixed
     */
    public static function getFieldModelsForModule($module_name, $uitype = false) {
        global $current_language;

        if($uitype !== false && !is_array($uitype)) {
            $uitype = array($uitype);
        }

        $cacheKey = md5(serialize($uitype).$module_name);

        if(isset(self::$_FieldModelCache[$cacheKey])) {
            return unserialize(serialize(self::$_FieldModelCache[$cacheKey]));
        }

        $adb = \PearDatabase::getInstance();
        $query = "SELECT * FROM vtiger_field WHERE tabid = ? ORDER BY sequence";
        $queryParams = Array(getTabid($module_name));

        $result = $adb->pquery($query, $queryParams);

        /**
         * @var [\Vtiger_Field_Model] $fields
         */
        $fields = array();

        while($valuemap = $adb->fetchByAssoc($result)) {
            /**
             * @var \Vtiger_Field_Model $tmp
             */
            $tmp = \Vtiger_Field_Model::getInstanceFromFieldId($valuemap['fieldid'], getTabid($module_name));

            /*
            $tmp = new \stdClass();
            $tmp->id = $valuemap['fieldid'];
            $tmp->name = $valuemap['fieldname'];
            $tmp->label= $valuemap['fieldlabel'];
            $tmp->column = $valuemap['columnname'];
            $tmp->table  = $valuemap['tablename'];
            $tmp->uitype = $valuemap['uitype'];
            $tmp->typeofdata = $valuemap['typeofdata'];
            $tmp->helpinfo = $valuemap['helpinfo'];
            $tmp->masseditable = $valuemap['masseditable'];
            $tmp->displaytype   = $valuemap['displaytype'];
            $tmp->generatedtype = $valuemap['generatedtype'];
            $tmp->readonly      = $valuemap['readonly'];
            $tmp->presence      = $valuemap['presence'];
            $tmp->defaultvalue  = $valuemap['defaultvalue'];
            $tmp->quickcreate = $valuemap['quickcreate'];
            $tmp->sequence = $valuemap['sequence'];
            $tmp->summaryfield = $valuemap['summaryfield'];
*/
            $fields[] = $tmp[0];
        }

        $module = $module_name;
        if($module != "Events") {
            $modLang = return_module_language($current_language, $module);
        }
        $moduleFields = array();

        /*
                // Fields in this module
                include_once("vtlib/Vtiger/Module.php");

                   #$alle = glob(dirname(__FILE__).'/functions/*.inc.php');
                   #foreach($alle as $datei) { include $datei; }


                   $instance = Vtiger_Module::getInstance($module);
                   //$blocks = Vtiger_Block::getAllForModule($instance);



                $fields = Vtiger_Field::getAllForModule($instance);
        */
        //$blocks = Vtiger_Block::getAllForModule($instance);
        if(is_array($fields)) {
            foreach($fields as $field) {

                //$fieldlabel = $field->get('fieldlabel');
                //$field->set('fieldlabel', isset($modLang[$fieldlabel])?$modLang[$fieldlabel]:$fieldlabel );
                /*
                                $field->type = new StdClass();
                                $field->type->name = self::getFieldTypeName($field->uitype, $field->typeofdata);
                */
                /*if($field->type->name == 'picklist') {
                    $language = \Vtiger_Language_Handler::getModuleStringsFromFile($current_language, $field->block->module->name);
                    if(empty($language)) {
                        $language = \Vtiger_Language_Handler::getModuleStringsFromFile('en_us', $field->block->module->name);
                    }

                    switch($field->name) {
                        case 'hdnTaxType':
                            $field->type->picklistValues = array(
                                'group' => 'Group',
                                'individual' => 'Individual',
                            );
                            break;
                        case 'email_flag':
                            $field->type->picklistValues = array(
                                'SAVED' => 'SAVED',
                                'SENT' => 'SENT',
                                'MAILSCANNER' => 'MAILSCANNER',
                            );
                            break;
                        case 'currency_id':
                            $field->type->picklistValues = array();
                            $currencies = getAllCurrencies();
                            foreach($currencies as $currencies) {
                                $field->type->picklistValues[$currencies['currency_id']] = $currencies['currencylabel'];
                            }

                        break;
                        default:
                            $field->type->picklistValues = getAllPickListValues($field->name, $language['languageStrings']);
                        break;
                    }

                }
*/
                if($uitype !== false) {
                    if(in_array($field->uitype, $uitype)) {
                        $moduleFields[] = $field;
                    }
                } else {
                    $moduleFields[] = $field;
                }
            }
            /*
                        $crmid = new StdClass();
                        $crmid->name = 'crmid';
                        $crmid->label = 'ID';
                        $crmid->type = 'string';
                        $moduleFields[] = $crmid;
            */
        }

        self::$_FieldModelCache[$cacheKey] = $moduleFields;
//7f18c166060f17d0ce582a4359ad1cbc
        return unserialize(serialize($moduleFields));
    }

    /**
     * @param $string
     * @return mixed
     */
    public static function parseJSON($string) {
        $result = json_decode($string);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $result;
        }

        return $string;
    }

    /**
     * @param $moduleName
     * @param $fieldName
     * @param $fieldLabel
     * @param $type
     */
    public static function addModuleField($moduleName, $fieldName, $fieldLabel, $type) {
        $adb = \PearDatabase::getInstance();

        $sql = 'SELECT * FROM vtiger_field WHERE tabid = ? AND fieldname = ?';
        $result = $adb->pquery($sql, array(getTabid($moduleName), $fieldName));
        if($adb->num_rows($result) > 0) {
            return;
        }

        include_once('vtlib/Vtiger/Menu.php');
        include_once('vtlib/Vtiger/Module.php');

        // Welches Modul soll bearbeitet werden?
        $targetModuleName = $moduleName;
        $type = strtolower($type);

        $uitype = 1;
        $typeofdata = 'NN~O~12,4';
        $colType = 'VARCHAR(255)';

        if($type == 'number') {
            $uitype = 7;
            $typeofdata = 'NN~O~12,4';
            $colType = 'DECIMAL(12,4)';
        }

        if(empty($uitype)) {
            echo $type.' not known<br/>';
            return;
        }
        // Welches Label soll das Feld bekommen?
        //$fieldLabel = 'Preisliste';

        // -------- ab hier nichts mehr anpassen !!!!
        $module = Vtiger_Module::getInstance($targetModuleName);

        $blocks = Vtiger_Block::getAllForModule($module);
        $block = $blocks[0];

        $field1 = new Vtiger_Field();
        $field1->name = $fieldName;
        $field1->label= $fieldLabel;
        $field1->table = $module->basetable;
        $field1->column = $fieldName;
        $field1->columntype = $colType;
        $field1->uitype = $uitype;

        $field1->typeofdata = $typeofdata;
        $block->addField($field1);

    }

    /**
     * @param $moduleName
     * @param $fieldName
     * @param $fieldLabel
     * @param $targetModuleNameArray
     * @param null $blockName
     */
    public static function addModuleReferenceField($moduleName, $fieldName, $fieldLabel, $targetModuleNameArray, $blockName = null) {
        $adb = \PearDatabase::getInstance();

        $sql = 'SELECT * FROM vtiger_field WHERE tabid = ? AND fieldname = ?';
        $result = $adb->pquery($sql, array(getTabid($moduleName), $fieldName));
        if($adb->num_rows($result) > 0) {
            return;
        }

        // Welches Modul soll bearbeitet werden?
        $targetModuleName = $moduleName;

        // Welche Module sollen ausgewählt werden können?
        $relatedModules = $targetModuleNameArray;

        // -------- ab hier nichts mehr anpassen !!!!
        $module = Vtiger_Module::getInstance($targetModuleName);

        if($blockName === null) {
            $blocks = Vtiger_Block::getAllForModule($module);
            $block = $blocks[0];
        } else {
            $block = Vtiger_Block::getInstance ($blockName, $module);
        }

        $field1 = new Vtiger_Field();
        $field1->name = $fieldName;
        $field1->label= $fieldLabel;
        $field1->table = $module->basetable;
        $field1->column = $fieldName;
        $field1->columntype = 'VARCHAR(100)';
        $field1->uitype = 10;
        $field1->typeofdata = 'V~O';
        $block->addField($field1);
        $field1->setRelatedModules($relatedModules);

    }

    public static function GetAdditionalPath($additionalKey) {
        return vglobal('root_directory') . 'modules' . DS . __NAMESPACE__ . DS . 'extends' . DS . 'additional' . DS . $additionalKey . DS;
    }

    public static function convertUTC2UserTimezone($dateTime) {
        global $current_user, $default_timezone;
        if(empty($user)) {
            $user = $current_user;
        }
        $timeZone = $user->time_zone ? $user->time_zone : $default_timezone;
        return \DateTimeField::convertTimeZone($dateTime, 'UTC', $timeZone);
    }

    public function sanitizeDate($value, $user) {
        global $current_user;
        if(empty($user)) {
            $user = $current_user;
        }

        if($user->date_format == 'mm-dd-yyyy') {
            list($date, $time) = explode(' ', $value);
            if(!empty($date)) {
                list($m, $d, $y) = explode('-', $date);
                if(strlen($m) < 3) {
                    $time = ' '.$time;
                    $value = "$y-$m-$d".rtrim($time);
                }
            }
        }
        return $value;
    }

    public static function convertUserTimezone2UTC($dateTime)
    {
        global $current_user, $default_timezone;
        if (empty($user)) {
            $user = $current_user;
        }
        $timeZone = $user->time_zone ? $user->time_zone : $default_timezone;
        $value = self::sanitizeDate($dateTime, $user);
        return \DateTimeField::convertTimeZone($value, $timeZone, 'UTC');
    }

}

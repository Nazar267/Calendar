<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 01.03.14 17:57
 * You must not use this file without permission.
 */
namespace ComplexCondition;

class ComplexeCondition
{
    private static $CONDITIONSCOPE = 'ComplexCondition';
    private static $VERSION = '2.3';

    protected $field = false;
    protected $parameter = false;

    public function __construct($field, $extraParameter = array(), $task = null) {
        $this->field = $field;
        $this->parameter = $extraParameter;
        $this->_task = $task;
    }



    public function getCondition($raw_condition) {
        if(!empty($raw_condition)) {
            $raw_condition = $this->createChilds($raw_condition);
        }

        return $raw_condition;
    }

    public function getHTML($condition, $moduleName) {
        $text = $this->_generateTextGroup($condition, $moduleName);

        return $this->_parseText($text);
    }

    public function _parseText($text) {
        $result = array();
        for($i = 0; $i < count($text); $i++) {
            if(is_array($text[$i])) {
                $tmp = '<div style="border-left:2px solid #777;padding-left:5px;margin-left:5px;">'.$this->_parseText($text[$i]).'</div>';
                $result[] = $tmp;
            } else {
                $result[] = $text[$i];
            }
        }

        $result = implode("\n", $result);
        if(substr($result, -2) == 'OR') {
            $result = substr($result, 0, -2);
        }
        if(substr($result, -3) == 'AND') {
            $result = substr($result, 0, -3);
        }
        return $result;
    }

    public function _generateTextGroup($condition, $moduleName) {
        $text = array();

        foreach($condition as $check) {
            $tmp = '';
            if($check["type"] == "group") {
                $tmp = $this->_generateTextGroup($check["childs"], $moduleName);
            } elseif($check["type"] == "field") {
                $tmp = $this->_generateTextField($check, $moduleName);
            }
            if ($check["join"] == "and") {
                $join = ' AND';
            } else {
                $join = ' OR';
            }

            if(is_string($tmp)) {
                $tmp .= $join;
            }

            $text[] = $tmp;

            if(is_array($tmp)) {
                $tmp[] = $join;
            }
        }

        return $text;
    }

    /**
     * @param array $check
     */
    public function _generateTextField($check, $moduleName) {
        $operation = explode('/', $check["operation"]);
        $conditionOperators = ConditionPlugin::getItem($operation[0]);

        return $conditionOperators->generateText($moduleName, $operation[1], $check);
    }


    public function InitViewer($transferData) {
        //$start = microtime(true);
        list($data, $viewer) = $transferData;
        $field = $this->field;

        if(isset($this->parameter['enableHasChanged'])) {
            $enableHasChanged = !empty($this->parameter['enableHasChanged']);
        } else {
            $enableHasChanged = true;
        }

        if(empty($this->parameter['mode'])) {
            $this->parameter['mode'] = 'field';
        }

        if(isset($this->parameter['fromModule'])) {
            $fromModule = $this->parameter['fromModule'];
        } else {
            $fromModule = '';
        }

        if(isset($this->parameter['toModule'])) {
            $toModule = $this->parameter['toModule'];
        } else {
            $toModule = $fromModule;
        }
        $availCurrency = getAllCurrencies();
        $availUser = array('user' => array(), 'group' => array());

        $adb = \PearDatabase::getInstance();
        $sql = "SELECT id FROM vtiger_ws_entity WHERE name = 'Users'";
        $result = $adb->query($sql);
        $wsTabId = $adb->query_result($result, 0, "id");

        $sql = "SELECT id,user_name,first_name,last_name FROM vtiger_users WHERE status = 'Active'";
        $result = $adb->query($sql);
        while($user = $adb->fetchByAssoc($result)) {
            $user["id"] = $user["id"];
            $availUser["user"][$user["id"]] = $user["user_name"]." (".$user["last_name"].", ".$user["first_name"].")";
        }


        $sql = "SELECT id FROM vtiger_ws_entity WHERE name = 'Groups'";
        $result = $adb->query($sql);
        $wsTabId = $adb->query_result($result, 0, "id");

        $sql = "SELECT * FROM vtiger_groups ORDER BY groupname";
        $result = $adb->query($sql);
        while($group = $adb->fetchByAssoc($result)) {
            $group["groupid"] = $group["groupid"];
            $availUser["group"][$group["groupid"]] = $group["groupname"];
        }

        $containerName = 'conditional_container';
        if(!empty($this->parameter['container'])) {
            $containerName = $this->parameter['container'];
        }
        $conditionals = $data[$field];

        //echo 'C'.__LINE__.': '.(microtime(true) - $start).'<br/>';
        if(isset($this->parameter['references'])) {
            $references = $this->parameter['references'] == true ? true : false;
        } else {
            $references = true;
        }

        if(class_exists('\\'.self::$CONDITIONSCOPE.'\\VtUtils')) {
            $moduleFields = VtUtils::getFieldsWithBlocksForModule($toModule, $references);
        } else {
            $moduleFields = self::getFieldsWithBlocksForModule($toModule, $references);
        }

        $viewer->assign("conditionalContent", '<div id="'.$containerName.'"><div style="margin:50px auto;text-align:center;font-weight:bold;color:#aaa;font-size:18px;">'.getTranslatedString('LOADING_INDICATOR', self::$CONDITIONSCOPE).'<br><br><img src="modules/'.self::$CONDITIONSCOPE.'/views/resources/img/loader.gif" alt="Loading ..."></div></div>');

        if(empty($this->parameter['operators'])) {
            $conditionOperators = ConditionPlugin::getAvailableOperators($toModule, $this->parameter['mode']);
        } else {
            $conditionOperators = $this->parameter['operators'];
        }

        $disableConditionMode = !empty($this->parameter['disableConditionMode']);
        $enableTemplateFields = empty($this->parameter['disableTemplateFields']);

        $script = 'var condition_module = "'.$toModule.'";';
        $script .= 'var condition_fromModule = "'.$fromModule.'";';

        $script .= 'jQuery(function() {
                window.setTimeout(function() {
                    MOD = {
                        \'LBL_STATIC_VALUE\' : \''.vtranslate('LBL_STATIC_VALUE',self::$CONDITIONSCOPE).'\',
                        \'LBL_FUNCTION_VALUE\' : \''.vtranslate('LBL_FUNCTION_VALUE',self::$CONDITIONSCOPE).'\',
                        \'LBL_EMPTY_VALUE\' : \''.vtranslate('LBL_EMPTY_VALUE',self::$CONDITIONSCOPE).'\',
                        \'LBL_VALUES\' : \''.vtranslate('LBL_VALUES',self::$CONDITIONSCOPE).'\',
                        \'LBL_ADD_GROUP\' : \''.vtranslate('LBL_ADD_GROUP',self::$CONDITIONSCOPE).'\',
                        \'LBL_ADD_CONDITION\' : \''.vtranslate('LBL_ADD_CONDITION',self::$CONDITIONSCOPE).'\',
                        \'LBL_REMOVE_GROUP\' : \''.vtranslate('LBL_REMOVE_GROUP',self::$CONDITIONSCOPE).'\',
                        \'LBL_NOT\' : \''.vtranslate('LBL_NOT',self::$CONDITIONSCOPE).'\',
                        \'LBL_AND\' : \''.vtranslate('LBL_AND',self::$CONDITIONSCOPE).'\',
                        \'LBL_OR\' : \''.vtranslate('LBL_OR',self::$CONDITIONSCOPE).'\',
                        \'LBL_COND_EQUAL\' : \''.vtranslate('LBL_COND_EQUAL',self::$CONDITIONSCOPE).'\',
                        \'LBL_COND_IS_CHECKED\' : \''.vtranslate('LBL_COND_IS_CHECKED',self::$CONDITIONSCOPE).'\',
                        \'LBL_COND_CONTAINS\' : \''.vtranslate('LBL_COND_CONTAINS',self::$CONDITIONSCOPE).'\',
                        \'LBL_COND_BIGGER\' : \''.vtranslate('LBL_COND_BIGGER',self::$CONDITIONSCOPE).'\',
                        \'LBL_COND_DATE_EMPTY\' : \''.vtranslate('LBL_COND_DATE_EMPTY',self::$CONDITIONSCOPE).'\',
                        \'LBL_COND_LOWER\' : \''.vtranslate('LBL_COND_LOWER',self::$CONDITIONSCOPE).'\',
                        \'LBL_COND_STARTS_WITH\' : \''.vtranslate('LBL_COND_STARTS_WITH',self::$CONDITIONSCOPE).'\',
                        \'LBL_COND_ENDS_WITH\' : \''.vtranslate('LBL_COND_ENDS_WITH',self::$CONDITIONSCOPE).'\',
                        \'LBL_COND_IS_EMPTY\' : \''.vtranslate('LBL_COND_IS_EMPTY',self::$CONDITIONSCOPE).'\',
                        \'LBL_CANCEL\' : \''.vtranslate('LBL_CANCEL',self::$CONDITIONSCOPE).'\',
                        \'LBL_SAVE\': \''.vtranslate('LBL_SAVE',self::$CONDITIONSCOPE).'\'
                    };

                    var objCondition = new ComplexeCondition("#'.$containerName.'");
                    objCondition.setEnabledTemplateFields(' . (!empty($enableTemplateFields)?"true":"false") . ');
                    objCondition.setMainCheckModule("' . $toModule . '");
                    objCondition.setMainSourceModule("' . $fromModule . '");
                    '.($disableConditionMode?'objCondition.disableConditionMode();':'').'
                    objCondition.setImagePath("modules/'.self::$CONDITIONSCOPE.'/views/resources/img/");
                    objCondition.setConditionOperators('.json_encode($conditionOperators).');
                    objCondition.setModuleFields('.json_encode($moduleFields).');
                    objCondition.setAvailableCurrencies('.json_encode($availCurrency).');
                    objCondition.setAvailableUser('.json_encode($availUser).');
                    objCondition.setCondition('.json_encode((empty($conditionals) || $conditionals == -1 ? array() : $conditionals)).');

                    objCondition.init();
                }, 1000);
            });
        ';

        $viewer->assign('javascript', $script);

        return $transferData;
    }

    public static function getFieldsWithBlocksForModule($module_name, $references = false, $refTemplate = "([source]: ([module]) [destination])") {
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


        if(is_array($blocks)) {
            foreach($blocks as $block) {
                $fields = Vtiger_Field::getAllForBlock($block, $instance);
                //echo 'C'.__LINE__.': '.round(microtime(true) - $start, 2).'<br/>';
                if(empty($fields) || !is_array($fields)) {
                    continue;
                }

                foreach($fields as $field) {
                    $field->label = getTranslatedString($field->label, $langModule);
                    $field->type = new StdClass();
                    $field->type->name = self::getFieldTypeName($field->uitype);

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

        if($references !== false) {
            $field = new StdClass();
            $field->name = "current_user";
            $field->label = getTranslatedString("LBL_CURRENT_USER", "CloudFile");
            $addReferences[] = array($field, "Users");
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

    private function createChilds($data) {
        $returns = array();

        foreach($data as $key => $value) {
            $tmp = array();
            if(substr($key, 0, 1) == "g") {
                $tmp["type"] = "group";
                $tmp["childs"] = self::createChilds($value);
            } else {
                if(empty($value["field"])) {
                    continue;
                }
                $tmp["type"] = "field";
                $tmp["field"] = $value["field"];
                $tmp["operation"] = $value["operation"];
                $tmp["not"] = $value["not"];
                $tmp["rawvalue"] = $value["rawvalue"];
                $tmp["mode"] = $value["mode"];
            }

            $tmp["join"] = $_POST["join"][$key];

            $returns[] = $tmp;
        }

        return $returns;
    }

}

?>
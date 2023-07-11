<?php

require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "autoload_wf.php");


class RedooCalendar_ComplexeCondition_Action extends Vtiger_Action_Controller {
    function checkPermission(Vtiger_Request $request) {
        return;
    }

    private $ScopeName = 'ComplexCondition';

    function __construct() {
        if(empty($this->ScopeName)) {
            $this->ScopeName = basename(dirname(dirname(__FILE__)));
        }

        parent::__construct();
        $this->exposeMethod('init');
        $this->exposeMethod('loadOperators');

        // OLD
        $this->exposeMethod('loadPicklistValues');
        $this->exposeMethod('ConditionStore');
    }

    private function json_encode($value) {
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

    private function getFullClassName($className) {
        return '\\'.$this->ScopeName . '\\' . $className;
    }

    public function init(\Vtiger_Request $request) {
        $moduleName = $request->get('mainModule');
        $conditionMode = $request->get('conditionMode');

        $required = $request->get('required');
        $return = array();

        foreach($required as $require) {

            switch($require) {
                case 'operators':

                    $className = $this->getFullClassName('ConditionPlugin');
                    $return['operators'] = $className::getAvailableOperators($moduleName, $conditionMode);

                    break;
                case 'fields':

                    $className = $this->getFullClassName('VtUtils');
                    $return['fields'] = $className::getFieldsWithBlocksForModule($moduleName, $request->get('referenceFields') == '1');

                    break;
                case 'users':

                    $return['users'] = getAllPickListValues('assigned_user_id');
                    $return['users']['$current_user_id'] = 'Variable: Current User';

                    break;
            }
        }

        echo $this->json_encode($return);
    }

    public function loadOperators(\Vtiger_Request $request) {
        $moduleName = $request->get('mainModule');
        $conditionMode = $request->get('conditionMode');

        $className = $this->getFullClassName('ConditionPlugin');
        $operators = $className::getAvailableOperators($moduleName, $conditionMode);
    }

    public function loadPicklistValues(\Vtiger_Request $request) {
        $moduleModel = Vtiger_Module_Model::getInstance(basename((dirname(dirname(__FILE__)))));

        $qualifiedModuleName = $request->getModule(false);

        $moduleName = $request->getModule(true);

        $fieldName = $request->get('fieldname');

        if($fieldName == 'assigned_user_id') {
            $picklistValues = getAllPickListValues($request->get('fieldname'));
            $picklistValues['$current_user_id'] = 'Variable: Current User';
        } else if($fieldName == 'currency_id') {
            $picklistValues = array();
            $currencies = getAllCurrencies();

            foreach ($currencies as $currencies) {
                $picklistValues[$currencies['currency_id']] = $currencies['currencylabel'];
            }

        } else {

            $picklistValues = getAllPickListValues($request->get('fieldname'));

        }

        echo $this->json_encode($picklistValues);

    }
    public function loadModuleFields() {

    }

    function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        if (!empty($mode)) {
            echo $this->invokeExposedMethod($mode, $request);
            return;
        }
    }

    public function ConditionStore(Vtiger_Request $request)
    {
        require_once(__DIR__.'/../lib/ComponentExample/ComplexeCondition.php');
        // Make sure \ComponentExample\ComplexeCondition is included/available

        $task = $request->get('task');

        if(!empty($task['condition'])) {
            $preset = new \ComponentExample\ComplexeCondition('condition', null, array());

            $condition = $preset->getCondition($task['condition']);
            $text = $preset->getHTML($condition, $task['module']);
        } else {
            $condition = '';
            $text = '';
        }

        echo json_encode(array('condition' => base64_encode(json_encode(array('condition' => $condition, 'module' => $task['module']))), 'html' => nl2br($text)));
    }
}


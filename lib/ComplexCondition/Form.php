<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 16:52
 */

namespace ComplexCondition;

use ComplexCondition\Form\Field;
use ComplexCondition\Form\Tab;

class Form
{
    protected $Tabs = array();
    protected $VariableScope = 'form';

    protected $Functions = array();
    protected $GetterFunctions = array();
    protected $SetterFunctions = array();
    protected $OnInitFunctions = array();

    private $Width = 600;

    protected $currentValue = array();

    /**
     * @var Validator[]
     */
    protected $validators = array();

    const LABEL_SUBMIT = 'submit-general';
    const LABEL_SUBMIT_CREATE = 'submit-create';
    const LABEL_SUBMIT_EDIT = 'submit-edit';
    const LABEL_FORM_TITLE = 'form-title';

    protected $label = array();

    /**
     * @var Field[]
     */
    protected $fields = array();

    public function __construct() {

    }

    public function setLabel($label) {
        $this->label = $label;
    }
    public function getLabel() {
        return $this->label;
    }

    public function registerField(Field $field) {
        $this->fields[] = &$field;
    }
    public function getFields() {
        return $this->fields;
    }

    public function addTab($headline) {
        $tmp = new Tab($this);
        $tmp->setTabLabel($headline);

        $this->Tabs[] = $tmp;

        return $tmp;
    }

    public function setWidth($widthPx) {
        $this->Width = intval($widthPx);
    }
    public function getWidth() {
        return $this->Width;
    }
    public function getTabs() {
        return $this->Tabs;
    }
    public function setVariableScope($variableScope) {
        $this->VariableScope = $variableScope;
    }
    public function getVariableScope() {
        return $this->VariableScope;
    }

    public function getHTML() {
        $viewer = \Vtiger_Viewer::getInstance();

        $viewer->assign('TABS', $this->getTabs());
        $viewer->assign('FORMMODULE', __NAMESPACE__);
        
        return $viewer->view('Form/FormContainer.tpl', __NAMESPACE__, true);
    }

    public function getJS() {
        if(empty($this->Functions)) $this->getHTML();

        $return = '';
        foreach($this->Functions as $hash => $function) {
            $return .= $function;
        }

        foreach($this->OnInitFunctions as $fieldname => $function) {
            $return .= 'FORMGenerator.registerInit("'.$fieldname.'", '.$function.');';
        }
        foreach($this->SetterFunctions as $fieldname => $function) {
            $return .= 'FORMGenerator.registerSetter("'.$fieldname.'", '.$function.');';
        }
        foreach($this->GetterFunctions as $fieldname => $function) {
            $return .= 'FORMGenerator.registerGetter("'.$fieldname.'", '.$function.');';
        }

        return $return;
    }

    public function setValues($values) {
        $this->currentValue = $values;

        foreach($this->fields as $field) {
            $fieldName = $field->getName();

            if(strpos($fieldName, '__detailfield__') === 0) {
                $origName = $fieldName;
                $fieldName = substr($fieldName, 15);

                if(isset($this->currentValue[$fieldName])) {
                    $this->currentValue[$origName] = $this->currentValue[$fieldName];
                }
            }

            if(isset($values[$fieldName])) {
                $field->setValue($values[$fieldName]);
            }
        }
    }

    public function registerSetter($fieldname, $function) {
        $hash = 'setter_'.sha1($function);

        $this->Functions[$hash] = 'function '.$hash.'(INPUT, VALUE) { '.$function.' }';
        $this->SetterFunctions[$fieldname] = $hash;
    }

    public function registerGetter($fieldname, $function) {
        $hash = 'getter_'.sha1($function);

        $this->Functions[$hash] = 'function '.$hash.'(INPUT, VALUE) { '.$function.' }';
        $this->GetterFunctions[$fieldname] = $hash;
    }

    public function registerValidators($fieldname, $validators) {
        $this->validators[$fieldname] = $validators;
    }

    public function registerOnInit($fieldname, $function) {
        $hash = 'oninit_'.sha1($function);

        $this->Functions[$hash] = 'function '.$hash.'(INPUT) { '.$function.' }';
        $this->OnInitFunctions[$fieldname] = $hash;
    }

    public function getFrontendData() {
        $html = $this->getHTML();
        $validatorDefinitions = array();

        foreach($this->validators as $fieldName => $validators) {
            $validatorDefinitions[$fieldName] = array();

            foreach($validators as $validator) {
                $validatorDefinitions[$fieldName] = array_merge($validatorDefinitions[$fieldName], $validator->generateValidateJsData());
            }
        }

        return array(
            'html' => $html,
            'js' => $this->getJS(),
            'width' => $this->getWidth(),
            'data' => $this->currentValue,
            'validators' => $validatorDefinitions
        );
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */
namespace ComplexCondition\Form\Type;

use ComplexCondition\Form\Type;

class Hidden extends Type
{

    public function render($fieldName) {
        $html = '<input type="hidden" class="EventEditorInputField" name="'.$fieldName.'" data-field="'.$this->Field->getName().'" autocomplete="off" value="" />';

        return $html;
    }

    public function getGetterFunction() {
        return "return INPUT.find('.EventEditorInputField').val();";
    }

    public function getSetterFunction() {
        return "INPUT.find('.EventEditorInputField').val(VALUE);";
    }

    public function getInitFunction() {
        return '';
    }

}

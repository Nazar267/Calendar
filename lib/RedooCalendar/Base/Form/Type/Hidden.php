<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */

namespace RedooCalendar\Base\Form\Type;

use RedooCalendar\Base\Form\Type;

class Hidden extends Type
{

    public function render($fieldName)
    {
        $html = '<input type="hidden" class="EventEditorInputField" name="' . $fieldName . '" data-field="' . $this->Field->getName() . '" autocomplete="off" value="' . $this->Field->getValue() . '"  data-bind="value:' . $this->bindValue . '" />';

        return $html;
    }

    public function getGetterFunction()
    {
        return "return INPUT.find('.EventEditorInputField').val();";
    }

    public function getSetterFunction()
    {
        return "INPUT.find('.EventEditorInputField').val(VALUE);";
    }

    public function getInitFunction()
    {
        return '';
    }

}

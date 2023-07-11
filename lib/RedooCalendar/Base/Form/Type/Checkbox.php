<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */

namespace RedooCalendar\Base\Form\Type;

use RedooCalendar\Base\Form\Type;

class Checkbox extends Type
{

    public function render($fieldName)
    {
        $html = '<input type="checkbox"' . ($this->readonly ? 'disabled="disabled"' : '') . '  class="EventEditorInputField rcSwitcher" name="' . $fieldName . '" data-field="' . $this->Field->getName() . '" value="1" checked="' . $this->Field->getValue() . '" />';

        return $html;
    }

    public function getGetterFunction()
    {
        return "return INPUT.find('.EventEditorInputField').prop('checked') ? 1 : 0;";
    }

    public function getSetterFunction()
    {
        return "INPUT.find('.EventEditorInputField').prop('checked', VALUE == '1').change();";
    }

    public function getInitFunction()
    {
        return '';
    }

}

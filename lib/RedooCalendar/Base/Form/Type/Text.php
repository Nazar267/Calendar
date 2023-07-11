<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */

namespace RedooCalendar\Base\Form\Type;

use RedooCalendar\Base\Form\Type;

class Text extends Type
{

    public function render($fieldName)
    {
        $changeHtml = $this->changeHandler ? ', events: { change: ' . $this->changeHandler . '}' : '';
        $html = '<input placeholder="' . $this->placeholder . '" id="' . $this->id . '" type="text" ' . ($this->readonly ? 'readonly="readonly"' : '') . ' class="EventEditorInputField" name="' . $fieldName . '" data-field="' . $this->Field->getName() . '" autocomplete="off" value="' . $this->Field->getValue() . '" data-bind="value:' . $this->bindValue . $changeHtml . '" />';

        return $html;
    }

    public function getGetterFunction()
    {
        return "return INPUT.find('.EventEditorInputField').val();";
    }

    public function getSetterFunction()
    {
        return "INPUT.find('.EventEditorInputField').val(VALUE).trigger('blur');";
    }

    public function getInitFunction()
    {
        return '';
    }

}

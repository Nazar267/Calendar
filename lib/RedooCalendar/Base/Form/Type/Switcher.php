<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */
namespace RedooCalendar\Base\Form\Type;

use RedooCalendar\Base\Form\Type;

class Switcher extends Type
{

    public function render($fieldName) {
        $html = '<input type="checkbox"'.($this->readonly ? 'disabled="disabled"' : '').'  class="EventEditorInputField" name="'.$fieldName.'" data-field="'.$this->Field->getName().'" value="1" data-bind="value:' . $this->bindValue . '" />';

        return $html;
    }

    public function getGetterFunction() {
        return "return INPUT.find('.EventEditorInputField').prop('checked') ? 1 : 0;";
    }

    public function getSetterFunction() {
        return "INPUT.find('.EventEditorInputField').prop('checked', VALUE == '1').change();";
    }

    public function getInitFunction() {
        return '
        INPUT.find(".EventEditorInputField").kendoSwitch({
            messages: {
                checked: "Yes",
                unchecked: "No"
            }
        });
        
        INPUT.find(".k-switch").css("margin-top", "35px");
        ';
    }

}

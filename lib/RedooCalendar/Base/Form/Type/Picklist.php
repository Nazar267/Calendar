<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */

namespace RedooCalendar\Base\Form\Type;

use RedooCalendar\Base\Form\Type;
use RedooCalendar\Base\VtUtils;

class Picklist extends Type
{

    public function render($fieldName)
    {
        $options = $this->Field->getOptions();
        $changeHtml = $this->changeHandler ? ', events: { change: ' . $this->changeHandler . '}' : '';

        if (empty($options['sortable'])) {
            $html = '<select data-bind="value:' . $this->bindValue . $changeHtml . '" ' . ($this->readonly ? 'disabled="disabled"' : '') . ' ' . (!empty($options['multiple']) ? 'multiple="multiple"' : '') . ' class="used EventEditorInputField MakeSelect2" name="' . $fieldName . '" data-field="' . $this->Field->getName() . '" >';
            foreach ($options['options'] as $value => $label) {
                $selected = '';

                if ($options['multiple'] && is_array($this->Field->getValue())) {
                    $selected = in_array($value,$this->Field->getValue()) ? 'selected' : '';
                } else {
                    $selected = $value === $this->Field->getValue() ? 'selected' : '';
                }
                $html .= '<option ' . $selected . ' value="' . $value . '">' . $label . '</option>';
            }
            $html .= '</select>';
        } else {
            $html = '<input type="hidden" ' . (!empty($options['multiple']) ? 'multiple="multiple"' : '') . ' class="used EventEditorInputField MakeSelect2" name="' . $fieldName . '" data-field="' . $this->Field->getName() . '" />';
        }

        return $html;
    }

    public function getGetterFunction()
    {
        return "return INPUT.find('.EventEditorInputField').select2('val');";
    }

    public function getSetterFunction()
    {
        return "INPUT.find('.EventEditorInputField').select2('val', VALUE);";
    }

    public function getInitFunction()
    {
        $options = $this->Field->getOptions();

        if (empty($options['sortable'])) {
            return 'INPUT.find(\'.EventEditorInputField\').select2();';
        } else {
            $values = $options['options'];
            $data = array();
            foreach ($values as $option => $optionLabel) {
                $data[] = array('id' => $option, 'text' => $optionLabel);
            }
            return 'INPUT.find(\'.EventEditorInputField\').select2({
                data: ' . VtUtils::json_encode($data) . ',
                ' . (!empty($options['multiple']) ? 'multiple: true,' : '') . '                
            });
                INPUT.select2("container").find("ul.select2-choices").sortable({
                    containment: \'parent\',
                    start: function() { INPUT.find(\'.EventEditorInputField\').select2("onSortStart"); },
                    update: function() { INPUT.find(\'.EventEditorInputField\').select2("onSortEnd"); }
                });        
            ';
        }
    }

}

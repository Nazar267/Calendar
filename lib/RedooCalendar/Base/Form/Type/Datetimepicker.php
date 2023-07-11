<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */

namespace RedooCalendar\Base\Form\Type;

class Datetimepicker extends Text
{

    public function render($fieldName)
    {
        $html = '<input id="' . $this->id . '" type="text" ' . ($this->readonly ? 'readonly="readonly"' : '') . ' class="EventEditorInputField" name="' . $fieldName . '" data-field="' . $this->Field->getName() . '" autocomplete="off" value="" data-bind="value:' . $this->bindValue . ', events: {change: changeTimePicker}" />';

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
        $date = $this->Field->getValue();
        $valueExist = isset($date) ? 'true' : 'false';
        return '
            let today = kendo.date.today();
            let date = new Date("' . $date . '");

            jQuery(INPUT).find(\'label\').remove();
            jQuery(INPUT).find(\'.EventEditorInputField\').kendoDateTimePicker({
                culture: \'en-US\',
                value: ' . $valueExist . ' ? date : today,
                parseFormats: ["MM/dd/yyyy"]
            }).data("kendoDateTimePicker");
            jQuery("span.k-widget.k-datetimepicker.EventEditorInputField").removeClass("EventEditorInputField");
        ';
    }

}

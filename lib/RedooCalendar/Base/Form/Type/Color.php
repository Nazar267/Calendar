<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */

namespace RedooCalendar\Base\Form\Type;

use RedooCalendar\Base\Form\Type;

class Color extends Type
{

    public function render($fieldName)
    {
        $color = $this->Field->getValue() ? $this->Field->getValue() : $this->Field->getOptions()[0];

        $changeHtml = $this->changeHandler ? ', events: { change: ' . $this->changeHandler . '}' : '';
        $html = '
        <div class="palette" data-bind="' . $changeHtml . '" style="margin-top: 35px;"></div>
        <input id="' . $this->id . '" type="hidden" ' . ($this->readonly ? 'readonly="readonly"' : '') . ' class="EventEditorInputField colorpicker" name="' . $fieldName . '" data-field="' . $this->Field->getName() . '" data-bind="value:' . $this->bindValue . '"  value="' . $color . '"/>';
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
        $color = $this->Field->getValue() ? $this->Field->getValue() : $this->Field->getOptions()[0];
        return '
                    jQuery(INPUT).find(".palette").kendoColorPalette({
                        columns: 20,
                        tileSize: {
                            width: 25,
                            height: 25  
                        },
                        value: "' . $color . '",
                        palette: [
                            "' . implode($this->Field->getOptions(), '","') . '"
                        ],
                        change(event) {
                             jQuery(INPUT).find(".EventEditorInputField").val(event.value).trigger("change");
                        }
                    });
                   
        ';
    }

}

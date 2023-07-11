<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 08.03.2019
 * Time: 14:52
 */

namespace ComplexCondition\Form\Type;


class Password extends Text
{
    public function render($fieldName) {
        $html = '<input type="password" '.($this->readonly ? 'readonly="readonly"' : '').' class="EventEditorInputField" style="-webkit-text-security: square;" name="'.$fieldName.'" data-field="'.$this->Field->getName().'" autocomplete="off" value="" />';

        return $html;
    }

}

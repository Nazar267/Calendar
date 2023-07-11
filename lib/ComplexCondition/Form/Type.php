<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */

namespace ComplexCondition\Form;

use ComplexCondition\Form;

abstract class Type
{
    protected $Form = null;
    protected $Field = null;
    protected $readonly = false;

    public function __construct(Form $form, Field $field) {
        $this->Form = $form;
        $this->Field = $field;
    }

    public function getGetterFunction() {
        return '';
    }
    public function makeReadonly() {
        $this->readonly = true;
    }
    public function getSetterFunction() {
        return '';
    }

    /**
     * Variable INPUT is jQuery FormElement DIV
     *
     * @return string
     */
    public function getInitFunction() {
        return '';
    }

    /**
     * @param $fieldname String
     * @return mixed
     */
    abstract function render($fieldname);
}

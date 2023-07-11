<?php


namespace ComplexCondition\Form\Base;


abstract class Validator
{
    protected $values = array();

    public function __construct() {
    }

    public function setCompleteData($completeData) {
        $this->values = $completeData;
    }

    abstract function isValid($value);
    abstract function generateValidateJsData();
}

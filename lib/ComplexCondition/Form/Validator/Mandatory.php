<?php
namespace ComplexCondition\Form\Validator;
use ComplexCondition\Form\Base\Validator;

class Mandatory extends Validator
{

    function isValid($value)
    {
        if(empty($value)) {
            throw new \Exception('Dieses Feld ist ein Pflichtfeld');
        }
    }

    function generateValidateJsData()
    {
        return array(
            'presence' => array(
                'allowEmpty' => false,
                'message' => 'Dieses Feld ist ein Pflichtfeld'
            )
        );
    }
}

<?php
namespace ComplexCondition\Form\Validator;

use ComplexCondition\Form\Base\Validator;

class Regex extends Validator
{
    private $regex = '';
    private $errorText = '';

    public function __construct($regexString, $errorText = 'Der Wert passt nicht zur Vorgabe %s')
    {
        $this->regex = $regexString;
        $this->errorText = $errorText;

        parent::__construct();
    }

    function isValid($value)
    {
        if(preg_match('/'.$this->regex.'/', $value) == false) {
            throw new \Exception(sprintf($this->errorText, $this->regex));
        }
    }

    function generateValidateJsData()
    {
        $data = array(
            'format' => array(
                'pattern' => $this->regex,
                'message' => $this->errorText
            )
        );

        return $data;
    }
}

<?php
namespace RedooCalendar\Base\Form\Validator;
use RedooCalendar\Base\Form\Base\Validator;

class Length extends Validator
{
    private $minLength = null;
    private $maxLength = null;
    private $isLength = null;

    private $minLengthError = null;
    private $maxLengthError = null;
    private $isLengthError = null;

    public function minLength($minLength, $errorMessage = 'Die Eingabe muss mindestens %d Zeichen besitzen') {
        $this->minLength = intval($minLength);
        $this->minLengthError = $errorMessage;
    }

    public function maxLength($maxLength, $errorMessage = 'Die Eingabe darf maximal %d Zeichen besitzen') {
        $this->maxLength = intval($maxLength);
        $this->maxLengthError =  $errorMessage;
    }

    public function isLength($isLength, $errorMessage = 'Die Eingabe muss genau %d Zeichen besitzen') {
        $this->isLength = intval($isLength);
        $this->isLengthError =  $errorMessage;
    }

    public function generateValidateJsData() {
        $data = array();

        if($this->minLength !== null && $this->isLength === null) {
            $data['minimum'] = $this->minLength;
            $data['tooShort'] = str_replace('%d', '%{count}', $this->minLengthError);
        }

        if($this->maxLength !== null && $this->isLength === null) {
            $data['maximum'] = $this->maxLength;
            $data['tooLong'] = str_replace('%d', '%{count}', $this->maxLengthError);
        }

        if($this->isLength !== null) {
            $data = array();
            $data['is'] = $this->isLength;
            $data['wrongLength'] = str_replace('%d', '%{count}', $this->isLengthError);
        }

        if(empty($data)) {
            return array();
        }

        return array('length' => $data);
    }

    public function isValid($value)
    {
        if($this->minLength !== null && strlen($value) < $this->minLength) {
            throw new \Exception(sprintf($this->minLengthError, $this->minLength));
        }

        if($this->maxLength !== null && strlen($value) > $this->maxLength) {
            throw new \Exception(sprintf($this->maxLengthError, $this->maxLength));
        }

        if($this->isLength !== null && strlen($value) != $this->isLength) {
            throw new \Exception(sprintf($this->isLengthError, $this->isLength));
        }

        return true;
    }
}

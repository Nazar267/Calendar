<?php
namespace ComplexCondition\Form\Validator;

class Alphanumerical extends Regex
{

    public function __construct()
    {
        parent::__construct('^[A-Za-z0-9]+$', 'Nur Buchstaben und Zahlen sind erlaubt');
    }

}

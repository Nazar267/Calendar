<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 17:49
 */

namespace ComplexCondition\Form;


use ComplexCondition\Form;

class Group
{
    private $Tab = null;
    private $Form = null;
    private $Fields = array();

    private $Headline = '';
    private $Columns = 1;

    /**
     * Group constructor.
     * @param Tab $tab
     * @param Form $form
     */
    public function __construct(Tab $tab, Form $form) {

        $this->Tab = $tab;
        $this->Form = $form;

    }

    public function addRow($columns) {
        $newRow = new Row($this, $this->Form);
    }
    /**
     * @return Field
     */
    public function addField() {

        $newField = new Field($this, $this->Form);
        $this->Form->registerField($newField);

        $this->Fields[] = $newField;

        return $newField;
    }

    /**
     * @param $columns
     * @throws \Exception
     */
    public function setColumCount($columns) {
        if($columns > 4) {
            throw new \Exception('Max 4 columns');
        }

        $this->Columns = intval($columns);
    }

    public function getColumnCount() {
        return $this->Columns;
    }

    /**
     * @param $headline
     */
    public function setHeadline($headline) {
        $this->Headline = $headline;
    }
    public function getHeadline() {
        return $this->Headline;
    }

    /**
     * @return array
     */
    public function getFields() {
        return $this->Fields;
    }

    /**
     * @return bool
     */
    public function hasHeadline() {
        return !empty($this->Headline);
    }
}

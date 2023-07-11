<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */

namespace RedooCalendar\Base\Form;

use RedooCalendar\Base\Form;

abstract class Type
{
    protected $Form = null;
    protected $id = '';
    protected $bindValue = '';
    protected $changeHandler = '';
    protected $bindScope = '';
    protected $Field = null;
    protected $readonly = false;
    protected $related = null;
    protected $placeholder = '';

    public function __construct(Form $form, Field $field)
    {
        $this->Form = $form;
        $this->Field = $field;
    }

    public function getGetterFunction()
    {
        return '';
    }

    public function makeReadonly()
    {
        $this->readonly = true;
    }

    public function getSetterFunction()
    {
        return '';
    }

    /**
     * Variable INPUT is jQuery FormElement DIV
     *
     * @return string
     */
    public function getInitFunction()
    {
        return '';
    }

    /**
     * @param $fieldname String
     * @return mixed
     */
    abstract function render($fieldname);

    public function setBindValue(string $bindValue): Type
    {
        $this->bindValue = $bindValue;
        return $this;
    }

    public function setChangeHandler(string $changeHandler = null): Type
    {
        $this->changeHandler = $changeHandler;
        return $this;
    }

    public function setBindScope(string $bindScope): Type
    {
        $this->bindScope = $bindScope;
        return $this;
    }

    public function setId(string $id = null): Type
    {
        $this->id = $id;
        return $this;
    }

    public function relatedTo(): string
    {

        if ($this->related) {
            $conditions = [];
            foreach (array_keys($this->related) as $related) {
                $conditions[$related] = false;
            }

            $script = 'let conditions = ' . json_encode($conditions);

            foreach ($this->related as $related => $relatedValues) {
                $uid = uniqid();
                $script .= '
                
                let values' . $uid . ' = ["' . implode('","', $relatedValues) . '"];
                
                if(!values' . $uid . '.includes(jQuery("#' . $this->Form->getFormId() . '").find("[data-field=\'' . $related . '\']").val())) {
                    jQuery(INPUT).hide();
                }
    
                jQuery("#' . $this->Form->getFormId() . '").find("[data-field=\'' . $related . '\']").change(function(event) {
                    conditions.' . $related . ' = values' . $uid . '.includes(event.val) 

                    if(Object.values(conditions).indexOf(false) == -1) {
                        jQuery(INPUT).slideDown(100).trigger("resize_dialog");
                    } else {
                        jQuery(INPUT).slideUp(100).trigger("resize_dialog");
                    }
                })
            ';
            }
            return $script;
        }
        return '';
    }

    public function setRelated(array $related): Type
    {
        $this->related = $related;
        return $this;
    }

    public function setPlaceholder(string $placeholder): Type
    {
        $this->placeholder = $placeholder;
        return $this;
    }

}

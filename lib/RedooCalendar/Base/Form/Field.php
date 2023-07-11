<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 17:50
 */

namespace RedooCalendar\Base\Form;


use RedooCalendar\Base\Form;
use RedooCalendar\Helper\Translator;

class Field
{
    use Translator;

    protected $path = [];
    protected $bindValue = '';
    protected $changeHandler = '';
    protected $bindScope = '';
    protected $id = '';
    protected $related = [];
    protected $relatedValues = [];
    protected $placeholder = '';

    private $Group = null;
    private $Form = null;

    private $Type = null;
    private $Name = null;
    private $Options = array();
    private $FullWidth = false;
    private $Label = '';
    private $helpText = '';

    private $currentValue = null;

    private $readonly = false;
    private $validators = array();
    private $disablePaddings = false;
    private $disableLabel = false;

    const INPUT_HTMLEDITOR = 'htmleditor';

    const INPUT_CHECKBOX = 'checkbox';
    const INPUT_SWITCHER = 'switcher';
    const INPUT_BOOLEAN = 'checkbox';

    const INPUT_HIDDEN = 'hidden';
    const INPUT_EMAIL = 'email';
    const INPUT_TEXT = 'text';
    const INPUT_COLOR = 'color';
    const INPUT_DATE = 'date';
    const INPUT_DATE_TIME_PICKER = 'datetimepicker';
    const INPUT_PASSWORD = 'password';
    const INPUT_PICKLIST = 'picklist';
    const INPUT_FILESTOREUPLOADER = 'filestore';

    public function __construct(Group $group, Form $form)
    {
        $this->Group = $group;
        $this->Form = $form;
    }

    public function setHelptext($helpText)
    {
        $this->helpText = $helpText;
        return $this;
    }

    public function disableLabel()
    {
        $this->disableLabel = true;

        return $this;
    }

    public function disablePaddings()
    {
        $this->disablePaddings = true;

        return $this;
    }

    public function isFullWidth()
    {
        return $this->FullWidth === true;
    }

    public function enableFullwidth()
    {
        $this->FullWidth = true;
        return $this;
    }

    public function setReadonly($readonly = true)
    {
        $this->readonly = ($readonly == true);
        return $this;
    }

    public function setType($type)
    {
        $this->Type = $type;
        return $this;
    }

    /**
     * @param callable|\RedooCalendar\Base\Form\Base\Validator $validator
     * @return $this
     */
    public function addValidator($validator)
    {
        $this->validators[] = $validator;
        return $this;
    }

    public function setLabel($value)
    {
        $this->Label = self::t($value);
        return $this;
    }

    public function setName($value)
    {
        $this->Name = $value;
        return $this;
    }

    public function getName()
    {
        return $this->Name;
    }

    public function setOptions($value)
    {
        $this->Options = $value;
        return $this;
    }

    public function getOptions()
    {
        return $this->Options;
    }

    public function setPath(array $path = []): Field
    {
        $this->path = $path;
        return $this;
    }

    public function setBindValue(string $value = null): Field
    {
        $this->bindValue = $value;
        return $this;
    }

    public function setChangeHandler(string $changeHandler = null): Field
    {
        $this->changeHandler = $changeHandler;
        return $this;
    }

    public function setBindScope(string $bindScope = null): Field
    {
        $this->bindScope = $bindScope;
        return $this;
    }


    public function render()
    {
        if (empty($this->Type)) {
            throw new \Exception('Please specify a type for field ' . $this->Name);
        }

        $typeClassName = ucfirst(strtolower($this->Type));

        $className = '\\RedooCalendar\\Base\\Form\\Type\\' . $typeClassName;
        if (class_exists($className) === false) {
            return '<div class="alert alert-danger">Fieldtype <strong>' . $this->Type . '</strong> not found!</div>';
        }

        /**
         * @var $obj Type
         */
        $obj = new $className($this->Form, $this);

        $obj->setBindValue($this->bindValue);
        $obj->setPlaceholder($this->placeholder);
        $obj->setChangeHandler($this->changeHandler);
        $obj->setBindScope($this->bindScope);
        $obj->setId($this->id);


        $scope = $this->Form->getVariableScope();

        if ($this->readonly === true) {
            $obj->makeReadonly();
        }

        if (empty($scope)) {
            $fieldName = $this->Name;
        } else {
            if (count($this->path) > 0) {
                $fieldName = $scope . '[' . implode('][', $this->path) . '][' . $this->Name . ']';
            } else {
                $fieldName = $scope . '[' . $this->Name . ']';
            }
        }


        $fieldhtml = $obj->render($fieldName);

        $functionName = '';
        if (empty($this->Label)) {
            $html = $fieldhtml;
        } else {
            $html = '<div class="group materialstyle ' . ($this->readonly ? 'ReadonlyField' : '') . ' ' . ($this->disablePaddings ? 'no-padding' : '') . '  type-' . $this->Type . ' ' . (!empty($this->helpText) ? 'has-helptext' : '') . '">' . (!empty($this->helpText) ? '<i class="fa fa-question-circle CC_helpText" data-tippy-content="' . $this->helpText . '" aria-hidden="true"></i>' : '') . $fieldhtml . '<span class="bar"></span>' . ($this->disableLabel == false ? '<label>' . $this->Label . '</label>' : '') . '<span class="errorMsg"></span></div>';
        }


        $this->Form->registerGetter($this->Name, $obj->getGetterFunction());
        $this->Form->registerSetter($this->Name, $obj->getSetterFunction());

        if (!$this->related) {
            $this->Form->registerOnInit($this->Name, $obj->getInitFunction());
        } else {
            $obj->setRelated($this->related);
            $this->Form->registerOnInit(
                $this->Name, $obj->getInitFunction().$obj->relatedTo()
            );
        }
        if (!empty($this->validators)) {
            $this->Form->registerValidators($this->Name, $this->validators);
        }

        return $html;
    }


    public function setValue($value)
    {
        $this->currentValue = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->currentValue;
    }

    public function isValid($fieldValue, $completeValues)
    {
        if (empty($this->validators)) return true;

        foreach ($this->validators as $validator) {
            $validator->setCompleteData($completeValues);

            if ($validator->isValid($fieldValue) === false) return false;
        }

        return true;
    }

    public function setId(string $id = null): Field
    {
        $this->id = $id;
        return $this;
    }

    public function setRelated(array $related): Field
    {
        $this->related = $related;
        return $this;
    }

    public function setPlaceholder(string $placeholder): Field {
        $this->placeholder = $placeholder;
        return $this;
    }

}

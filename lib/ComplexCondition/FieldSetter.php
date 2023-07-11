<?php
/**
 * User: StefanWarnat
 * Date: 16.04.2019
 * Time: 14:59
 */

/**
 * @version 1.0
 */
namespace ComplexCondition;


class FieldSetter
{
    /**
     * @var array
     */
    private $fieldSetConfiguration = array();

    /**
     * @var null|array
     */
    private $processed = null;

    /**
     * @var VTEntity|null
     */
    private $sourceContext = null;

    /**
     * FieldSetter constructor.
     * @param array $fieldSetConfiguration Configuration of Fieldsetter
     * @param null|VTEntity $sourceContext  Source Context to load variables from
     */
    public function __construct($fieldSetConfiguration = array(), $sourceContext = null) {
        $this->fieldSetConfiguration = $fieldSetConfiguration;
        $this->sourceContext = $sourceContext;
    }

    /**
     * Function process the data from configuration to ready data
     */
    private function process() {

        if($this->processed !== null) {
            return;
        }

        $this->processed = array();
        foreach($this->fieldSetConfiguration as $config) {
            switch($config['mode']) {
                case 'value':

                    if($this->sourceContext !== null) {

                        $this->processed[$config['field']] = VTTemplate::parse($config['value'], $this->sourceContext);

                    } else {

                        $this->processed[$config['field']] = $config['value'];

                    }

                    break;
            }


        }
    }

    /**
     * This function will return the configuration as flat array
     * Structure is like fieldname -> value
     *
     * @return array
     */
    public function getAsArray() {
        $this->process();

        return $this->processed;
    }
}
<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 18.06.15 16:21
 * You must not use this file without permission.
 */
namespace ComplexCondition\Plugin;


class CoreConditionOperator extends \ComplexCondition\ConditionPlugin
{
    public function getOperators($moduleName) {
        $operators = array(
            'equal' => array(
                'config' => array(
                    'value' => array(
                        'type' => 'default'
                    )
                ),
                'label' => 'is equal',
                'fieldtypes' => array('text', 'date', 'picklist', 'multipicklist', 'number', 'crmid'),
                'text' => '##field## is ##not##equal to ##c.value##',
            ),

            'contains' => array(
                'config' => array(
                    'value' => array(
                        'type' => 'default'
                    ),
                ),
                'label' => 'contains',
                'fieldtypes' => array('text', 'multipicklist', 'number')
            ),
            'has_changed' => array(
                'config' => array(
                    'value' => array(
                        'type' => 'textfield'
                    ),
                ),
                'mysqlmode' => false,
                'label' => 'has changed',
                'fieldtypes' => array('all')
            ),
            'starts_with' => array(
                'config' => array(
                    'value' => array(
                        'type' => 'textfield'
                    ),
                ),
                'label' => 'starts with',
                'fieldtypes' => array('text')
            ),
            'between' => array(
                'config' => array(
                    'from' => array(
                        'type' => 'textfield',
                        'length' => 'short',
                    ),
                    'to' => array(
                        'type' => 'textfield',
                        'label' => ' and ',
                        'length' => 'short',
                    ),
                ),
                'label' => 'between',
                'fieldtypes' => array('date'),
                'text' => '##field## is ##not##between ##c.from## and ##c.to##'
            ),
            'within_next' => array(
                'config' => array(
                    'value' => array(
                        'type' => 'textfield',
                        'label' => 'Within next ',
                        'length' => 'short',
                    ),
                    'type' => array(
                        'type' => 'picklist',
                        'options' => array(
                            'day' => 'Day/s',
                            'week' => 'Week/s',
                            'month' => 'Month/s',
                            'quarter' => 'Quarter/s',
                            'year' => 'Years/s',
                        )
                    ),
                ),
                'label' => 'within next',
                'fieldtypes' => array('date')
            ),
            'within_past' => array(
                'config' => array (
                    'value' => array(
                        'type' => 'textfield',
                        'length' => 'short',
                    ),
                    'type' => array(
                        'type' => 'picklist',
                        'options' => array(
                            'day' => 'Day/s',
                            'week' => 'Week/s',
                            'month' => 'Month/s',
                            'quarter' => 'Quarter/s',
                            'year' => 'Years/s',
                        )
                    ),

                ),
                'label' => 'within last',
                'fieldtypes' => array('date')
            ),
            'current' => array(
                'config' => array(
                    'type' => array(
                        'type' => 'picklist',
                        'options' => array(
                            'day' => 'Day',
                            'week' => 'Week',
                            'month' => 'Month',
                            'quarter' => 'Quarter',
                            'year' => 'Year',
                        )
                    ),

                ),
                'label' => 'in current',
                'fieldtypes' => array('date')
            ),
            'ends_with' => array(
                'config' => array(
                    'value' => array(
                        'type' => 'textfield'
                    ),
                ),
                'label' => 'ends with',
                'fieldtypes' => array('text')
            ),
            'after' => array(
                'config' => array(
                    'value' => array(
                        'type' => 'textfield',
                        'length' => 'short',
                    ),
                ),
                'label' => 'after',
                'fieldtypes' => array('date')
            ),
            'before' => array(
                'config' => array(
                    'value' => array(
                        'type' => 'textfield',
                        'length' => 'short',
                    ),
                ),
                'label' => 'before',
                'fieldtypes' => array('date')
            ),
            'bigger' => array(
                'config' => array(
                    'value' => array(
                        'type' => 'textfield'
                    ),
                ),
                'label' => 'greater than',
                'fieldtypes' => array('number' )
            ),
            'lower' => array(
                'config' => array(
                    'value' => array(
                        'type' => 'textfield'
                    ),
                ),
                'label' => 'lower then',
                'fieldtypes' => array('number' )
            ),
            'is_checked' => array(
                'config' => array(),
                'label' => 'is checked',
                'fieldtypes' => array('boolean'),
                'text' => '##field## is checked'
            ),
            'yesterday' => array(
                'config' => array(),
                'label' => 'yesterday',
                'fieldtypes' => array('date')
            ),
            'today' => array(
                'config' => array(),
                'label' => 'today',
                'fieldtypes' => array('date')
            ),
            'tomorrow' => array(
                'config' => array(),
                'label' => 'tomorrow',
                'fieldtypes' => array('date')
            ),
            'is_empty' => array(
                'config' => array(),
                'label' => 'is empty',
                'fieldtypes' => array('text', 'picklist', 'multipicklist', 'number', 'crmid')
            ),
            'date_empty' => array(
                'config' => array(),
                'label' => 'date is empty',
                'fieldtypes' => array('date',)
            ),
            'is_numeric' => array(
                'config' => array(),
                'label' => 'is numeric',
                'fieldtypes' => array('text', 'picklist', 'multipicklist', 'number',)
            ),
            'is_within' => array(
                'config' => array(
                    'value' => array(
                        'type' => 'default',
                        'multiple' => true,
                    )
                ),
                'label' => 'is one of',
                'fieldtypes' => array('picklist'),
            )

        );

        return $operators;
    }

    public function generateSQLCondition($key, $columnName, $config, $not) {
        $adb = \PearDatabase::getInstance();

        if(is_string($config)) {
            $config = array('value' => $config);
        }

        // default calculations
        switch($key) {
            case 'is_within':
                foreach($config['value'] as $index => $value) {
                    $config['value'][$index] = $adb->quote($value);
                }
                return "".$columnName." " . ($not ? "NOT" : "" ) . ' IN ('.implode(',', $config['value']).')';
                break;
            case 'reference_equal':
            case 'equal':
                // Tested by swa 2016-01-28
                return "".$columnName." " . ($not ? "!" : "" ) . "= ".$adb->quote($config['value'])."";
                break;
            case 'contains':
                // Tested by swa 2016-01-28
                return "".$columnName." " . ($not ? "NOT" : "" ) . " LIKE ".$adb->quote("%".$config['value']."%")."";
                break;
            case 'starts_with':
                // Tested by swa 2016-01-28
                return "".$columnName." " . ($not ? "NOT" : "" ) . " LIKE ".$adb->quote("".$config['value']."%")."";
                break;
            case 'ends_with':
                // Tested by swa 2016-01-28
                return "".$columnName." " . ($not ? "NOT" : "" ) . " LIKE ".$adb->quote("%".$config['value']."")."";
                break;
            case 'bigger':
            case 'after':
                // Tested by swa 2016-01-28
                return "".$columnName." " . ($not ? "<=" : ">" ) . " ".$adb->quote("".$config['value'])."";
                break;
            case 'lower':
            case 'before':
                // Tested by swa 2016-01-28
                return "".$columnName." " . ($not ? ">=" : "<" ) . " ".$adb->quote("".$config['value']."")."";
                break;
            case 'today':
                // Tested by swa 2016-01-28
                return "DATE(".$columnName.") " . ($not ? "!" : "" ) . "= '".date('Y-m-d', time())."'";
                break;
            case 'tomorrow':
                // Tested by swa 2016-01-28
                return "DATE(".$columnName.") " . ($not ? "!" : "" ) . "= '".date('Y-m-d', time() + 86400)."'";
                break;
            case 'yesterday':
                // Tested by swa 2016-01-28
                return "DATE(".$columnName.") " . ($not ? "!" : "" ) . "= '".date('Y-m-d', time() - 86400)."'";
                break;
            case 'is_checked':
                // Tested by swa 2016-01-28
                return "".$columnName." " . ($not ? "!" : "" ) . "= 1";
                break;
            case "is_numeric":
                // Tested by swa 2016-01-29
                return "".$columnName." " . ($not ? "NOT" : "" ) . " REGEXP '^[[:digit:]]+$'";
                break;
            case 'date_empty':
            case 'is_empty':
                // Tested by swa 2016-01-28
                if(!$not) {
                    return "(".$columnName." = '' OR ".$columnName." = '0' OR ".$columnName." = '0000-00-00' OR ".$columnName." IS NULL)";
                } else {
                    return "(".$columnName." != '' AND ".$columnName." != '0' AND ".$columnName." != '0000-00-00' AND ".$columnName." IS NOT NULL)";
                }
                break;
        }

        $firstDay = false;

        // date calculations
        switch($key) {
            case 'between':
                // Tested by swa 2016-01-28
                $firstDay = date("Y-m-d", strtotime($config['from']));
                $lastDay = date("Y-m-d", strtotime($config['to']));

                break;
            case 'current':
                // Tested by swa 2016-01-28
                switch($config['type']) {
                    case 'day':
                        //$lastweek0 = date("Y-m-d", strtotime("-2 week Sunday"));
                        $lastDay = $firstDay = date('Y-m-d');
                        break;
                    case 'week':
                        $firstDay = date("Y-m-d", strtotime('last Sunday'));
                        $lastDay = date("Y-m-d", strtotime('this Saturday'));
                        break;
                    case 'month':
                        $firstDay = date("Y-m-d", strtotime('first day of this month'));
                        $lastDay = date("Y-m-d", strtotime('last day of this month'));
                        break;
                    case 'quarter':
                        $dates = $this->get_dates_of_quarter('current', null, 'Y-m-d');
                        $firstDay = $dates['start'];
                        $lastDay = $dates['end'];
                        break;
                    case 'year':
                        $firstDay = date("Y").'-01-01';
                        $lastDay = date("Y").'-12-31';
                        break;
                }

                break;
            case 'within_past':
                // Tested by swa 2016-01-28
                switch($config['type']) {
                    case 'day':
                        //$lastweek0 = date("Y-m-d", strtotime("-2 week Sunday"));
                        $firstDay = date('Y-m-d', strtotime('-'.$config['value'].' day'));
                        $lastDay = date("Y-m-d", time());
                        break;
                    case 'week':
                        $firstDay = date("Y-m-d", strtotime('-'.($config['value']+1).' week Sunday'));
                        $lastDay = date("Y-m-d", strtotime('-'.$config['value'].' week Saturday'));
                        break;
                    case 'month':
                        $firstDay = date("Y-m-d", strtotime('first day of -'.$config['value'].' month'));
                        $lastDay = date("Y-m-d", strtotime('last day of last month'));
                        break;
                    case 'quarter':
                        $dateObj = new \DateTime();
                        $currentQuarter = ceil($dateObj->format('n') / 3);

                        $years = floor($config['value'] / 4);
                        $year = date('Y') - $years;

                        $quarter = $currentQuarter - $config['value'] % 4;
                        if($quarter < 1) {
                            $year -= 1;
                            $quarter = 4 - abs($quarter);
                        }

                        $start = $this->get_dates_of_quarter(intval($quarter), intval($year), 'Y-m-d');
                        $end = $this->get_dates_of_quarter('previous', null, 'Y-m-d');

                        $firstDay = $start['start'];
                        $lastDay = $end['end'];
                        break;
                    case 'year':
                        $firstDay = (date("Y") - $config['value']).'-01-01';
                        $lastDay = (date("Y") - 1).'-12-31';
                        break;
                }
                break;
            case 'within_next':
                // Tested by swa 2016-01-28
                switch($config['type']) {
                    case 'day':
                        //$lastweek0 = date("Y-m-d", strtotime("-2 week Sunday"));
                        $firstDay = date("Y-m-d", time());
                        $lastDay = date('Y-m-d', strtotime('+'.$config['value'].' day'));
                        break;
                    case 'week':
                        $firstDay = date("Y-m-d", strtotime('this Sunday'));
                        $lastDay = date("Y-m-d", strtotime('+'.$config['value'].' week Saturday'));
                        break;
                    case 'month':
                        $firstDay = date("Y-m-d", strtotime('first day of next month'));
                        $lastDay = date("Y-m-d", strtotime('last day of +'.$config['value'].' month'));
                        break;
                    case 'quarter':
                        $dateObj = new \DateTime();
                        $currentQuarter = ceil($dateObj->format('n') / 3);
                        $currentQuarter += $config['value'];
                        if($currentQuarter % 4 > 0) {
                            $year = date('Y') + intval($currentQuarter / 4);
                            $quarter = $currentQuarter % 4;
                        } else {
                            $year = date('Y') + intval($currentQuarter / 4) - 1;
                            $quarter = 4;
                        }

                        $start = $this->get_dates_of_quarter('next', null, 'Y-m-d');
                        $end = $this->get_dates_of_quarter($quarter, $year, 'Y-m-d');

                        $firstDay = $start['start'];
                        $lastDay = $end['end'];
                        break;
                    case 'year':
                        $firstDay = (date("Y") + 1).'-01-01';
                        $lastDay = (date("Y") +  $config['value']).'-12-31';
                        break;
                }


                break;
        }

        if($firstDay !== false) {
            if($firstDay != $lastDay) {
                if (!$not) {
                    return 'DATE(' . $columnName . ') >= "' . $firstDay . '" AND DATE(' . $columnName . ') <= "' . $lastDay . '"';
                } else {
                    return 'DATE(' . $columnName . ') < "' . $firstDay . '" OR DATE(' . $columnName . ') > "' . $lastDay . '"';
                }
            } else {
                if (!$not) {
                    return 'DATE(' . $columnName . ') = "' . $firstDay . '"';
                } else {
                    return 'DATE(' . $columnName . ') <> "' . $firstDay . '"';
                }
            }
        }


    }

    // Copyright: Delmo
    // http://stackoverflow.com/questions/21185924/get-startdate-and-enddate-for-current-quarter-php
    private function get_dates_of_quarter($quarter = 'current', $year = null, $format = null)
    {
        $dateObj = new \DateTime();
        if ( !is_int($year) ) {
            $year = $dateObj->format('Y');
        }

        $current_quarter = ceil($dateObj->format('n') / 3);
        switch (  strtolower($quarter) ) {
            case 'this':
            case 'current':
                $quarter = ceil($dateObj->format('n') / 3);
                break;

            case 'previous':
                $year = $dateObj->format('Y');
                if ($current_quarter == 1) {
                    $quarter = 4;
                    $year--;
                } else {
                    $quarter =  $current_quarter - 1;
                }
                break;
            case 'next':
                $year = $dateObj->format('Y');
                if ($current_quarter == 4) {
                    $quarter = 1;
                    $year++;
                } else {
                    $quarter =  $current_quarter + 1;
                }
                break;

            case 'first':
                $quarter = 1;
                break;

            case 'last':
                $quarter = 4;
                break;

            default:
                $quarter = (!is_int($quarter) || $quarter < 1 || $quarter > 4) ? $current_quarter : $quarter;
                break;
        }
        if ( $quarter === 'this' ) {
            $quarter = ceil($dateObj->format('n') / 3);
        }

        $start = new \DateTime($year.'-'.(3*$quarter-2).'-1 00:00:00');
        $end = new \DateTime($year.'-'.(3*$quarter).'-'.($quarter == 1 || $quarter == 4 ? 31 : 30) .' 23:59:59');

        return array(
            'start' => $format ? $start->format($format) : $start,
            'end' => $format ? $end->format($format) : $end,
        );
    }

    public function checkValue($context, $key, $fieldvalue, $config, $checkConfig)
    {
        // old check functions
        $checkvalue = $config['value'];
        switch ($key) {
            case "is_within":
                if(in_array($fieldvalue, $checkvalue)) {
                    return true;
                }
                return false;
                break;
            case "reference_equal":
            case "equal":
                // Tested by swa 2016-01-27
                if ($fieldvalue == $checkvalue) {
                    return true;
                }
                return false;
                break;
            case "contains":
                // Tested by swa 2016-01-27
                if (strpos($fieldvalue, $checkvalue) !== false) {
                    return true;
                }
                return false;
                break;
            case "starts_with":
                // Tested by swa 2016-01-27
                if (strlen($fieldvalue) < strlen($checkvalue))
                    return false;

                return substr($fieldvalue, 0, strlen($checkvalue)) == $checkvalue;

                break;
            case "ends_with":
                // Tested by swa 2016-01-27
                if (strlen($fieldvalue) < strlen($checkvalue))
                    return false;

                return substr($fieldvalue, strlen($fieldvalue) - strlen($checkvalue)) == $checkvalue;
                break;
            case "has_changed":
                // Tested by swa 2016-01-27
                $hasChanged = \Workflow\EntityDelta::hasChanged($context->getModuleName(), $context->getId(), $checkConfig["field"]);
                $checkvalue = trim($checkvalue);

                if (empty($checkvalue)) {
                    return $hasChanged;
                } else {
                    return $hasChanged && $checkvalue == $fieldvalue;
                }

                break;
            case "after":
            case "bigger":
            // Tested by swa 2016-01-27
                if ($fieldvalue > $checkvalue) {
                    return true;
                }
                return false;
                break;
            case "before":
            case "lower":
            // Tested by swa 2016-01-27
                if ($fieldvalue < $checkvalue) {
                    return true;
                }
                return false;
                break;
            case "is_empty":
                // Tested by swa 2016-01-27
                $fieldvalue = trim($fieldvalue, "0.");
                if (empty($fieldvalue)) {
                    return true;
                }
                return false;
                break;
            case "date_empty":
                // Tested by swa 2016-01-27
                $fieldvalue = trim($fieldvalue, ".");
                if (empty($fieldvalue) || $fieldvalue == "0000-00-00") {
                    return true;
                }
                return false;
                break;
            case "is_checked":
                // Tested by swa 2016-01-27
                if ($fieldvalue == "1") {
                    return true;
                }
                return false;
                break;
            case "is_numeric":
                // Tested by swa 2016-01-27
                return is_numeric($fieldvalue);
                break;
            case "today":
                // Tested by swa 2016-01-27
                return date('Y-m-d', time()) == date('Y-m-d', strtotime($fieldvalue));
                break;
            case "yesterday":
                // Tested by swa 2016-01-27
                return date('Y-m-d', time() - 86400) == date('Y-m-d', strtotime($fieldvalue));
                break;
            case "tomorrow":
                // Tested by swa 2016-01-27
                return date('Y-m-d', time() + 86400) == date('Y-m-d', strtotime($fieldvalue));
                break;
        }

        $firstDay = false;

        // date calculations
        switch($key) {
            case 'between':
                // Tested by swa 2016-01-27
                $firstDay = date("Y-m-d", strtotime($config['from']));
                $lastDay = date("Y-m-d", strtotime($config['to']));

                break;
            case 'current':
                // Tested by swa 2016-01-27
                switch($config['type']) {
                    case 'day':
                        //$lastweek0 = date("Y-m-d", strtotime("-2 week Sunday"));
                        $lastDay = $firstDay = date('Y-m-d');
                        break;
                    case 'week':
                        $firstDay = date("Y-m-d", strtotime('last Sunday'));
                        $lastDay = date("Y-m-d", strtotime('this Saturday'));
                        break;
                    case 'month':
                        $firstDay = date("Y-m-d", strtotime('first day of this month'));
                        $lastDay = date("Y-m-d", strtotime('last day of this month'));
                        break;
                    case 'quarter':
                        $dates = $this->get_dates_of_quarter('current', null, 'Y-m-d');
                        $firstDay = $dates['start'];
                        $lastDay = $dates['end'];
                        break;
                    case 'year':
                        $firstDay = date("Y").'-01-01';
                        $lastDay = date("Y").'-12-31';
                        break;
                }

                break;
            case 'within_past':
                // Tested by swa 2016-01-27
                switch($config['type']) {
                    case 'day':
                        //$lastweek0 = date("Y-m-d", strtotime("-2 week Sunday"));
                        $firstDay = date('Y-m-d', strtotime('-'.$config['value'].' day'));
                        $lastDay = date("Y-m-d", time() - 86400);
                        break;
                    case 'week':
                        $firstDay = date("Y-m-d", strtotime('-'.($config['value']+1).' week Sunday'));
                        $lastDay = date("Y-m-d", strtotime('-'.$config['value'].' week Saturday'));
                        break;
                    case 'month':
                        $firstDay = date("Y-m-d", strtotime('first day of -'.$config['value'].' month'));
                        $lastDay = date("Y-m-d", strtotime('last day of last month'));
                        break;
                    case 'quarter':
                        $dateObj = new \DateTime();
                        $currentQuarter = ceil($dateObj->format('n') / 3);

                        $years = floor($config['value'] / 4);
                        $year = date('Y') - $years;

                        $quarter = $currentQuarter - $config['value'] % 4;
                        if($quarter < 1) {
                            $year -= 1;
                            $quarter = 4 - abs($quarter);
                        }

                        $start = $this->get_dates_of_quarter(intval($quarter), intval($year), 'Y-m-d');
                        $end = $this->get_dates_of_quarter('previous', null, 'Y-m-d');

                        $firstDay = $start['start'];
                        $lastDay = $end['end'];
                        break;
                    case 'year':
                        $firstDay = (date("Y") - $config['value']).'-01-01';
                        $lastDay = (date("Y") - 1).'-12-31';
                        break;
                }
                break;
            case 'within_next':
                // Tested by swa 2016-01-27
                switch($config['type']) {
                    case 'day':
                        //$lastweek0 = date("Y-m-d", strtotime("-2 week Sunday"));
                        $firstDay = date("Y-m-d", time() + 86400);
                        $lastDay = date('Y-m-d', strtotime('+'.$config['value'].' day'));
                        break;
                    case 'week':
                        $firstDay = date("Y-m-d", strtotime('this Sunday'));
                        $lastDay = date("Y-m-d", strtotime('+'.$config['value'].' week Saturday'));
                        break;
                    case 'month':
                        $firstDay = date("Y-m-d", strtotime('first day of next month'));
                        $lastDay = date("Y-m-d", strtotime('last day of +'.$config['value'].' month'));
                        break;
                    case 'quarter':
                        $dateObj = new \DateTime();
                        $currentQuarter = ceil($dateObj->format('n') / 3);
                        $currentQuarter += $config['value'];
                        if($currentQuarter % 4 > 0) {
                            $year = date('Y') + intval($currentQuarter / 4);
                            $quarter = $currentQuarter % 4;
                        } else {
                            $year = date('Y') + intval($currentQuarter / 4) - 1;
                            $quarter = 4;
                        }

                        $start = $this->get_dates_of_quarter('next', null, 'Y-m-d');
                        $end = $this->get_dates_of_quarter($quarter, $year, 'Y-m-d');

                        $firstDay = $start['start'];
                        $lastDay = $end['end'];
                        break;
                    case 'year':
                        $firstDay = (date("Y") + 1).'-01-01';
                        $lastDay = (date("Y") +  $config['value']).'-12-31';
                        break;
                }

                break;
        }

        if($firstDay !== false) {
            $fieldvalue = date('Y-m-d', strtotime($fieldvalue));

            return $fieldvalue >= $firstDay && $fieldvalue <= $lastDay;
        }

        return false;
    }
}

\ComplexCondition\ConditionPlugin::register('core', '\\ComplexCondition\\Plugin\\CoreConditionOperator');
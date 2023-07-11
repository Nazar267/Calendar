<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 18.06.15 16:21
 * You must not use this file without permission.
 */
namespace ComplexCondition\Plugin;

class CustomViewConditionOperator extends \ComplexCondition\ConditionPlugin
{
    public function getOperators($moduleName) {
        $adb = \PearDatabase::getInstance();

        $sql = 'SELECT cvid, viewname FROM vtiger_customview WHERE entitytype = ?';
        $result = $adb->pquery($sql, array($moduleName));

        $filters = array();
        while($row = $adb->fetchByAssoc($result)) {
            $filters[$row['cvid']] = $row['viewname'];
        }

        $operators = array(
            'within_cv' => array (
                'config' => array (
                    'customview' => array (
                        'type' => 'picklist',
                        'options' => $filters,
                        'label' => 'within Filter',
                    ),
                ),
                'label' => 'within filter',
                'fieldtypes' => array ('crmid'),
            ),
        );

        return $operators;
    }

    public function generateSQLCondition($key, $columnName, $config, $not) {
        $adb = \PearDatabase::getInstance();

        if(is_string($config)) {
            $config = array('customview' => $config);
        }

        $sql = 'SELECT entitytype from vtiger_customview WHERE cvid = '.intval($config['customview']);
        $result = $adb->query($sql);

        $queryGenerator = new \QueryGenerator($adb->query_result($result, 0, 'entitytype'), \Users::getActiveAdminUser());
        $queryGenerator->initForCustomViewById($config['customview']);
        $query = $queryGenerator->getQuery();
        $parts = preg_split('/FROM/i', $query);
        $sqlQuery = 'SELECT vtiger_crmentity.crmid as id_col FROM '.$parts[1];

        // default calculations
        switch($key) {
            case 'within_cv':
                return "".$columnName." " . ($not ? "!" : "" ) . " IN (".$sqlQuery.")";
                break;
        }

    }

    public function checkValue($context, $key, $fieldvalue, $config, $checkConfig) {
        $adb = \PearDatabase::getInstance();

        switch ($key) {
            case "within_cv":
                $sql = 'SELECT entitytype from vtiger_customview WHERE cvid = '.intval($config['customview']);
                $result = $adb->query($sql);

                $queryGenerator = new \QueryGenerator($adb->query_result($result, 0, 'entitytype'), \Users::getActiveAdminUser());

                $queryGenerator->initForCustomViewById($config['customview']);
                $query = $queryGenerator->getQuery();
                $parts = preg_split('/FROM/i', $query);
                $sqlQuery = 'SELECT vtiger_crmentity.crmid as id_col FROM '.$parts[1];
                $result = $adb->query($sqlQuery, true);

                while($row = $adb->fetchByAssoc($result)) {
                    if($fieldvalue == $row["id_col"]) {
                        return true;
                        break;
                    }
                }
        }

        return false;
    }
}

\ComplexCondition\ConditionPlugin::register('customview', '\\ComplexCondition\\Plugin\\CustomViewConditionOperator');
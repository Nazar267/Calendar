<?php
namespace RedooCalendar\Updater;

class Relation
{
    /**
     * Add a new relation
     *
     * @param $srcModulename
     * @param $relatedModulename
     * @param $methodName
     * @param $relatedTabLabel
     * @param string[] $actions
     * @param null $relatedFieldName
     */
    public static function add($srcModulename, $relatedModulename, $methodName, $relatedTabLabel, $actions = array('ADD', 'SELECT'), $relatedFieldName = null) {

        $remoteModule = \Vtiger_Module::getInstance($relatedModulename);
        $srcModule = \Vtiger_Module::getInstance($srcModulename);

        if(!empty($relatedFieldName)) {
            /**
             * @var $fieldModel \Vtiger_Field_Model
             */
            $fieldModel = \Vtiger_Field_Model::getInstance($relatedFieldName, $remoteModule);

            $fieldId = $fieldModel->getId();
        } else {
            $fieldId = null;
        }

        $srcModule->setRelatedList($remoteModule, $relatedTabLabel, $actions, $methodName, $fieldId);

    }

    /**
     * Remove a relation
     *
     * @param $srcModulename
     * @param $relatedModulename
     * @param $methodName
     * @param $relatedTabLabel
     */
    public static function remove($srcModulename, $relatedModulename, $methodName, $relatedTabLabel)  {

        $remoteModule = \Vtiger_Module::getInstance($relatedModulename);
        $srcModule = \Vtiger_Module::getInstance($srcModulename);

        $srcModule->unsetRelatedList($remoteModule, $relatedTabLabel, $methodName);

    }

    /**
     * Make sure, a relation is only created exactly once. Delete all additional equal relations
     *
     * @param $srcModulename
     * @param $relatedModulename
     * @param $methodName
     * @param $relatedTabLabel
     * @param string[] $actions
     * @param null $relatedFieldName
     */
    public static function uniqueRelation($srcModulename, $relatedModulename, $methodName, $relatedTabLabel, $actions = array('ADD', 'SELECT'), $relatedFieldName = null) {
        $remoteModule = \Vtiger_Module::getInstance($relatedModulename);
        $srcModule = \Vtiger_Module::getInstance($srcModulename);

        if(!empty($relatedFieldName)) {
            /**
             * @var $fieldModel \Vtiger_Field_Model
             */
            $fieldModel = \Vtiger_Field_Model::getInstance($relatedFieldName, $remoteModule);

            $fieldId = $fieldModel->getId();
        } else {
            $fieldId = null;
        }

        $sql = 'SELECT COUNT(relation_id) as num FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ? AND name = ? AND label = ?';
        $result = MODDBCheck::pquery($sql, array($srcModule->getId(), $remoteModule->getId(), $methodName, $relatedTabLabel));

        $row = MODDBCheck::fetchByAssoc($result);

        if($row['num'] == 1) {
            return;
        } elseif($row['num'] > 1) {
            self::remove($srcModulename, $relatedModulename, $methodName, $relatedTabLabel);
        }

        self::add($srcModulename, $relatedTabLabel, $methodName, $relatedTabLabel, $actions, $relatedFieldName);
    }

}

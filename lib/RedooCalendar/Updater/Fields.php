<?php
/**
 * 2021-03-31
 *  - Implement removeField
 * 2021-02-23
 *  - Implement addPicklist and addMultipicklist
 *  - fix case when block not exists
 *
 */
namespace RedooCalendar\Updater;

class Fields
{

    /**
     * Add a field to module, when field not already exists
     *
     * @param string $moduleName The name of the module, which will get the field
     * @param string $fieldName The name of the field
     * @param string $fieldLabel The label of the field, which is translateable in context of module
     * @param string $type The crm compatible type of column (Available values: text, number)
     * @param string $blockName [optional] Within this block this will be added. (Default = first block)
     */
    public static function addModuleField($moduleName, $fieldName, $fieldLabel, $type, $blockName = null) {
        $adb = \PearDatabase::getInstance();

        $sql = 'SELECT * FROM vtiger_field WHERE tabid = ? AND fieldname = ?';
        $result = $adb->pquery($sql, array(getTabid($moduleName), $fieldName));
        if($adb->num_rows($result) > 0) {
            return;
        }

        include_once('vtlib/Vtiger/Menu.php');
        include_once('vtlib/Vtiger/Module.php');

        // Welches Modul soll bearbeitet werden?
        $targetModuleName = $moduleName;
        $type = strtolower($type);

        $uitype = 1;
        $typeofdata = 'V~O';
        $colType = 'VARCHAR(100)';

        if($type == 'number') {
            $uitype = 7;
            $typeofdata = 'NN~O~12,4';
            $colType = 'DECIMAL(12,4)';
        }

        if(empty($uitype)) {
            echo $type.' not known<br/>';
            return;
        }
        // Welches Label soll das Feld bekommen?
        //$fieldLabel = 'Preisliste';

        // -------- ab hier nichts mehr anpassen !!!!
        $module = \Vtiger_Module::getInstance($targetModuleName);

        if(empty($module->basetable)) {
            if(class_exists('\\Vtiger_Cache') && method_exists('\\Vtiger_Cache', 'flushModuleCache')) {
                \Vtiger_Cache::flushModuleCache($targetModuleName);
            }

            $module = \Vtiger_Module::getInstance($targetModuleName);
        }

        if($blockName === null) {
            $blocks = \Vtiger_Block::getAllForModule($module);
            $block = $blocks[0];
        } else {
            $block = \Vtiger_Block::getInstance ($blockName, $module);

            if(!$block) {
                $block = new \Vtiger_Block();
                $block->label = $blockName;
                $module->addBlock($block);
            }
        }

        $field1 = new \Vtiger_Field();
        $field1->name = $fieldName;
        $field1->label= $fieldLabel;
        $field1->table = $module->basetable;
        $field1->column = $fieldName;
        $field1->columntype = $colType;
        $field1->uitype = $uitype;

        $field1->typeofdata = $typeofdata;
        $block->addField($field1);

    }

    public static function addModuleReferenceField($moduleName, $fieldName, $fieldLabel, $targetModuleNameArray, $blockName = null) {
        $adb = \PearDatabase::getInstance();

        $sql = 'SELECT * FROM vtiger_field WHERE tabid = ? AND fieldname = ?';
        $result = $adb->pquery($sql, array(getTabid($moduleName), $fieldName));
        if($adb->num_rows($result) > 0) {
            return;
        }

        // Welches Modul soll bearbeitet werden?
        $targetModuleName = $moduleName;

        // Welche Module sollen ausgewählt werden können?
        $relatedModules = $targetModuleNameArray;

        // -------- ab hier nichts mehr anpassen !!!!
        $module = \Vtiger_Module::getInstance($targetModuleName);

        if(empty($module->basetable)) {
            if(class_exists('\\Vtiger_Cache')&& method_exists('\\Vtiger_Cache', 'flushModuleCache')) {
                \Vtiger_Cache::flushModuleCache($targetModuleName);
            }
            $module = \Vtiger_Module::getInstance($targetModuleName);
        }

        if($blockName === null) {
            $blocks = \Vtiger_Block::getAllForModule($module);
            $block = $blocks[0];
        } else {
            $block = \Vtiger_Block::getInstance ($blockName, $module);

            if(!$block) {
                $block = new \Vtiger_Block();
                $block->label = $blockName;
                $module->addBlock($block);
            }
        }

        $field1 = new \Vtiger_Field();
        $field1->name = $fieldName;
        $field1->label= $fieldLabel;
        $field1->table = $module->basetable;
        $field1->column = $fieldName;
        $field1->columntype = 'VARCHAR(100)';
        $field1->uitype = 10;
        $field1->typeofdata = 'V~O';
        $block->addField($field1);
        $field1->setRelatedModules($relatedModules);

    }

    public static function deleteModuleField($moduleName, $fieldName)
    {
        $module = \Vtiger_Module::getInstance($moduleName);
        if ($module) {
            $field = \Vtiger_Field::getInstance($fieldName, $module);
            if ($field) $field->delete();
        }
    }

    public static function addPicklist($modules, $fieldName, $fieldLabel, $options, $blockName = null) {
        $adb = \PearDatabase::getInstance();

        if(!is_array($modules)) {
            $modules = array($modules);
        }

        $initValues = false;
        foreach($modules as $index => $targetModuleName) {
            $sql = 'SELECT * FROM vtiger_field WHERE tabid = ? AND fieldname = ?';
            $result = $adb->pquery($sql, array(getTabid($targetModuleName), $fieldName));
            if($adb->num_rows($result) > 0) {
                continue;
            }

            // -------- ab hier nichts mehr anpassen !!!!
            $module = \Vtiger_Module::getInstance($targetModuleName);

            if($blockName === null) {
                $blocks = \Vtiger_Block::getAllForModule($module);
                $block = $blocks[0];
            } else {
                $block = \Vtiger_Block::getInstance ($blockName, $module);

                if(!$block) {
                    $block = new \Vtiger_Block();
                    $block->label = $blockName;
                    $module->addBlock($block);
                }
            }

            $field1 = new \Vtiger_Field();
            $field1->name = $fieldName;
            $field1->label= $fieldLabel;
            $field1->table = $module->basetable;
            $field1->column = $fieldName.$index;
            $field1->columntype = 'VARCHAR(100)';
            $field1->uitype = 16;

            $field1->typeofdata = 'V~O';
            $block->addField($field1);

            if($initValues === false) {
                $field1->setPicklistValues( $options );
                $initValues = true;
            }

        }
    }
    public static function addMultipicklist($modules, $fieldName, $fieldLabel, $options, $blockName = null) {
        $adb = \PearDatabase::getInstance();

        if(!is_array($modules)) {
            $modules = array($modules);
        }

        $initValues = false;
        foreach($modules as $index => $targetModuleName) {
            $sql = 'SELECT * FROM vtiger_field WHERE tabid = ? AND fieldname = ?';
            $result = $adb->pquery($sql, array(getTabid($targetModuleName), $fieldName));
            if($adb->num_rows($result) > 0) {
                continue;
            }

            // -------- ab hier nichts mehr anpassen !!!!
            $module = \Vtiger_Module::getInstance($targetModuleName);

            if($blockName === null) {
                $blocks = \Vtiger_Block::getAllForModule($module);
                $block = $blocks[0];
            } else {
                $block = \Vtiger_Block::getInstance ($blockName, $module);

                if(!$block) {
                    $block = new \Vtiger_Block();
                    $block->label = $blockName;
                    $module->addBlock($block);
                }
            }

            $field1 = new \Vtiger_Field();
            $field1->name = $fieldName;
            $field1->label= $fieldLabel;
            $field1->table = $module->basetable;
            $field1->column = $fieldName.$index;
            $field1->columntype = 'VARCHAR(100)';
            $field1->uitype = 33;

            $field1->typeofdata = 'V~O';
            $block->addField($field1);

            if($initValues === false) {
                $field1->setPicklistValues( $options );
                $initValues = true;
            }

        }
    }

    /**
     * Remove a field from module
     *
     *
     * @param string $moduleName The name of the module, which will loose the field
     * @param string $fieldName The name of the field, which will removed
     */
    public function removeField($moduleName, $fieldName) {
        $field = \Vtiger_Field_Model::getInstance($fieldName, \Vtiger_Module_Model::getInstance($moduleName));
        $field->delete();
    }
}

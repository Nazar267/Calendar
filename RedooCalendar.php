<?php


class RedooCalendar
{

    const DATABASE_EXCEPTION_MESSAGE = 'Database error, please contact administrator';
    const EXCEPTION_MESSAGE = 'Some error occurred, please contact administrator';

    public function initialize_module()
    {
        ob_start();

        // Check DB
        $this->checkDB();

        ob_end_clean();
    }

    public function checkDB()
    {
        require_once(dirname(__FILE__) . '/MODCheckDB.php');
    }

    /**
     * Invoked when special actions are performed on the module.
     * @param String Module name
     * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
     */
    function vtlib_handler($modulename, $event_type)
    {
        global $adb;

        if ($event_type == 'module.postinstall') {
            $this->initialize_module();

            // TODO Handle post installation actions
        } else if ($event_type == 'module.disabled') {

            // TODO Handle actions when this module is disabled.
        } else if ($event_type == 'module.enabled') {

            $this->initialize_module();

            // TODO Handle actions when this module is enabled.
        } else if ($event_type == 'module.preuninstall') {

            // TODO Handle actions when this module is about to be deleted.
        } else if ($event_type == 'module.preupdate') {
            // TODO Handle actions before this module is updated.
        } else if ($event_type == 'module.postupdate') {
            // TODO Handle actions after this module is updated.

            $this->initialize_module();
        }
    }

    function vtranslate($key, $moduleName = '') {
        die('asd');
        $args = func_get_args();
        $formattedString = call_user_func_array(array('Vtiger_Language_Handler', 'getTranslatedString'), $args);
        array_shift($args);
        array_shift($args);
        if (is_array($args) && !empty($args)) {
            $formattedString = call_user_func_array('vsprintf', array($formattedString, $args));
        }
        return $formattedString;
    }
}
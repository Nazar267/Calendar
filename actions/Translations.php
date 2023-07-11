<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Base\VtUtils;
use RedooCalendar\Model\Subscribe;

class RedooCalendar_Translations_Action extends BaseActionController
{
    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $language = Vtiger_Language_Handler::getLanguage();
        $translations = Vtiger_Language_Handler::getModuleStringsFromFile($language, 'RedooCalendar');

        echo json_encode([
            'status' => true,
            'language' => $language,
            'translations' => $translations['jsLanguageStrings']
        ]);

        return;
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateReadAccess();
    }

}

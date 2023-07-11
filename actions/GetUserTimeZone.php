<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\ActionController\BaseActionController;

class RedooCalendar_GetUserTimeZone_Action extends BaseActionController
{
    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        echo json_encode([
            'status' => true,
            'user_time_zone' => $this->getUser()->get('time_zone'),
        ]);
        return;
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateReadAccess();
    }

}

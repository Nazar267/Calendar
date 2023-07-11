<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Model\Subscribe;

class RedooCalendar_TaskDone_Action extends BaseActionController
{
    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $taskid = $request->get("id");

        $sql = "UPDATE vtiger_activity SET status='done' WHERE activityid={$taskid}";
        \PearDatabase::getInstance()->pquery($sql);

        echo json_encode([
            'status' => true,
            'event' => null,
            'message' => self::t('Task Successfully Finished')
        ]);
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateReadAccess();
    }

}

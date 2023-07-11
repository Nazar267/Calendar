<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Model\Subscribe;
use RedooCalendar\Base\VtUtils;

class RedooCalendar_GetCompletedTasks_Action extends BaseActionController
{
    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $sql = "SELECT activityid FROM vtiger_activity WHERE status='done'";

        //forming array of ids
        $tasks = array_map(function($task){
            return $task['activityid'];
        }, VtUtils::fetchRows($sql));

        echo json_encode($tasks);
    }
    
    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateReadAccess();
    }

}

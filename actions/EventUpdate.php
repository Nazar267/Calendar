<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Updater\Database\Table;

class RedooCalendar_EventUpdate_Action extends BaseActionController
{

    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        try {
            Database::startTransaction();
            $event = $this->getConnector()->updateEvent($request);

            if(vtlib_isModuleActive('HausmeisterTasks'))
            {
                $hausmeistertasksid = \FlexSuite\Database::fetchRows("SELECT hausmeistertasksid FROM vtiger_hausmeistertasks WHERE calendar_id = ?;",$event["id"])[0]["hausmeistertasksid"];

                if($hausmeistertasksid)
                {
                    $this->updateHausmeisterTask($hausmeistertasksid, $event);
                }
            }

             Database::commitTransaction();

            echo json_encode([
                'status' => true,
                'event' => $event->getData(),
                'message' => self::t('Event Successfully Updated')
            ]);
        } catch (DatabaseException $databaseException) {
            Database::rollbackTransaction();
            echo json_encode([
                'status' => false,
                'test' => $databaseException->getMessage(),
                'message' => self::t(RedooCalendar::DATABASE_EXCEPTION_MESSAGE)
            ]);
        } catch (\Exception $exception) {

            Database::rollbackTransaction();
            echo json_encode([
                'status' => false,
                'message' => self::t(RedooCalendar::EXCEPTION_MESSAGE)
            ]);
        }

        return;
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateReadAccess();
    }

    public function updateHausmeisterTask($hausmeistertasksid, $event)
    {
        $hausmeistertask = \FlexSuite\Database::fetchRows("SELECT * FROM vtiger_hausmeistertasks WHERE hausmeistertasksid = ?;", $hausmeistertasksid);

        $description = \FlexSuite\Database::fetchRows("SELECT description FROM vtiger_crmentity WHERE crmid = ?", $event["id"])[0]["description"];

        if($hausmeistertask[0]["hausmeistertasks_title"] != $event["subject"])
        {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_hausmeistertasks SET hausmeistertasks_title = ? WHERE hausmeistertasksid = ?;", $event["subject"], $hausmeistertasksid);
        }
        if($hausmeistertask[0]["description"] != $description)
        {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_hausmeistertasks SET description = ? WHERE hausmeistertasksid = ?;", $description, $hausmeistertasksid);
        }
        if($hausmeistertask[0]["start_date"] != $event["date_start"])
        {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_hausmeistertasks SET start_date = ? WHERE hausmeistertasksid = ?;", $event["date_start"], $hausmeistertasksid);
        }
        if($hausmeistertask[0]["start_working_time"] != $event["time_start"])
        {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_hausmeistertasks SET start_working_time = ? WHERE hausmeistertasksid = ?;", $event["time_start"], $hausmeistertasksid);
        }
        if($hausmeistertask[0]["end_date"] != $event["date_end"])
        {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_hausmeistertasks SET end_date = ? WHERE hausmeistertasksid = ?;", $event["date_end"], $hausmeistertasksid);
        }
        if($hausmeistertask[0]["end_working_time"] != $event["time_end"])
        {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_hausmeistertasks SET end_working_time = ? WHERE hausmeistertasksid = ?;", $event["time_end"], $hausmeistertasksid);
        }

        return true;
    }

}

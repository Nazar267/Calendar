<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Model\Subscribe;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;

class RedooCalendar_CalendarSetVisible_Action extends BaseActionController
{
    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        try {
            Database::startTransaction();
            $subscribe = new Subscribe();

            $subscribe->fetchByCalendarId($request->get('id'), $request->get('connector'));
            $subscribe->setVisible($request->get('visible') == 'true');

            $subscribe->save();
            Database::commitTransaction();

            echo json_encode([
                'status' => true,
            ]);
        } catch (DatabaseException $databaseException) {
            Database::rollbackTransaction();
            echo json_encode([
                'status' => false,
                'message' =>  self::t(RedooCalendar::DATABASE_EXCEPTION_MESSAGE)
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

}

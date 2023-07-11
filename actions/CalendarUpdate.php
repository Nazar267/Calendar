<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Model\Calendar;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\Database;

class RedooCalendar_CalendarUpdate_Action extends BaseActionController
{

    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        try {
            Database::startTransaction();

            $calendar = new Calendar();
            $calendar->setId($request->get('id'));
            $calendar->setTitle($request->get('title'));
            $calendar->setColor($request->get('color'));
            $calendar->setAccessMode($request->get('access_mode'));
            $calendar->setHideEventDetails($request->get('hide_event_details'));

            $calendar = $this->getConnector()->updateCalendar($calendar);

            $subscription = new RedooCalendar\Model\Subscribe();

            $subscription->fetchByCalendarId($request->get('id'), $this->getConnector()->getId());
            $subscription->setColor($request->get('color'));
            $subscription->save();

            Database::commitTransaction();

            echo json_encode([
                'status' => true,
                'message' => self::t('Calendar Successfully Updated')
            ]);
        } catch (DatabaseException $databaseException) {
            Database::rollbackTransaction();
            echo json_encode([
                'status' => false,
                'message' => self::t(RedooCalendar::DATABASE_EXCEPTION_MESSAGE)
            ]);
        } catch (\Exception $exception) {
            Database::rollbackTransaction();
            echo json_encode([
                'status' => false,
                'test' => self::t(RedooCalendar::EXCEPTION_MESSAGE)
            ]);
        }
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateReadAccess();
    }

}

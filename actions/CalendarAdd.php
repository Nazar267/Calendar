<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Model\Subscribe;

class RedooCalendar_CalendarAdd_Action extends BaseActionController
{
    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        try {
            Database::startTransaction();

            $calendar = $this->getConnector()->createCalendar($request);

            $subscribe = new Subscribe();
            $subscribe->setCalendarId($calendar->getId());
            $subscribe->setConnectionId($request->get('connector'));
            $subscribe->setOwner(true);
            $subscribe->setColor($request->get('color'));
            $subscribe->setVisible(true);
            $subscribe->save();

            Database::commitTransaction();

            echo json_encode([
                'status' => true,
                'calendar' => $calendar->getData(),
                'connector' => $request->get('connector'),
                'message' => self::t('Calendar Successfully Added')
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

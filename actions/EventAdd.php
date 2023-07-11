<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;

class RedooCalendar_EventAdd_Action extends BaseActionController
{

    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        try {
            Database::startTransaction();
            $event = $this->getConnector()->createEvent($request);

            Database::commitTransaction();

            echo json_encode([
                'status' => true,
                'event' => $event->getData(),
                'message' => self::t('Event Successfully Added'),
            ]);
        } catch (DatabaseException $databaseException) {
            Database::rollbackTransaction();
            echo json_encode([
                'status' => false,
                'message' =>  self::t(RedooCalendar::DATABASE_EXCEPTION_MESSAGE)
            ]);
        } catch (\Exception $exception) {
            var_dump($exception);
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

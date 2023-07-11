<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Model\Calendar;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\Database;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Source\DefaultConnections;
use RedooCalendar\Source\ReadonlyConnections;

class RedooCalendar_CalendarDelete_Action extends BaseActionController
{

    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        try {
            Database::startTransaction();
            $connector = $this->getConnector();

            if (in_array($connector->getData()['connector'], ReadonlyConnections::getKeys())) {
                echo json_encode([
                    'readonly' => true,
                    'message' => self::t('Read Only Calendar')
                ]);
                return;
            }

            $this->getConnector()->deleteCalendar($request->get('calendar_id'));

            Database::commitTransaction();

            echo json_encode([
                'status' => true,
                'connector' => $request->get('connector'),
                'message' => self::t('Calendar Deleted Successfully')
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

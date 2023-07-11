<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;

class RedooCalendar_EventDelete_Action extends BaseActionController
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

            if ($connector->getData('connector')['connector'] == 'custom') {
                echo json_encode([
                    'status' => false,
                    'message' => 'Event cannot be deleted'
                ]);
                return;
            }


            $calendar = $connector->getCalendar($request->get('calendar_id'));

            if($calendar->getData()['id'] == null)
            {

                $idUser = $this->getConnector()->getData()['user_id'];

                $calendar = $this->getConnector()->getCalendar('user_'.$idUser);

            }

            $event = $connector->getEvent($calendar, $request->get('id'));

            $this->getConnector()->deleteEvent($event);

            Database::commitTransaction();

            echo json_encode([
                'status' => true,
                'message' => self::t('Event Successfully Deleted')
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

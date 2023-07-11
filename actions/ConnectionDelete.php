<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Model\Connection;

class RedooCalendar_ConnectionDelete_Action extends BaseActionController
{
    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        try {
            Database::startTransaction();

            $connection = new Connection();
            $connection->fetch((int)$request->get('connection_id'));
            $connection->delete();

            Database::commitTransaction();

            echo json_encode([
                'status' => true,
                'message' => self::t('Connection Successfully Deleted')
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

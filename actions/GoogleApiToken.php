<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Base\Connection\Connection as ConnectionConnection;
use RedooCalendar\Model\Connection;

class RedooCalendar_GoogleApiToken_Action extends BaseActionController
{
    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        global $site_URL;

        try {
            Database::startTransaction();

            $googleConnection = new ConnectionConnection($request->get('state')['connection_id']);
            $connector = $googleConnection->getConnector();
            $accessToken = $connector->fetchAccessTokenFromCode($request->get('code'));

            $googleConnection->setSettings(json_encode([
                'access_token' => $request->get('code'),
                'scope' => $request->get('scope')
            ]));
            $googleConnection->save();
            Database::commitTransaction();

            header("Location: " . $site_URL . "index.php?module=RedooCalendar&view=List");
        } catch (DatabaseException $databaseException) {
            Database::rollbackTransaction();
            echo json_encode([
                'status' => false,
                'message' => 'Database error, please contact administrator'
            ]);
        } catch (\Exception $exception) {
            Database::rollbackTransaction();
            echo json_encode([
                'status' => false,
                'message' => 'Some error occurred, please contact administrator',
                'exception' => $exception->getMessage(),
            ]);
        }

        return;
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateReadAccess();
    }
}

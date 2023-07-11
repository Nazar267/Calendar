<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Base\Collection\BaseCollection;
use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\Connection\ConnectorPlugin\ConnectorPluginInterface;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;

class RedooCalendar_GetCalendar_Action extends BaseActionController
{

    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $user = Users_Record_Model::getCurrentUserModel();
        $connectorCollection = Connection::getAvailableConnections($user);

        $collections = [];

        /** @var Connection $connector */
        foreach ($connectorCollection as $connector) {
            /** @var array $collection */
            $collection = $connector->getConnector()
                ->getSubscribedCalendarCollection();

            $collections[] = [
                'connector' => $connector->getData(),
                'collection' => $collection
            ];
        }

        echo json_encode(['connectors' => $collections]);
        return;
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateReadAccess();
    }

}

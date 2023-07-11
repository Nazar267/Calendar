<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Base\Collection\BaseCollection;
use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\Connection\ConnectorPlugin\ConnectorPluginInterface;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;
use RedooCalendar\Source\DefaultConnections;

class RedooCalendar_GetSubscribedCalendars_Action extends BaseActionController
{

    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $connectorCollection = Connection::getAvailableConnections($this->getUser());
        $collections = [];
        $defaultConnections = DefaultConnections::getData();

        /** @var Connection $connection */
        foreach ($connectorCollection as $connection) {
            if (isset($defaultConnections[$connection->getConnectionProcessor()])) {
                unset($defaultConnections[$connection->getConnectionProcessor()]);
            }
        }

        foreach ($defaultConnections as $code => $title) {
            $connection = new \RedooCalendar\Model\Connection();
            $connection->setData('user_id', $this->getUser()->getId());
            $connection->setData('title', html_entity_decode($title));
            $connection->setData('code', $code);
            $connection->setData('accessmode', 'private');
            $connection->setData('connector', $code);
            $connection->setData('default', true);
            $connection->save();
        }

        $connectorCollection = Connection::getAvailableConnections($this->getUser());

        /** @var Connection $connection */
        foreach ($connectorCollection as $connection) {

            try {
                // Readd Unsubscribed calendars
                if ($connection->getConnectionProcessor() === 'vtiger_event') {
                    $calendarId = 'user_' . $this->getUser()->getId();
                    $subscription = new RedooCalendar\Model\Subscribe();
                    $subscription->fetchByCalendarId($calendarId, $connection->getId());
                    if (!$subscription->getId()) {
                        $subscription->setData('calendar_id', $calendarId);
                        $subscription->setData('connection_id', $connection->getId());
                        $subscription->setData('owner', true);
                        $subscription->setData('visible', true);
                        $subscription->save();
                    }
                }

                // Readd Unsubscribed calendars
                if ($connection->getConnectionProcessor() === 'vtiger_task') {
                    $calendarId = 'task_user_' . $this->getUser()->getId();
                    $subscription = new RedooCalendar\Model\Subscribe();
                    $subscription->fetchByCalendarId($calendarId, $connection->getId());

                    if (!$subscription->getId()) {
                        $subscription->setData('calendar_id', $calendarId);
                        $subscription->setData('connection_id', $connection->getId());
                        $subscription->setData('owner', true);
                        $subscription->setData('visible', true);
                        $subscription->save();
                    }
                }

                /** @var array $collection */
                $collection = $connection->getConnector()
                    ->getSubscribedCalendarCollection();

                if (!empty($collection)) {

                    $collections[] = [
                        'connector' => $connection->getData(),
                        'collection' => $collection
                    ];
                }

            } catch (\Exception $exp) {
                var_dump($exp->getMessage());
                var_dump($exp->getTrace());
            }
        }

        echo json_encode(['connectors' => $collections]);
        return;
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateReadAccess();
    }
}

<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Model\Subscribe;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;

class RedooCalendar_CalendarSubscribe_Action extends BaseActionController
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

            $connectorData = explode(':', $request->get('calendar_id'));
            $connector = $connectorData[0];
            $calendarId = $connectorData[1];
            $subscribe->setData('owner', \Users_Record_Model::getCurrentUserModel()->getId());
            $subscribe->setData('calendar_id', $calendarId);
            $subscribe->setData('connection_id', $connector);
            $subscribe->setData('visible', 1);
            $subscribe->setData('color', $request->get('color'));

            $subscribe->save();
            Database::commitTransaction();

            $connectorCollection = Connection::getAvailableConnections($this->getUser());

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

            echo json_encode([
                'status' => true,
                'subscribe' => $subscribe->toArray(),
                'connectors' => $collections,
                'message' => self::t('Calendar Subscribed Successfully')
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

<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use ComplexCondition\ConditionCheck;
use ComplexCondition\ConditionMysql;
use ComplexCondition\ConditionPlugin;
use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Base\VTEntity;
use RedooCalendar\Base\VtUtils;
use RedooCalendar\ConnectorPlugins\Custom;
use RedooCalendar\Model\Connection;
use RedooCalendar\Model\GeneratedCalendar;
use RedooCalendar\Model\Subscribe;

class RedooCalendar_CalendarGenerate_Action extends BaseActionController
{

    const CONNECTOR = 'custom';

    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        try {
            Database::startTransaction();

            $connectionEntity = new Connection();
            $connectionEntity->fetchByProcessorForUser(self::CONNECTOR, $this->getUser());

            $connection = \RedooCalendar\Base\Connection\Connection::GetInstance($connectionEntity->getId());

            /** @var Custom $connector */
            $connector = $connection->getConnector();
            $baseQuery = $connector->getBaseQuery($request->get('form'));

            $module = Vtiger_Module_Model::getInstance($request->get('form')['module_id']);
            $objMySQL = new ConditionMysql($module->getName(), VTEntity::getDummy());

            $objMySQL->preserveVariables(true);

            $sqlCondition = $objMySQL->parse($request->get('settings')['condition']);

            if (strlen($sqlCondition) > 3) {
                $sqlCondition .= " AND vtiger_crmentity.deleted = 0";
            } else {
                $sqlCondition .= "vtiger_crmentity.deleted = 0";
            }

            $sqlQuery = $baseQuery . $sqlCondition;

            $generatedCalendar = new GeneratedCalendar();
            $generatedCalendar->setUserId($this->getUser()->getId());
            $generatedCalendar->setSql($sqlQuery);
            $generatedCalendar->setConfig(json_encode($request->get('form')));
            $generatedCalendar->setFieldConfig(json_encode($request->get('settings')['condition']));
            $generatedCalendar->setTitle($request->get('form')['title']);
            $generatedCalendar->setAccessMode($request->get('form')['access_mode']);
            $generatedCalendar->save();

            $generatedCalendar->unsetData('sql');


            $subscribe = new Subscribe();

            $subscribe->setCalendarId($generatedCalendar->getId());
            $subscribe->setOwner(\Users_Record_Model::getCurrentUserModel()->getId());
            $subscribe->setConnectionId($connection->getId());
            $subscribe->setColor($request->get('color'));
            $subscribe->setVisible(true);

            $subscribe->save();

            $generatedCalendar->setVisible(true);
            $generatedCalendar->initRelations();
            $connector->loadEvents($generatedCalendar);

            Database::commitTransaction();

            echo json_encode([
                'status' => true,
                'calendar' => $generatedCalendar->getAsArray(),
                'connector' => $connector->getId(),
                'message' => self::t('Calendar Successfully Generated')
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

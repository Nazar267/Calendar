<?php

namespace RedooCalendar\Base\Connection;

use RedooCalendar\Base\Connection\ConnectorPlugin\ConnectorPluginInterface;
use RedooCalendar\Base\VtUtils;
use RedooCalendar\Helper\Redoo;
use RedooCalendar\Model\Calendar;

/**
 * Class Connection
 * @package RedooCalendar\Base\Connection
 */
class Connection
{
    private $_connectionCode = null;
    private $_connectionId = null;
    private $_Data = null;
    private $_Connector = null;
    private $_Calendar = null;
    private static $Instances = [];

    private function __construct($connectionId)
    {
        $this->_connectionId = $connectionId;
    }

    public static function GetInstance(int $connectionId): Connection
    {
        if (isset(self::$Instances[$connectionId]) === false) self::$Instances[$connectionId] = new Connection($connectionId);
        return self::$Instances[$connectionId];
    }

    public function supports($key)
    {
        return $this->getConnector()->supports($key);
    }

    public function setCalendar(Calendar $calendar)
    {
        $this->_Calendar = $calendar;
    }

    public function getConnector(): ConnectorPluginInterface
    {
        if ($this->_Connector !== null) return $this->_Connector;

        $data = $this->getData();
        $connectionKey = $this->getConnectorKey();

        //var_dump($connectionKey);

        $className = '\\RedooCalendar\\ConnectorPlugins\\' . $connectionKey;
        $file = realpath(dirname(__FILE__) . '/../../../../extends/connectors/' . $connectionKey . '.inc.php');
        require_once($file);

        $this->_Connector = new $className($data);

        return $this->_Connector;
    }

    public function getId()
    {
        return $this->getData()['id'];
    }

    public function getServiceModule()
    {
        return $this->getConnector()->getServiceModule();
    }

    private function getConnectorKey()
    {
        $data = $this->getData();

        return ucfirst(Redoo::underscoreToCamelCase(preg_replace('/[^a-zA-Z0-9_]/', '', $data['connector'])));
    }

    public function getCalendarData(Calendar $calendar)
    {
        $connector = $this->getConnector();
        return $connector->getCalendarData($calendar);
    }

    public function getAvailableCalendars()
    {
        $connector = $this->getConnector();
        $calendars = $connector->getCalendars($this);

        $return = array();
        foreach ($calendars as $calendar) {
            $tmp = new Calendar($calendar['id'], $calendar['title']);

            $tmp->setConnection($this);

            $return[] = $tmp;
        }

        return $return;
    }

    public function getData()
    {
        if ($this->_Data !== null) return $this->_Data;

        $this->_Data = VtUtils::fetchByAssoc('SELECT * FROM vtiger_redoocalendar_connections WHERE id = ?', $this->_connectionId);

        $this->_Data['settings'] = VtUtils::json_decode(html_entity_decode($this->_Data['settings']));

        return $this->_Data;
    }

    public function getSettings()
    {
        $data = $this->getData();

        return $data['settings'];
    }

    public function getTitle()
    {
        $data = $this->getData();

        return $data['title'];
    }

    public static function getAllCalendars()
    {
        $connections = \RedooCalendar\Connection::getAvailableConnections();

        $calendars = array();
        foreach ($connections as $connection) {
            $calendars[] = array(
                'group' => $connection->getTitle(),
                'module' => $connection->getServiceModule(),
                'calendars' => $connection->getAvailableCalendars()
            );
        }

        return $calendars;
    }

    public static function getAvailableConnections(\Users_Record_Model $user)
    {
        $assignCondition = array();
        $assignCondition[] = 'accessmode = "private" AND user_id = ' . $user->getId();

        $assignCondition2 = array();
        $assignCondition2[] = 'type = "Users" AND typeid = ' . $user->getId();
        $assignCondition2[] = 'type = "Roles" AND typeid = ' . substr($user->getRole(), 1) . '';

        require('user_privileges/user_privileges_' . $user->getId() . '.php');

        if (!empty($current_user_groups)) {
            $assignCondition2[] = 'type = "Groups" AND typeid IN (' . implode(',', $current_user_groups) . ')';
        } else {
            $current_user_groups = \Users_Record_Model::getUserGroups($user->getId());

            if (!empty($current_user_groups)) {
                $assignCondition2[] = 'type = "Groups" AND typeid IN (' . implode(',', $current_user_groups) . ')';
            }
        }
        $assignCondition[] = 'accessmode = "share" AND ((' . implode(') OR (', $assignCondition2) . '))';
        $assignCondition[] = 'accessmode = "public"';

        $sql = '
            SELECT vtiger_redoocalendar_connections.id
            FROM vtiger_redoocalendar_connections 
                LEFT JOIN vtiger_redoocalendar_connection_permission 
                    ON (vtiger_redoocalendar_connection_permission.connectionid = vtiger_redoocalendar_connections.id)
            WHERE (' . implode(') OR (', $assignCondition) . ')
      /*AND vtiger_redoocalendar_connections.user_id = ' . $user->getId() . '*/
            GROUP BY vtiger_redoocalendar_connections.id';

      $result = VtUtils::fetchRows($sql);

        $connections = array();
        foreach ($result as $row) {
            $connections[] = Connection::GetInstance($row['id']);
        }

        return $connections;
    }

    public function getCode(): string
    {
        $data = $this->getData();
        return $data['code'];
    }

    public function getConnectionProcessor(): string
    {
        $data = $this->getData();
        return $data['connector'];
    }


}

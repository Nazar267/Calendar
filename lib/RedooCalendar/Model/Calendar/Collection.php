<?php

namespace RedooCalendar\Model\Calendar;

use RedooCalendar\Base\Collection\BaseCollection;
use RedooCalendar\Base\Connection\ConnectorPlugin;
use RedooCalendar\Base\Model\BaseModel;
use RedooCalendar\Model\Calendar;

/**
 * CalendarCollection
 * @package RedooCalendar\Model\Calendar
 */
class Collection extends BaseCollection
{
    protected $model = Calendar::class;

    public function getSharedCalendars(array $connection)
    {
        $modelClass = $this->model;
        $user = \Users_Record_Model::getCurrentUserModel();

        $rows = $this->_table::pquery('
            select vtiger_redoocalendar_calendar.*,
                   vrc.user_id as owner_id
            from vtiger_redoocalendar_calendar
                left join vtiger_redoocalendar_connections vrc on vtiger_redoocalendar_calendar.connection_id = vrc.id
                left join vtiger_redoocalendar_calendar_share vrcs on vtiger_redoocalendar_calendar.id = vrcs.calendar_id
            where vtiger_redoocalendar_calendar.connection_id = ?
                or vrc.connector = ? and vtiger_redoocalendar_calendar.access_mode = \'public\'
                or vrc.connector = ? and vtiger_redoocalendar_calendar.access_mode = \'share\' and vrcs.user_id = ?
        ',
            [
                $connection['id'],
                $connection['connector'],
                $connection['connector'],
                $user->getId()
            ]
        );

        foreach ($rows as $row) {
            /** @var BaseModel $model */
            $model = new $modelClass();
            $model->setData($row);
            $model->setOwner($user->getId() == $row['owner_id']);
            $model->initRelations();
            $this->setItem($model);
        }
        return $this;
    }
}
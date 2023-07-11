<?php

namespace RedooCalendar\Model\CalendarShare;

use RedooCalendar\Base\Collection\BaseCollection;
use RedooCalendar\Base\Connection\ConnectorPlugin;
use RedooCalendar\Base\Model\BaseModel;
use RedooCalendar\Model\Calendar;
use RedooCalendar\Model\CalendarShare;

/**
 * CalendarCollection
 * @package RedooCalendar\Model\Calendar
 */
class Collection extends BaseCollection
{
    protected $model = CalendarShare::class;

    public function getSharedUsers(int $calendarId)
    {
        $this->fetch([
            [
                'column' => 'calendar_id',
                'condition' => '=',
                'value' => $calendarId
            ]
        ]);
        return $this;
    }

    public function getUserIds(): array
    {
        $result = [];
        /** @var BaseModel $item */
        foreach ($this->getItems() as $item) {
            $result[] = $item->getUserId();
        }

        return $result;
    }
}
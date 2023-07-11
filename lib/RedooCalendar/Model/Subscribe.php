<?php

namespace RedooCalendar\Model;

use RedooCalendar\Base\Model\BaseModel;

class Subscribe extends BaseModel
{
    static $_tableName = 'subscribe';

    /**
     * Fetch by calendar and user
     *
     * @param string $calendarId
     * @param int $connectionId
     * @return BaseModel
     */
    public function fetchByCalendarId(string $calendarId, int $connectionId): BaseModel
    {

        $data = $this->_table->fetchRows([
            'calendar_id = \'' . $calendarId . '\'',
            'connection_id = ' . $connectionId
        ]);
        if (isset($data[0])) {
            $this->setData($data[0]);
        }

        return $this;
    }
}

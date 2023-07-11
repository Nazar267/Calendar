<?php

namespace RedooCalendar\Model;

use RedooCalendar\Base\Model\BaseModel;
use RedooCalendar\Model\Base\CalendarInterface;
use RedooCalendar\Model\Event\Collection as EventCollection;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;

class Calendar extends BaseModel implements CalendarInterface
{
    static $_tableName = 'calendar';

    protected $relations = [
        'has_many' => [
            'events' => [
                'class' => EventCollection::class,
                'local_column' => 'id',
                'remote_column' => 'calendar_id'
            ],
            'subscribe' => [
                'class' => SubscribeCollection::class,
                'local_column' => 'id',
                'remote_column' => 'calendar_id'
            ]
        ]
    ];
}
<?php

namespace RedooCalendar\Model\Event;

use RedooCalendar\Base\Collection\BaseCollection;
use RedooCalendar\Model\Event;

/**
 * EventCollection
 * @package RedooCalendar\Model\Event
 */
class Collection extends BaseCollection
{
    protected $model = Event::class;

}
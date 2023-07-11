<?php

namespace RedooCalendar\Model\Subscribe;

use RedooCalendar\Base\Collection\BaseCollection;
use RedooCalendar\Base\Model\BaseModel;
use RedooCalendar\Model\Subscribe;

/**
 * SubscribeCollection
 * @package RedooCalendar\Model\Subscribe
 */
class Collection extends BaseCollection
{
    protected $model = Subscribe::class;

    public function getCalendarIds(): array
    {
        $ids = [];
        /** @var BaseModel $item */
        foreach ($this->getItems() as $item) {
            $ids[] = $item->getCalendarId();
        }
        return $ids;
    }
}
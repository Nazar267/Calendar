<?php

namespace RedooCalendar\Model\GeneratedCalendar;

use RedooCalendar\Base\Collection\BaseCollection;
use RedooCalendar\Base\Collection\CollectionInterface;
use RedooCalendar\Base\Collection\Item\CollectionItemInterface;
use RedooCalendar\Base\Model\BaseModel;
use RedooCalendar\Model\GeneratedCalendar;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;


/**
 * CalendarCollection
 * @package RedooCalendar\Model\Calendar
 */
class Collection extends BaseCollection
{
    protected $model = GeneratedCalendar::class;

    /**
     * Get collection items as array
     *
     * @return array
     */
    public function getItemsAsArray(): array
    {
        $resultArray = [];

        /** @var GeneratedCalendar $item */
        foreach ($this->getItems() as $item) {

            $_item = $item->getData();
            $_item['relations'] = [];

            /** @var CollectionItemInterface $relation */
            foreach ($item->relations as $key => $relation) {
                $_item['relations'][$key] = $relation->getItemsAsArray();
            }

            $resultArray[] = $_item;
        }
        return $resultArray;
    }
}
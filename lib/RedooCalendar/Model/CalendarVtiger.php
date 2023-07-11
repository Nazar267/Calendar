<?php

namespace RedooCalendar\Model;

use RedooCalendar\Base\Model\BaseExternalModel;
use RedooCalendar\Model\EventVtiger;
use RedooCalendar\Model\Base\CalendarInterface;
use RedooCalendar\Model\CalendarVtiger\Collection as CalendarVtigerCollection;
use RedooCalendar\Model\EventVtiger\Collection as EventVtigerCollection;

class CalendarVtiger extends BaseExternalModel implements CalendarInterface
{
    protected $relations;
    protected $collection = CalendarVtigerCollection::class;

    public function __construct()
    {
        $this->relations['events'] = new EventVtigerCollection();
        parent::__construct();
    }

    /**
     * Get events collection
     *
     * @return EventVtigerCollection
     */
    public function getEvents(): EventVtigerCollection
    {
        return $this->relations['events'];
    }

    /**
     * Add event to events collection
     *
     * @param EventVtiger $event
     * @return $this
     */
    public function setEvent(EventVtiger $event)
    {
        $this->getEvents()->setItem($event);
        return $this;
    }

    /**
     * Get relations
     *
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    public function delete(): bool
    {
        return false;
    }
}
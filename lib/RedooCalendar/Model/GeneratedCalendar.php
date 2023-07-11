<?php

namespace RedooCalendar\Model;

use RedooCalendar\Base\Collection\Item\CollectionItemInterface;
use RedooCalendar\Base\Model\BaseModel;
use RedooCalendar\Model\Base\CalendarInterface;
use RedooCalendar\Model\Base\EventInterface;
use RedooCalendar\Model\Event\Collection as EventCollection;
use RedooCalendar\Model\GeneratedCalendar\Collection as GeneratedCalendarCollection;
use RedooCalendar\Model\GeneratedEvent\Collection as GeneratedEventCollection;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;

/**
 * @method getConfig($key)
 */
class GeneratedCalendar extends BaseModel implements CalendarInterface
{
    static $_tableName = 'generated_calendar';

    public $relations;
    protected $collection = GeneratedCalendarCollection::class;

    public function __construct()
    {
        $this->relations['events'] = new GeneratedEventCollection();
        parent::__construct();
    }

    public function initRelations(): BaseModel
    {
        $this->relations['events'] = new GeneratedEventCollection();
        return $this;
    }

    /**
     * Get events collection
     *
     * @return GeneratedEventCollection
     */
    public function getEvents(): GeneratedEventCollection
    {
        return $this->relations['events'];
    }

    /**
     * Add event to events collection
     *
     * @param GeneratedEvent $event
     * @return $this
     */
    public function setEvent(GeneratedEvent $event): GeneratedCalendar
    {
        $this->getEvents()->setItem($event);
        return $this;
    }

    public function getAsArray(): array
    {
        $result = $this->getData();
        $result['relations'] = [];

        /** @var CollectionItemInterface $relation */
        foreach ($this->relations as $key => $relation) {
            $result['relations'][$key] = $relation->getItemsAsArray();
        }

        return $result;
    }

}

<?php

namespace RedooCalendar\Model;

use RedooCalendar\Base\Model\BaseExternalModel;
use RedooCalendar\Base\VTEntity;
use RedooCalendar\Model\Base\EventInterface;
use RedooCalendar\Model\EventVtiger\Collection as EventVtigerCollection;

class EventVtiger extends BaseExternalModel implements EventInterface
{
    protected $collection = EventVtigerCollection::class;

    /**
     * Save Vtiger event
     *
     * @throws \Exception
     */
    public function save(): EventVtiger
    {
       // echo("dddsds");
        $context = VTEntity::create('Events');
        //var_dump($this->getData);

        foreach ($this->getData() as $field => $value) {
            $context->set($field, $value);
        }

        $context->save();

        $this->setId($context->getId());

        return $this;
    }

    /**
     * Update Event
     *
     * @return EventVtiger
     * @throws \Exception
     */
    public function update(): EventVtiger
    {
        $context = VTEntity::getForId($this->getId(), 'Events');

        foreach ($this->getData() as $field => $value) {
            $context->set($field, $value);
        }

        $context->save();
        return $this;
    }

}
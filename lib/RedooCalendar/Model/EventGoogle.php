<?php

namespace RedooCalendar\Model;

use RedooCalendar\Base\Model\BaseExternalModel;
use RedooCalendar\Model\Base\EventInterface;
use RedooCalendar\Model\EventGoogle\Collection as EventGoogleCollection;
use RedooCalendar\Model\EventVtiger\Collection as EventVtigerCollection;

class EventGoogle extends BaseExternalModel implements EventInterface
{
    protected $apiClient;
    protected $service;
    protected $collection = EventGoogleCollection::class;

    public function __construct(\Google_Client $apiClient = null)
    {
        if ($apiClient) {
            $this->apiClient = $apiClient;
            $this->service = new \Google_Service_Calendar($this->apiClient);
            parent::__construct();
        }
    }


    /**
     * Save model
     *
     * @return CalendarGoogle
     * @throws \Exception
     */
    public function save(): EventGoogle
    {
        if ($this->service) {
            $event = new \Google_Service_Calendar_Event();
            $event->setSummary($this->getTitle());

            $start = new \Google_Service_Calendar_EventDateTime();
            $start->setDateTime($this->getDateStart());

            $end = new \Google_Service_Calendar_EventDateTime();
            $end->setDateTime($this->getDateEnd());

            $event->setStart($start);
            $event->setEnd($end);
            $event->setDescription('test');

            $createdEvent = $this->service->events->insert($this->getCalendarId(), $event);

            $this->setId($createdEvent->getId());
        } else {
            throw new \Exception('Api Client not provided');
        }
        return $this;
    }

    public function update(): EventGoogle
    {
        if ($this->service) {
            $event = new \Google_Service_Calendar_Event();
            $event->setSummary($this->getTitle());
            $event->setId($this->getId());

            $start = new \Google_Service_Calendar_EventDateTime();
            $start->setDateTime($this->getDateStart());
            $end = new \Google_Service_Calendar_EventDateTime();

            if($this->getDateEnd()) {
                $end->setDateTime($this->getDateEnd());
            } else {
                $end->setDateTime($this->getDateStart());
            }

            $event->setStart($start);
            $event->setEnd($end);
            $event->setDescription('test');

            $createdEvent = $this->service->events->update($this->getCalendarId(), $this->getId(), $event);

            $this->setId($createdEvent->getId());
        } else {
            throw new \Exception('Api Client not provided');
        }
        return $this;
    }
}
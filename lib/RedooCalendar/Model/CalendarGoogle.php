<?php


namespace RedooCalendar\Model;

use RedooCalendar\Base\Model\BaseExternalModel;
use RedooCalendar\Model\Base\CalendarInterface;
use RedooCalendar\Model\CalendarGoogle\Collection as CalendarGoogleCollection;
use RedooCalendar\Model\EventVtiger\Collection as EventVtigerCollection;

class CalendarGoogle extends BaseExternalModel implements CalendarInterface
{
    protected $apiClient;
    protected $service;
    protected $collection = CalendarGoogleCollection::class;
    protected $relations;

    public function __construct(\Google_Client $apiClient = null)
    {
        if ($apiClient) {
            $this->apiClient = $apiClient;
            $this->service = new \Google_Service_Calendar($this->apiClient);
        }
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
     * @param $event
     * @return CalendarGoogle
     */
    public function setEvent(EventGoogle $event): CalendarGoogle
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

    /**
     * Save model
     *
     * @return CalendarGoogle
     * @throws \Exception
     */
    public function save(): CalendarGoogle
    {
        if ($this->service) {
            $calendar = new \Google_Service_Calendar_Calendar();
            $calendar->setSummary($this->getTitle());
            $calendar->setDescription('test');

            $createdCalendar = $this->service->calendars->insert($calendar);

            $this->setId($createdCalendar->getId());
        } else {
            throw new \Exception('Api Client not provided');
        }
        return $this;
    }

    public function delete(): bool
    {
        if ($this->service) {
            try {
                $this->service->calendars->delete($this->getId());
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        } else {
            throw new \Exception('Api Client not provided');
        }

    }


}
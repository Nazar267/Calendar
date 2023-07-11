<?php

namespace RedooCalendar\ConnectorPlugins;

use DateTime;
use DateTimeZone;
use RedooCalendar\Base\Collection\BaseCollection;
use RedooCalendar\Base\Collection\BaseExternalCollection;
use RedooCalendar\Base\Collection\CollectionInterface;
use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\EventForm;
use RedooCalendar\Base\Form;
use RedooCalendar\Base\Form\Field;
use RedooCalendar\Base\Form\Group;
use RedooCalendar\Base\Form\Validator\Mandatory;
use RedooCalendar\Model\Base\CalendarInterface;
use RedooCalendar\Model\Base\EventInterface;
use RedooCalendar\Model\Calendar;
use RedooCalendar\Model\CalendarShare;
use RedooCalendar\Model\Event;
use RedooCalendar\Base\Connection\ConnectorPlugin;
use RedooCalendar\Model\Subscribe;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;
use RedooCalendar_GetEventSourceData_Action;

class Shared extends ConnectorPlugin
{
//    protected $allDaySupport = true;
    protected $_Generator = true;

    protected $_Supports = array('time', 'create');

    protected $eventConfig = [
        'event' => [
            'model' => 'RedooEvent',
            'headline' => 'Event',
            'blocks' => [
                'general' => [
                    'column_count' => 1,
                    'headline' => 'General',
                    'fields' => [
                        'title' => [
                            'headline' => 'Event Title',
                            'name' => 'title',
                            'type' => Field::INPUT_TEXT,
                            'validator' => Mandatory::class
                        ]
                    ]
                ],
                'date_time' => [
                    'column_count' => 3,
                    'headline' => 'Date and time',
                    'fields' => [
                        'date_start' => [
                            'headline' => 'Date Start',
                            'name' => 'date_start',
                            'type' => Field::INPUT_DATE_TIME_PICKER,
                        ],
                        'date_end' => [
                            'headline' => 'Date End',
                            'name' => 'date_end',
                            'type' => Field::INPUT_DATE_TIME_PICKER,
                        ],
                        'all_day_event' => [
                            'headline' => 'All Day Event',
                            'name' => 'all_day_event',
                            'type' => Field::INPUT_PICKLIST,
                            'options' => [
                                'options' => [0 => 'No', 1 => 'Yes']
                            ]
                        ],
                    ]
                ],
                'content' => [
                    'column_count' => 1,
                    'headline' => 'Content',
                    'fields' => [
                        'title' => [
                            'headline' => 'Event Description',
                            'name' => 'description',
                            'type' => Field::INPUT_TEXT
                        ],
                    ]
                ],
            ]
        ],
    ];

    /**
     * Get calendars collection
     *
     * @return BaseCollection
     * @throws \RedooCalendar\Base\Exception\RelationException
     */
    public function getCalendarCollection(): CollectionInterface
    {
        $adb = \PearDatabase::getInstance();

        $query = '
            select * from vtiger_redoocalendar_calendar_permission
                left join vtiger_redoocalendar_subscribe on vtiger_redoocalendar_calendar_permission.calendar_id = vtiger_redoocalendar_subscribe.id
                left join vtiger_redoocalendar_connections vrc on vtiger_redoocalendar_subscribe.connection_id = vrc.id
            where vtiger_redoocalendar_calendar_permission.user_id = ?
        ';

        $rows = $adb->pquery($query, [\Users_Record_Model::getCurrentUserModel()->getId()]);
        $collection = new BaseExternalCollection();

        foreach ($rows as $row) {
            if(!isset($row['connection_id']) || !isset($row['calendar_id'])) continue;
            $connector = Connection::GetInstance($row['connection_id']);

            $calendar = $connector->getConnector()->getCalendar($row['calendar_id']);

            $connector->getConnector()->initEvents($calendar);

            switch ($row['access_level']) {
                case 'read':
                    $calendar->setReadOnly(true);

                    /** @var EventInterface $event */
                    foreach ($calendar->getEvents()->getItems() as &$event) {
                        $event->setReadOnly(true);
                    }

                    break;
                case 'write':
                    $calendar->setReadOnly(true);

                    /** @var CalendarInterface */
                    foreach ($calendar->getEvents()->getItems() as &$event) {
                        $event->setReadOnly(false);
                    }

                    break;
            }

            $collection->setItem($calendar);
        }

        foreach ($collection->getItems() as &$calendar) {
            $calendar->setReadOnly($calendar->getReadOnly() || !$this->allowEditCalendars);
            $calendar->setPreventDelete($calendar->getReadOnly() || !$this->allowDeleteCalendars);
        }

        return $collection;
    }

    public function getEventModel(): string
    {
        return 'RedooEvent';
    }

    /**
     * Create event
     *
     * @param \Vtiger_Request $request
     * @return EventInterface
     * @throws \Exception
     */
    public function createEvent(\Vtiger_Request $request): EventInterface
    {
        $event = new Event();
        $event->setTitle($request->get('title'));
        $event->setDescription($request->get('description'));

        $timezone = new DateTimeZone('UTC');

        $event->setCalendarId($request->get('calendar_id'));
        $event->setDateStart(
            (new DateTime('@' . $request->get('date_start_timestamp')))
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s')
        );

        $event->setDateEnd(
            (new DateTime('@' . $request->get('date_end_timestamp')))
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s')
        );

        $event->save();

        $timezone = $this->getUserTimeZone();

        $event->setDateStart(
            (new DateTime($event->getDateStart()))
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s')
        );

        $event->setDateEnd(
            (new DateTime($event->getDateEnd()))
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s')
        );

        $event->setConnector($request->get('connector'));
        return $event;
    }

    /**
     * Create calendar
     *
     * @param \Vtiger_Request $request
     * @return CalendarInterface
     */
    public function createCalendar(\Vtiger_Request $request): CalendarInterface
    {
        $calendar = new Calendar();
        $calendar->setTitle($request->get('title'));
        $calendar->setColor($request->get('color'));
        $calendar->setAccessMode($request->get('access_mode'));
        $calendar->setHideEventDetails($request->get('hide_event_details') === 'true');
        $calendar->setConnectionId($request->get('connector'));

        $calendar->save();

        $calendar->setReadOnly(!$this->allowEditCalendars);
        $calendar->setPreventDelete(!$this->allowDeleteCalendars);

        if ($request->get('access_mode') === 'share') {
            $users = explode(',', $request->get('share_to'));
            foreach ($users as $userId) {
                $share = new CalendarShare();
                $share->setUserId($userId);
                $share->setCalendarId($calendar->getId());
                $share->save();
            }
        }

        return $calendar;
    }

    public function updateEvent(\Vtiger_Request $request): EventInterface
    {
        $event = new Event();

        $event->setId($request->get('id'));
        $event->setTitle($request->get('title'));
        $event->setDescription($request->get('description'));
        $event->setCalendarId($request->get('calendar_id'));
        $event->setAllDayEvent(json_decode($request->get('all_day_event')));

        $timezone = new DateTimeZone('UTC');

        $event->setDateStart(
            (new DateTime('@' . $request->get('date_start_timestamp')))
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s')
        );

        if ($request->get('date_end_timestamp')) {
            $event->setDateEnd(
                (new DateTime('@' . $request->get('date_end_timestamp')))
                    ->setTimezone($timezone)
                    ->format('Y-m-d H:i:s')
            );
        } else {
            $event->setDateEnd(
                (new DateTime('@' . $request->get('date_start_timestamp')))
                    ->setTimezone($timezone)
                    ->format('Y-m-d H:i:s')
            );
        }

        $event->save();
        $event->setConnector($request->get('connector'));
        $timezone = $this->getUserTimeZone();

        $event->setData('all_day_event', json_decode($request->get('all_day_event')));

        if (json_decode($request->get('all_day_event'))) {
            $event->setDateStart(
                (new DateTime($event->getDateStart()))
                    ->setTimezone($timezone)
                    ->format('Y-m-d')
            );

            $event->setDateEnd(
                (new DateTime($event->getDateEnd()))
                    ->setTimezone($timezone)
                    ->format('Y-m-d')
            );
        } else {
            $event->setDateStart(
                (new DateTime($event->getDateStart()))
                    ->setTimezone($timezone)
                    ->format('Y-m-d H:i:s')
            );

            $event->setDateEnd(
                (new DateTime($event->getDateEnd()))
                    ->setTimezone($timezone)
                    ->format('Y-m-d H:i:s')
            );
        }

        return $event;
    }

    public function updateCalendar(CalendarInterface $calendar): CalendarInterface
    {
        $calendar->save();
        return $calendar;
    }

    public function deleteEvent(EventInterface $event): bool
    {
        return $event->delete();
    }

    public function deleteCalendar(string $id): bool
    {
        $calendar = new Calendar();
        $calendar->fetch($id);
        return $calendar->delete();
    }

    public function getCalendar(string $id): CalendarInterface
    {
        $calendar = new Calendar();
        $calendar->fetch($id);
        return $calendar;
    }

    public function getEvent(CalendarInterface $calendar, $eventId): EventInterface
    {

        $timezone = $this->getUserTimeZone();
        $event = new Event();
        $event->fetch($eventId);


        $event->setDateStart(
            (new DateTime($event->getDateStart()))
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s')
        );

        $event->setDateEnd(
            (new DateTime($event->getDateEnd()))
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s')
        );

        return $event;
    }

    public function getSubscribedCalendarCollection(): array
    {
        $subscribeCollection = new SubscribeCollection();
        $subscribeCollection->fetch([
            [
                'column' => 'connection_id',
                'value' => $this->getId()
            ]
        ]);

        $collection = $this->getCalendarCollection();

        /** @var Subscribe $item */
        foreach ($subscribeCollection->getItems() as $item) {
            $calendar = $collection->getItem($item->getCalendarId());
            if ($calendar) {
                $calendar->setVisible($item->getVisible());
                $calendar->setColor($item->getColor());
            }
        }

        $timezone = $this->getUserTimeZone();
        /** @var Calendar $calendar */
//        foreach ($collection->getItems() as &$calendar) {
//            /** @var Event\Collection $eventCollection */
//            $eventCollection = $calendar->getRelations('has_many')['events']['collection'];
//            /** @var Event $event */
//            foreach ($eventCollection->getItems() as &$event) {
//
//                $event->setDateStart(
//                    (new DateTime($event->getDateStart()))
//                        ->setTimezone($timezone)
//                        ->format('Y-m-d H:i:s')
//                );
//
//                $event->setDateEnd(
//                    (new DateTime($event->getDateEnd()))
//                        ->setTimezone($timezone)
//                        ->format('Y-m-d H:i:s')
//                );
//
//                $event->setData('all_day_event', (boolean)$event->getData('all_day_event'));
//            }
//        }

        return array_values(array_filter($collection->getItemsAsArray(), function ($item) use ($subscribeCollection) {
            return in_array($item['id'], $subscribeCollection->getCalendarIds());
        }));
    }

    public function getUnsubscribedCalendarCollection(): array
    {
        $subscribeCollection = new SubscribeCollection();
        $subscribeCollection->fetch([
            [
                'column' => 'connection_id',
                'value' => $this->getId()
            ]
        ]);

        return array_filter($this->getCalendarCollection()->getItemsAsArray(), function ($item) use ($subscribeCollection) {
            return !in_array($item['id'], $subscribeCollection->getCalendarIds());
        });
    }

    public function getShareUserList(Group &$group, CalendarInterface $calendar = null)
    {
        $users = [];

        /** @var \Users_Record_Model $user */
        foreach (\Users_Record_Model::getAll() as $user) {
            $users[$user->getId()] = $user->getName();
        }

        $field = $group->addField()
            ->setLabel('Share To')
            ->setBindValue('share_to')
            ->setName('share_to_' . $this->getId())
            ->setRelated([
                'access_mode' => ['share'],
                'connector' => [$this->getId()]
            ])
            ->setOptions([
                'multiple' => true,
                'options' => $users
            ])
            ->setType(Field::INPUT_PICKLIST);

        if ($calendar) {
            $subscribedUsers = new CalendarShare\Collection();
            $subscribedUsers->getSharedUsers($calendar->getId());
            $field->setValue($subscribedUsers->getUserIds());
        }

    }
}

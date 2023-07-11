<?php

namespace RedooCalendar\ConnectorPlugins;

use DateTimeZone;
use RedooCalendar\Base\Collection\BaseExternalCollection;
use RedooCalendar\Base\Collection\CollectionInterface;
use RedooCalendar\Base\Connection\ConnectorPlugin;
use RedooCalendar\Base\Form\Field;
use RedooCalendar\Base\Form\Group;
use RedooCalendar\Base\Form\Validator\Mandatory;
use RedooCalendar\Base\VTEntity;
use RedooCalendar\Base\VtUtils;
use RedooCalendar\Model\Base\CalendarInterface;
use RedooCalendar\Model\Base\EventInterface;
use RedooCalendar\Model\Calendar;
use RedooCalendar\Model\CalendarVtiger;
use RedooCalendar\Model\Event;
use RedooCalendar\Model\CalendarVtiger\Collection as CalendarVtigerCollection;
use RedooCalendar\Model\EventVtiger;
use RedooCalendar\Model\Subscribe;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;
use RedooCalendar\Source\ActivityType;
use RedooCalendar\Source\EventStatusOptions;
use RedooCalendar\Source\TaskStatusOptions;

class VtigerEvent extends ConnectorPlugin
{
    protected $_Generator = true;

    protected $_Supports = array('time', 'create');

    protected $allowCreateCalendars = false;
    protected $allowDeleteCalendars = false;
    protected $allowEditCalendars = true;

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
                        ],
                        'status' => [
                            'headline' => 'Status',
                            'name' => 'eventstatus',
                            'type' => Field::INPUT_PICKLIST,
                            'options' => [
                                'options' => EventStatusOptions::data
                            ]
                        ],
                        'activitytype' => [
                            'headline' => 'Activity Type',
                            'name' => 'activitytype',
                            'type' => Field::INPUT_PICKLIST,
                            'options' => [
                                'options' => ActivityType::data
                            ]
                        ],
                    ],
                ],
                'date_time' => [
                    'column_count' => 2,
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
                    ]
                ],

            ]
        ],
    ];


    public function getEvent(CalendarInterface $calendar, $eventId): EventInterface
    {

        $sql = $this->getBaseQuery($calendar);
        $sql .= ' AND vtiger_crmentity.crmid = ?';

        $row = VtUtils::fetchByAssoc($sql, $eventId);

        $timeZone = $this->getUserTimeZone();

        //checking if owner of this event ha vacation right now
        $sql_vac = "SELECT vtiger_vacations.vacation_to AS endVacationDate
                FROM vtiger_vacations 
                JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_vacations.vacationsid
                WHERE vtiger_vacations.vacation_to > NOW() AND vtiger_crmentity.smownerid = {$this->getOwnerId($calendar)}";
        $result_vac = VtUtils::fetchByAssoc($sql_vac);
        $endvacationdate = $result_vac['endvacationdate'] == NULL ? NULL : $result_vac['endvacationdate'];
        

        $event = new EventVtiger();
        $event->setData('endvacationdate', $endvacationdate);
        $event->setTitle($row['subject']);
        $event->setDescription($row['description']);
        $event->setEventstatus($row['eventstatus']);
        $event->setActivitytype($row['activitytype']);
        $event->setDateStart((new \DateTime($row['date_start'] . ' ' . $row['time_start']))
            ->setTimezone($timeZone)
            ->format('Y-m-d H:i:s'));
        $event->setDateEnd((new \DateTime($row['due_date'] . ' ' . $row['time_end']))
           ->setTimezone($timeZone)
            ->format('Y-m-d H:i:s'));
        $event->setAllDayEvent(true);
        $event->setId($row['crmid']);
        $event->setCalendarId($calendar->getId());

        return $event;
    }


    protected function getEditFields()
    {
        return array(
            array(
                'headline' => 'Events',
                'blocks' => array(
                    array(
                        'headline' => 'General',
                        'columns' => '2',
                        'items' => array(
                            array(
                                'name' => 'subject',
                                'label' => 'Subject',
                                'type' => 'text',
                                'fullwidth' => true,
                            ),
                            array(
                                'name' => 'location',
                                'label' => 'Location',
                                'type' => 'text',
                                'fullwidth' => true,
                            ),
                            /*array(
                                'name' => 'assigned_to',
                                'label' => 'Assigned to',
                                'type' => 'owner',
                            ),*/
                            array(
                                'name' => 'activitytype',
                                'label' => 'Activitytype',
                                'type' => 'select',
                                'options' => array(
                                    'Call' => 'Call',
                                    'Meeting' => 'Meeting',
                                    'Mobile Call' => 'Mobile Call',
                                )
                            ),
                            array(
                                'name' => 'taskpriority',
                                'label' => 'Priority',
                                'type' => 'select',
                                'options' => array(
                                    'High' => 'High',
                                    'Medium' => 'Medium',
                                    'Low' => 'Low',
                                )
                            ),
                        )
                    ),
                    array(
                        'headline' => 'Dates',
                        'columns' => 2,
                        'items' => array(
                            array(
                                'name' => 'from_date',
                                'label' => 'Start',
                                'type' => 'datetime',
                                'relation' => 'from'
                            ),
                            array(
                                'name' => 'to_date',
                                'label' => 'End',
                                'type' => 'datetime',
                                'relation' => 'to'
                            ),
                        )
                    ),
                )
            ),
            array(
                'headline' => 'Description',
                'blocks' => array(
                    array(
                        'headline' => '',
                        'columns' => '1',
                        'items' => array(
                            array(
                                'name' => 'description',
                                'label' => 'Description',
                                'type' => 'textarea',
                                'fullwidth' => true,
                            )
                        )
                    )
                )
            ),
            array(
                'headline' => 'Recurrence',
                'blocks' => array(
                    array(
                        'headline' => 'Recurrance',
                        'columns' => '1',
                        'items' => array(
                            array(
                                'name' => 'recurring',
                                'label' => 'Recurring Event',
                                'type' => 'recurring',
                                'fullwidth' => true,
                            )
                        )
                    )
                )
            ),
            array(
                'headline' => 'Attendees',
                'blocks' => array(
                    array(
                        'headline' => 'Attendees',
                        'columns' => '1',
                        'items' => array(
                            array(
                                'name' => 'attendees',
                                'label' => 'Attendeest',
                                'type' => 'attendees',
                                'fullwidth' => true,
                            )
                        )
                    )
                )
            ),
        );
    }

    private function getOwnerId(CalendarVtiger $calendar)
    {
        $parts = explode('_', $calendar->getId());

        return $parts[1];

    }

    private function getBaseQuery(CalendarVtiger $calendar)
    {
        return 'SELECT 
              vtiger_activity.date_start,
              vtiger_activity.due_date,
              vtiger_activity.time_start,
              vtiger_activity.time_end,
              vtiger_activity.subject,
              vtiger_activity.activitytype,
              vtiger_activity.visibility,
              vtiger_activity.eventstatus,
              vtiger_activity.priority,
              vtiger_activity.location,
              vtiger_crmentity.label,
              vtiger_crmentity.setype,
              vtiger_crmentity.crmid,
              vtiger_crmentity.description,
              vtiger_crmentity.smownerid,
              
              vtiger_recurringevents.recurringdate,
              vtiger_recurringevents.recurringtype,
              vtiger_recurringevents.recurringfreq,
              vtiger_recurringevents.recurringinfo,
              vtiger_recurringevents.recurringenddate
              
            FROM vtiger_crmentity
              INNER JOIN vtiger_activity ON (vtiger_activity.activityid = vtiger_crmentity.crmid)
              LEFT JOIN vtiger_recurringevents ON (vtiger_recurringevents.activityid = vtiger_crmentity.crmid)
            WHERE 
              vtiger_crmentity.setype = "Calendar" AND 
              vtiger_crmentity.deleted = 0 AND 
              vtiger_crmentity.smownerid = ' . $this->getOwnerId($calendar) . ' AND
              vtiger_activity.activitytype NOT IN ("Emails","Task")';
    }

    public function getEvents(CalendarVtiger $calendar)
    {
        $sql = $this->getBaseQuery($calendar);
//        $sql .= 'AND vtiger_activity.date_start >= ? AND vtiger_activity.date_start <= ?';

        $result = VtUtils::fetchRows($sql);

        $events = array();
//        foreach ($result as $row) {
//            $from = new \DateTime($row['date_start'] . ' ' . $row['time_start'], new \DateTimeZone('UTC'));
//            $to = new \DateTime($row['due_date'] . ' ' . $row['time_end'], new \DateTimeZone('UTC'));
//
//            $obj = new Event($calendar, $row['crmid'], $from, $to, $row['label'], vtranslate($row['setype'], $row['location']));
//
//            $allowedAccess = array();
//            if ($row['visibility'] == 'private') {
//                $allowedAccess[] = array('User', $row['smownerid']);
//            }
//            $obj->setVisibility($allowedAccess, Event::VISIBILITY_BLOCKED);
//
//            $events[] = $obj;
//        }

        return $result;
    }

    public function updateEvent(\Vtiger_Request $request): EventInterface
    {
        $calendar_id = $request->get('calendar_id');
        if($calendar_id == '')
        {
            $event_model = \Calendar_Record_Model::getInstanceById($request->get('id'), 'Calendar');
            $calendar_id = 'user_'.$event_model->get('assigned_user_id');
        }

        $eventVtiger = new EventVtiger();
        $calendarVtiger = new CalendarVtiger();
        $calendarVtiger->setId($calendar_id);
        $timeZone = $this->getUserTimeZone();

        $eventVtiger->setData($request->getAll());

        $fromTime =  new \DateTime('@' . $request->get('date_start_timestamp'));
        $endTime =  new \DateTime('@' . $request->get('date_end_timestamp'));




        $eventVtiger = new EventVtiger();
        $eventVtiger->setId($request->get('id'));
        $eventVtiger->setActivitytype($request->get('activitytype'));
        $eventVtiger->setData('date_start', $fromTime->format('Y-m-d'));
        $eventVtiger->setData('time_start', $fromTime->format('H:i:s'));

        if ($request->get('date_end_timestamp')) {
            $eventVtiger->setData('due_date', $endTime->format('Y-m-d'));
            $eventVtiger->setData('time_end', $endTime->format('H:i:s'));
        } else {
            $eventVtiger->setData('due_date', $fromTime->format('Y-m-d'));
            $eventVtiger->setData('time_end', $fromTime->format('H:i:s'));
        }

        $eventVtiger->setData('subject', $request->get('title'));
        $eventVtiger->setData('description', $request->get("description"));
        //$eventVtiger->setDescription($request->get("description"));
        $eventVtiger->setData('assigned_user_id', $this->getOwnerId($calendarVtiger));
        $eventVtiger->setData('eventstatus', $request->get('eventstatus'));
        $eventVtiger->setData('title', $eventVtiger->getData('subject'));
        $eventVtiger->setData('connector', $this->getId());
        $eventVtiger->setData('calendar_id', $calendarVtiger->getId());
        //$eventVtiger->setData('date_start', $eventVtiger->getData('date_start') . ' ' . $eventVtiger->getData('time_start'));
        //$eventVtiger->setData('date_end', $eventVtiger->getData('due_date') . ' ' . $eventVtiger->getData('time_end'));


      $eventVtiger->update();

        return $eventVtiger;
    }

    public function deleteEvent(EventInterface $event): bool
    {
        $context = VTEntity::getForId($event->getId(false), 'Events');
        $context->delete();
        return true;
    }

    /*public function createEvent(\Vtiger_Request $request): EventInterface
    {
        $timeZone = new DateTimeZone('UTC');
        $calendarVtiger = new CalendarVtiger();
        $calendarVtiger->setId($request->get('calendar_id'));

        $event = new EventVtiger();

        $event->setData('date_start',
            (new \DateTime())
                ->setTimestamp($request->get('date_start_timestamp'))
                ->setTimezone($timeZone)
                ->format('Y-m-d'));
        $event->setData('time_start',
            (new \DateTime())
                ->setTimestamp($request->get('date_start_timestamp'))
                ->setTimezone($timeZone)
                ->format('H:i:s'));

        $event->setData('due_date',
            (new \DateTime())
                ->setTimestamp($request->get('date_end_timestamp'))
                ->setTimezone($timeZone)
                ->format('Y-m-d'));
        $event->setData('time_end',
            (new \DateTime())
                ->setTimestamp($request->get('date_end_timestamp'))
                ->setTimezone($timeZone)
                ->format('H:i:s'));

        $event->setData('subject', $request->get('title'));
        $event->setConnector($request->get('connector'));

        $event->setData('assigned_user_id', $this->getOwnerId($calendarVtiger));
        $event->setData('activitytype', $request->get('activitytype'));
        $event->setData('eventstatus', $request->get('eventstatus'));

        $event->save();
        $event->setCalendarId($calendarVtiger->getId());
        $event->setData('title', $request->get('title'));
        $timeZone = $this->getUserTimeZone();

        $event->setData('date_start',
            (new \DateTime())
                ->setTimestamp(strtotime($event->getData('date_start')))
                ->setTimezone($timeZone)
                ->format('Y-m-d'));
        $event->setData('time_start',
            (new \DateTime())
                ->setTimestamp(strtotime($event->getData('time_start')))
                ->setTimezone($timeZone)
                ->format('H:i:s'));

        $event->setData('due_date',
            (new \DateTime())
                ->setTimestamp(strtotime($event->getData('due_date')))
                ->setTimezone($timeZone)
                ->format('Y-m-d'));
        $event->setData('time_end',
            (new \DateTime())
                ->setTimestamp(strtotime($event->getData('time_end')))
                ->setTimezone($timeZone)
                ->format('H:i:s'));

        $event->setData('date_start', $event->getData('date_start') . ' ' . $event->getData('time_start'));
        $event->setData('date_end', $event->getData('due_date') . ' ' . $event->getData('time_end'));

        return $event;
    }*/

    public function createEvent(\Vtiger_Request $request): EventInterface
    {
//        $timeZone = new DateTimeZone('UTC');
        $timeZone = $this->getUserTimeZone();
        $calendarVtiger = new CalendarVtiger();
        $calendarVtiger->setId($request->get('calendar_id'));
       // echo("good");
        $event = new EventVtiger();
        $event->setAllDayEvent(false);

        // fromTime in UTC Timezone
        $fromTime =  new \DateTime('@'. $request->get('date_start_timestamp'));
        $endTime =  new \DateTime('@'. $request->get('date_end_timestamp'));
        
        $event->setData('date_start', $fromTime->format('Y-m-d'));
        $event->setData('time_start', $fromTime->format('H:i:s'));

        $event->setData('due_date', $endTime->format('Y-m-d'));
        $event->setData('time_end', $endTime->format('H:i:s'));
        
        $event->setData('activitytype', $request->get('activitytype'));
        //$event->setDescription($request->get("description"));
        $event->setData('description', $request->get("description"));

        $event->setData('visibility', 'Private');
        $event->setData('subject', $request->get('title'));
        $event->setData('title', $request->get('title'));
        $event->setConnector($request->get('connector'));
        $event->setData('assigned_user_id', $this->getOwnerId($calendarVtiger));

        $event->setData('taskstatus', $request->get('taskstatus'));

        $event->save();

        // We need to change value of return to send correct value to javascript
        $timeZone = $this->getUserTimeZone();
        $fromTime =  (new \DateTime('@' . $request->get('date_start_timestamp')))->setTimezone($timeZone);
        $endTime =  (new \DateTime('@' . $request->get('date_end_timestamp')))->setTimezone($timeZone);
        $event->setData('date_start', $fromTime->format('Y-m-d'));
        $event->setData('time_start', $fromTime->format('H:i:s'));

        $event->setData('due_date', $endTime->format('Y-m-d'));
        $event->setData('time_end', $endTime->format('H:i:s'));

        $event->setData('date_start', $event->getData('date_start') . ' ' . $event->getData('time_start'));
        $event->setData('date_end', $event->getData('due_date') . ' ' . $event->getData('time_end'));

        return $event;
    }

    public function getCalendars()
    {
        $currentUser = \Users_Record_Model::getCurrentUserModel();

        $sharedUsers = \Calendar_Module_Model::getSharedUsersOfCurrentUser($currentUser->id);

//        $sharedGroups = \Calendar_Module_Model::getSharedCalendarGroupsList($currentUser->id);
//        $sharedUsersInfo = \Calendar_Module_Model::getSharedUsersInfoOfCurrentUser($currentUser->id);

        $calendars = array();
        $calendars[] = array(
            'id' => 'user_' . $currentUser->getId(),
            'owner' => true,
            'title' => $currentUser->getName(),
        );

        foreach ($sharedUsers as $userId => $username) {
            $calendars[] = array(
                'id' => 'user_' . $userId,
                'owner' => false,
                'title' => $username
            );
        }
        /*
        foreach($sharedGroups as $groupId => $groupname) {
            $calendars[] = array(
                'id' => 'group_'.$groupId,
                'title' => $groupname
            );
        }
        */

        return $calendars;
    }

    public function getCalendarData(Calendar $calendar)
    {
        $currentUser = \Users_Record_Model::getCurrentUserModel();

        $id = $calendar->getId(false);
        $parts = explode('_', $id);

        if ($parts[1] == $currentUser->getId()) {
            $title = 'Mine';
        } else {
            if ($parts[0] == 'user') {
                $title = \Vtiger_Functions::getUserRecordLabel($parts[1]);
            } else {
                $title = \Vtiger_Functions::getGroupRecordLabel($parts[1]);
            }
        }

        return array(
            'title' => $title
        );
    }

    /**
     * Get calendars collection
     *
     * @return BaseExternalCollection
     * @throws \Exception
     */
    public function getCalendarCollection(): CollectionInterface
    {
        $timeZone = $this->getUserTimeZone();
        $utcTimezone = new \DateTimeZone('UTC');

        $calendarCollection = new CalendarVtigerCollection();
        $calendars = $this->getCalendars();
        foreach ($calendars as $_calendar) {
            $calendar = new CalendarVtiger();
            $calendar->setId($_calendar['id']);
            $calendar->setColor('#00f');
            $calendar->setOwner($_calendar['owner']);
            $calendar->setTitle(html_entity_decode($_calendar['title']));
            $calendar->setReadOnly(!$this->allowEditCalendars);
            $calendar->setPreventDelete(!$this->allowDeleteCalendars);

            foreach ($this->getEvents($calendar) as $_event) {
                $event = new EventVtiger();
                $event->setTitle(html_entity_decode($_event['subject']));
                $event->setDateStart((new \DateTime($_event['date_start'] . ' ' . $_event['time_start'], $utcTimezone))
                    ->setTimezone($timeZone)
                    ->format('Y-m-d H:i:s'));

                $event->setDateEnd((new \DateTime($_event['due_date'] . ' ' . $_event['time_end'], $utcTimezone))
                    ->setTimezone($timeZone)
                    ->format('Y-m-d H:i:s'));

                $event->setId($_event['crmid']);
                $event->setCalendarId($calendar->getId());
                $calendar->setEvent($event);
            }

            $calendarCollection->setItem($calendar);
        }

        return $calendarCollection;
    }

    public function getCalendar(string $id): CalendarInterface
    {
        $calendar = new CalendarVtiger();
        $calendar->setId($id);
        return $calendar;
    }

    public function updateCalendar(CalendarInterface $calendar): CalendarInterface
    {
        return new CalendarVtiger();
    }

    public function deleteCalendar(string $id): bool
    {
        // TODO: Implement deleteCalendar() method.
    }

    public function createCalendar(\Vtiger_Request $request): CalendarInterface
    {
        // TODO: Implement createCalendar() method.
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

        if($subscribeCollection->count() == 0) {
            $currentUser = \Users_Record_Model::getCurrentUserModel();

            $subscribe = new Subscribe();

            $subscribe->setData('calendar_id', 'user_' . $currentUser->getId());
            $subscribe->setData('connection_id', $this->getId());
            $subscribe->setData('visible', 1);

            $subscribe->save();

            // Skip last part of function and reload SubscribeCollection
            return $this->getSubscribedCalendarCollection();
        }

        $collection = $this->getCalendarCollection();

        foreach ($subscribeCollection->getItems() as $item) {
            $calendar = $collection->getItem($item->getCalendarId());
            if ($calendar) {
                $calendar->setVisible($item->getVisible());
                $calendar->setColor($item->getColor());
            }
        }

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

    public function getShareUserList(Group &$group)
    {
        $users = [];

        /** @var \Users_Record_Model $user */
        foreach (\Users_Record_Model::getAll() as $user) {
            $users[$user->getId()] = $user->getName();
        }

        $group->addField()
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

    }

}
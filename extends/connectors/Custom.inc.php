<?php

/**
 * Created by Redoo Networks GmbH
 * User: Stefan
 * Date: 25.09.2017
 * Time: 17:24
 */

namespace RedooCalendar\ConnectorPlugins;

use ComplexCondition\VTTemplate;
use DateTimeZone;
use RedooCalendar\Base\Collection\CollectionInterface;
use RedooCalendar\Base\Form\Field;
use RedooCalendar\Base\Form\Group;
use RedooCalendar\Base\Form\Validator\Mandatory;
use RedooCalendar\Model\Base\CalendarInterface;
use RedooCalendar\Model\Base\EventInterface;
use RedooCalendar\Model\Calendar;
use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\Connection\ConnectorPlugin;
use RedooCalendar\Model\Event;
use RedooCalendar\Base\VTEntity;
use RedooCalendar\Base\VtUtils;
use RedooCalendar\Model\GeneratedCalendar;
use RedooCalendar\Model\GeneratedEvent;
use RedooCalendar\Model\Subscribe;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;

class Custom extends ConnectorPlugin
{
    protected $_Generator = true;

    protected $_Supports = array('time');

    protected $allowCreateCalendars = false;

    protected $eventConfig = [
        'event' => [
            'model' => 'RedooEvent',
            'headline' => 'Event',
            'blocks' => [
                'general' => [
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

    /**
     * @param GeneratedCalendar $calendar
     * @param $eventId
     * @return EventInterface
     * @throws \Exception
     */
    public function getEvent(CalendarInterface $calendar, $eventId): EventInterface
    {
        $sql = $calendar->getSql() . ' AND vtiger_crmentity.crmid = ?';

        $result = VtUtils::fetchByAssoc(htmlspecialchars_decode($sql, ENT_QUOTES), $eventId);
        $event = new GeneratedEvent();
        $event->setData($result);
        $event->setId($result['__crmid']);

        return $event;
    }

    public function getBaseQuery(array $settings)
    {
        $moduleInstance = \Vtiger_Module_Model::getInstance($settings['module_id']);
        $module = \CRMEntity::getInstance($moduleInstance->getName());

        $tables = array();
        $select = array();

        $select[] = 'vtiger_crmentity.crmid as __crmid';

        $tables[] = "FROM " . $module->table_name;
        $tables[] = 'INNER JOIN vtiger_crmentity ON (vtiger_crmentity.crmid = ' . $module->table_name . '.' . $module->table_index . ' AND deleted = 0)';

        foreach ($module->tab_name_index as $tableName => $tableIndex) {
            if ($tableName != 'vtiger_crmentity' && $tableName != $module->table_name) {
                $tables[] = 'LEFT JOIN ' . $tableName . ' ON (' . $tableName . '.' . $tableIndex . ' = ' . $module->table_name . '.' . $module->table_index . ')';
            }
        }

        $fromColumnInfo = VtUtils::getFieldInfo($settings['date_from'], getTabId($moduleInstance->getName()));
        $toColumnInfo = VtUtils::getFieldInfo($settings['date_to'], getTabId($moduleInstance->getName()));

        $select[] = '' . $fromColumnInfo['tablename'] . '.' . $fromColumnInfo['columnname'] . ' as date_from';
        $select[] = '' . $toColumnInfo['tablename'] . '.' . $toColumnInfo['columnname'] . ' as due_date';

        $columnInfo = VtUtils::getFieldInfo($settings['event_title'], getTabId($moduleInstance->getName()));


        $select[] = '' . $columnInfo['tablename'] . '.' . $columnInfo['columnname'] . ' as title';

        if (!empty($settings['event_subtitle'])) {
            $columnInfo = VtUtils::getFieldInfo($settings['event_subtitle'], getTabId($moduleInstance->getName()));
            $select[] = '' . $columnInfo['tablename'] . '.' . $columnInfo['columnname'] . ' as subtitle';
        }

        if ($settings['datetime_mode'] == 'datetime') {
            $fromTimeColumnInfo = VtUtils::getFieldInfo($settings['time_from'], getTabId($moduleInstance->getName()));
            $toTimeColumnInfo = VtUtils::getFieldInfo($settings['time_to'], getTabId($moduleInstance->getName()));

            $select[] = '' . $fromTimeColumnInfo['tablename'] . '.' . $settings['time_from'] . ' as time_start';
            $select[] = '' . $toTimeColumnInfo['tablename'] . '.' . $settings['time_to'] . ' as time_end';
        }

        return 'SELECT ' . implode(',', $select) . ' /* INSERT FIELDS */ ' . implode(' ', $tables) . ' WHERE ';
    }

    public function updateEvent(\Vtiger_Request $request): EventInterface
    {
        $calendar = new GeneratedCalendar();
        $calendar->fetch($request->get('calendar_id'));
        $settings = json_decode(htmlspecialchars_decode($calendar->getConfig(), ENT_QUOTES), true);

        $utc = new \DateTimeZone('UTC');

        $module = \Vtiger_Module_Model::getInstance($settings['module_id']);

        $instance = VTEntity::getForId($request->get('id'), $module->getName());

        global $default_timezone;
        switch ($settings['timezone']) {
            case 'UTC':
                $existingTimezone = new \DateTimeZone($default_timezone);
                break;
            case 'currentuser':
                $existingTimezone = $this->getUserTimeZone();
                break;
        }

        $from_date = new \DateTime('@' . $request->get('date_start_timestamp'), new \DateTimeZone($default_timezone));
        $to_date = new \DateTime('@' . $request->get('date_end_timestamp'), new \DateTimeZone($default_timezone));

        $from_date->setTimezone($existingTimezone);
        $to_date->setTimezone($existingTimezone);

        $instance->set($settings['date_from'], $from_date->format('Y-m-d'));
        $instance->set($settings['date_to'], $to_date->format('Y-m-d'));

        if ($settings['datetime_mode'] == 'datetime') {
            $instance->set($settings['time_from'], $from_date->format('H:i:s'));
            $instance->set($settings['time_to'], $to_date->format('H:i:s'));
        }

        $instance->save();

        return new GeneratedEvent();
    }

    public function deleteEvent(EventInterface $event): bool
    {
    }

    public function getCalendarCollection(): CollectionInterface
    {
        $timeZone = $this->getUserTimeZone();

        $generatedCalendarCollection = new GeneratedCalendar\Collection();
        $generatedCalendarCollection->fetch([
            [
                'column' => 'user_id',
                'value' => $this->getUserId()
            ]
        ]);

        /** @var GeneratedCalendar $calendar */
        foreach ($generatedCalendarCollection->getItems() as &$calendar) {
            $calendar->initRelations();

            $this->loadEvents($calendar);
        }

        $publicCalendarCollection = $this->getPublicCalendarCollection();
        $generatedCalendarCollection->merge($publicCalendarCollection);

        return $generatedCalendarCollection;
    }

    public function getPublicCalendarCollection(): CollectionInterface
    {
      $timeZone = $this->getUserTimeZone();

      $generatedCalendarCollection = new GeneratedCalendar\Collection();
      $generatedCalendarCollection->fetch([
        [
          'column' => 'access_mode',
          'value' => '"public"'
        ]
      ]);


      /** @var GeneratedCalendar $calendar */
      foreach ($generatedCalendarCollection->getItems() as &$calendar) {
        $calendar->initRelations();

        $this->loadEvents($calendar);
      }

      return $generatedCalendarCollection;
    }

    public function getSubscribedCalendarCollection(): array
    {
        $subscribeCollection = new SubscribeCollection();
        $subscribeCollection->fetch([
            [
                'column' => 'connection_id',
                'value' => $this->getId()
            ],
            [
                'column' => 'owner',
                'value' => \Users_Record_Model::getCurrentUserModel()->getId()
            ]
        ]);
        $collection = $this->getCalendarCollection();

        foreach ($subscribeCollection->getItems() as $item) {
          if (!is_numeric($item->getCalendarId())) {
            continue;
          }

          $calendar = $collection->getItem($item->getCalendarId());

          if ($calendar) {
            $calendar->setVisible($item->getVisible());
            $calendar->setColor($item->getColor());
          }
        }

        $result = array_values(array_filter($collection->getItemsAsArray(), function ($item) use ($subscribeCollection) {
            return in_array($item['id'], $subscribeCollection->getCalendarIds());
        }));

        foreach ($result as &$calendar) {
            $calendar['title'] = html_entity_decode($calendar['title']);
            unset($calendar['sql']);
            unset($calendar['config']);
            unset($calendar['field_config']);
        }

        foreach ($result2 as &$calendar) {
          $calendar['title'] = html_entity_decode($calendar['title']);
          unset($calendar['sql']);
          unset($calendar['config']);
          unset($calendar['field_config']);
        }

        if (!empty($result2)) {
          $result = array_merge($result, $result2);
        }

        return $result;
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
        $data = $this->getCalendarCollection()->getItemsAsArray();
        $data = $data + $this->getPublicCalendarCollection()->getItemsAsArray();

        return array_filter($data, function ($item) use ($subscribeCollection) {
            return !in_array($item['id'], $subscribeCollection->getCalendarIds());
        });
    }

    public function getCalendar(string $id): CalendarInterface
    {
        $calendar = new GeneratedCalendar();
        $calendar->fetch((int)$id);
        return $calendar;
    }

    public function deleteCalendar(string $id): bool
    {
        $calendar = new GeneratedCalendar();
        $calendar->fetch($id);
        $calendar->delete();
        return true;
    }

    public function updateCalendar(CalendarInterface $calendar): CalendarInterface
    {
        // TODO: Implement updateCalendar() method.
    }

    public function createCalendar(\Vtiger_Request $request): CalendarInterface
    {
        // TODO: Implement createCalendar() method.
    }

    public function getShareUserList(Group &$group)
    {
        // TODO: Implement getShareUserList() method.
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

    public function createEvent(\Vtiger_Request $request): EventInterface
    {
    }

    public function loadEvents(GeneratedCalendar &$generatedCalendar): ConnectorPlugin
    {
        global $default_timezone, $current_user;
        $timeZone = $this->getUserTimeZone();
        $settings = json_decode(htmlspecialchars_decode($generatedCalendar->getConfig(), ENT_QUOTES), true);

        // $sql = VTTemplate::parse($generatedCalendar->getSql(), VTEntity::getDummy());

        $sql = str_replace('$current_user_id', $current_user->id, $generatedCalendar->getSql());
        $result = VtUtils::query(htmlspecialchars_decode($sql, ENT_QUOTES));

        try {

            $generatedCalendar->setReadOnly(!$this->allowEditCalendars);
            $generatedCalendar->setPreventDelete(!$this->allowDeleteCalendars);

            if (empty($settings['timezone'])) {
                $settings['timezone'] = 'UTC';
            }

            switch ($settings['timezone']) {
                case 'UTC':
                    $existingTimezone = new \DateTimeZone($default_timezone);
                    break;
                case 'currentuser':
                    $existingTimezone = $timeZone;
                    break;
            }

            $generatedCalendar->setGenerated(true);
            foreach ($result as $item) {
                $item['title'] = html_entity_decode($item['title']);
                if (!empty($settings['title_prefix'])) {
                    $item['title'] = '' . $settings['title_prefix'] . '' . $item['title'];
                }
                if ($settings['datetime_mode'] == 'date' || empty($item['time_start']) || empty($item['time_end'])) {


                    $event = new GeneratedEvent();
                    $event->setData($item);

                    $event->setData(
                        'date_start',
                        (new \DateTime($event->getData('date_from') . '12:00:00'))
                            ->setTimezone($timeZone)
                            ->format('Y-m-d H:i:s')
                    );

                    $event->setData(
                        'date_end',
                        (new \DateTime($event->getData('due_date') . '12:00:00'))
                            ->setTimezone($timeZone)
                            ->format('Y-m-d H:i:s')
                    );
                    $event->setData('all_day_event', true);

                    $event->setId($item['__crmid']);
                    $generatedCalendar->setEvent($event);
                } elseif (
                    $settings['datetime_mode'] == 'datetime'
                    && isset($item['time_start'])
                    && isset($item['time_end'])
                    && $item['time_start']
                    && $item['time_end']
                ) {
                    $event = new GeneratedEvent();
                    $event->setData($item);

                    $event->setData(
                        'date_start',
                        (new \DateTime($event->getData('date_from') . ' ' . $event->getData('time_start'), $existingTimezone))
                            ->setTimezone($timeZone)
                            ->format('Y-m-d H:i:s')
                    );

                    $event->setData(
                        'date_end',
                        (new \DateTime($event->getData('due_date') . ' ' . $event->getData('time_end'), $existingTimezone))
                            ->setTimezone($timeZone)
                            ->format('Y-m-d H:i:s')
                    );

                    $event->setId($item['__crmid']);
                    $generatedCalendar->setEvent($event);
                }
            }
        } catch (\Exception $exception) {
        }
        return $this;
    }
}

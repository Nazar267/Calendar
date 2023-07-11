<?php

namespace RedooCalendar\ConnectorPlugins;

use DateTime;
use DateTimeZone;
use Google_Client;
use RedooCalendar\Base\Collection\BaseExternalCollection;
use RedooCalendar\Base\Collection\CollectionInterface;
use RedooCalendar\Base\Connection\ConnectorPlugin;
use RedooCalendar\Base\Form\Field;
use RedooCalendar\Base\Form\Group;
use RedooCalendar\Base\Form\Validator\Length;
use RedooCalendar\Base\Form\Validator\Mandatory;
use RedooCalendar\Config;
use RedooCalendar\Model\Base\CalendarInterface;
use RedooCalendar\Model\Base\EventInterface;
use RedooCalendar\Model\Calendar;
use RedooCalendar\Model\CalendarGoogle;
use RedooCalendar\Model\CalendarGoogle\Collection as CalendarGoogleCollection;
use RedooCalendar\Model\Connection;
use RedooCalendar\Model\Event;
use RedooCalendar\Model\EventGoogle;
use RedooCalendar\Model\Subscribe;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;

class Google extends ConnectorPlugin
{
    protected $apiClient;

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
                    ]
                ],
                'date_time' => [
                    'column_count' => 2,
                    'headline' => 'Date and time',
                    'fields' => [
                        'date_start' => [
                            'headline' => 'Date Start',
                            'name' => 'date_start',
                            'type' => Field::INPUT_DATE_TIME_PICKER,
                            'validator' => Mandatory::class
                        ],
                        'date_end' => [
                            'headline' => 'Date End',
                            'name' => 'date_end',
                            'type' => Field::INPUT_DATE_TIME_PICKER,
                            'validator' => Mandatory::class
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
                            'type' => Field::INPUT_TEXT,
                            'validator' => Mandatory::class
                        ],
                    ]
                ],
            ]
        ],
        'task' => [
            'model' => 'RedooTask',
            'headline' => 'Task',
            'blocks' => [
                'general' => [
                    'column_count' => 1,
                    'headline' => 'General',
                    'fields' => [
                        'title' => [
                            'headline' => 'Task Title',
                            'name' => 'title',
                            'type' => Field::INPUT_TEXT,
                            'validator' => Mandatory::class
                        ],
                    ]
                ],
                'date_time' => [
                    'column_count' => 2,
                    'headline' => 'Date and time',
                    'fields' => [
                        'date_start' => [
                            'headline' => 'Date Start',
                            'name' => 'date_start',
                            'type' => Field::INPUT_DATE_TIME_PICKER,
                            'validator' => Mandatory::class
                        ],
                        'date_end' => [
                            'headline' => 'Date End',
                            'name' => 'date_end',
                            'type' => Field::INPUT_DATE_TIME_PICKER, 'validator' => Mandatory::class
                        ],
                    ]
                ],
                'content' => [
                    'column_count' => 1,
                    'headline' => 'Content',
                    'fields' => [
                        'title' => [
                            'headline' => 'Task Description',
                            'name' => 'description',
                            'type' => Field::INPUT_TEXT,
                            'validator' => Mandatory::class
                        ],
                    ]
                ],
            ]
        ],
    ];


    public function __construct($settings)
    {
        parent::__construct($settings);
    }

    /**
     * @return \Google_Client
     */
    public function getGoogleClient()
    {
        $clientToken = Config::get('google_clientid', null);

        if (empty($clientToken)) {
            throw new \Exception('CONFIGERROR');
        }

        $clientSecret = Config::get('google_secret', null);
        global $site_URL;

        $googleConnection = new Connection();
        $googleConnection->fetchByCode($this->data['code']);
        $settings = json_decode(html_entity_decode($googleConnection->getSettings()), true);

        $apiClient = new \Google_Client();
        $apiClient->setApplicationName('Google Calendar API PHP Quickstart');
        $apiClient->setAuthConfig(json_encode(array(
            'web' =>
            array(
                'client_id' => $clientToken,
                'project_id' => 'redoo-calendar',
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_secret' => $clientSecret,
                'redirect_uris' =>
                array(
                    0 => $site_URL . 'index.php?module=RedooCalendar&action=GoogleApiToken',
                ),
            ),
        )));
        $apiClient->setAccessType('offline');
        $apiClient->setPrompt('select_account consent');
        $apiClient->setScopes(\Google_Service_Calendar::CALENDAR);

        if (!empty($settings['access_token'])) {
            $apiClient->setAccessToken($settings['access_token']);
        }

        return $apiClient;
    }

    public function fetchAccessTokenFromCode($code)
    {
        $googleClient = $this->getGoogleClient();

        $googleClient->fetchAccessTokenWithAuthCode();
    }
    /**
     * Get google api client
     *
     * @param bool $redirect
     * @return Google
     * @throws \Google_Exception
     */
    public function getApiClient(bool $redirect = false): Google
    {
        $apiClient = $this->getGoogleClient();
        /*
        if (isset($settings['token'])) {
            $apiClient->setAccessToken($settings['token']);
        }

        if (isset($settings['oauth_key'])) {
            if ($apiClient->isAccessTokenExpired()) {
                if ($apiClient->getRefreshToken()) {
                    $apiClient->fetchAccessTokenWithRefreshToken($apiClient->getRefreshToken());
                } else {

                    $accessToken = $apiClient->fetchAccessTokenWithAuthCode($settings['oauth_key']);
                    $apiClient->setAccessToken($accessToken);

                    $googleConnection->setSettings(
                        json_encode(
                            array_merge(['token' => $accessToken], $settings)
                        )
                    );

                    $googleConnection->save();
                }
            }
            $this->apiClient = $apiClient;
        }
*/
        $this->apiClient = $apiClient;

        return $this;
    }

    public function getRedirectUrl(): string
    {
        $clientToken = Config::get('google_clientid', null);

        if (empty($clientToken)) {
            return 'index.php?module=RedooCalendar&parent=Settings&view=Configuration';
        }

        $apiClient = $this->getGoogleClient();

        $apiClient->setState(json_encode([
            'connection_id' => $this->data['id']
        ]));

        return $apiClient->createAuthUrl();
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
            if ($calendar) $calendar->setVisible($item->getVisible());
        }

        return array_values(array_filter($collection->getItemsAsArray(), function ($item) use ($subscribeCollection) {
            return in_array($item['id'], $subscribeCollection->getCalendarIds());
        }));
    }

    /**
     * Get calendars collection
     *
     * @return BaseExternalCollection
     * @throws \Exception
     */
    public function getCalendarCollection(): CollectionInterface
    {

        $this->getApiClient();

        if ($this->apiClient) {

            $calendarService = new \Google_Service_Calendar($this->apiClient);
            $calendarCollection = new CalendarGoogleCollection();

            /** @var \Google_Service_Calendar_CalendarListEntry $item */
            foreach ($calendarService->calendarList->listCalendarList()->getItems() as $_calendar) {
                $calendar = new CalendarGoogle();
                $calendar->setId($_calendar->getId());
                $calendar->setTitle($_calendar->getSummary());
                $calendar->setColor($_calendar->getBackgroundColor());

                /** @var \Google_Service_Calendar_Event $_event */
                foreach ($calendarService->events->listEvents($calendar->getId()) as $_event) {
                    $event = new EventGoogle();
                    $event->setId($_event->getId());
                    $event->setCalendarId($calendar->getId());
                    $event->setTitle($_event->getSummary());
                    $event->setDateStart((new \DateTime($_event->getStart()->getDateTime()))->format('Y-m-d H:i:s'));
                    $event->setDateEnd((new \DateTime($_event->getEnd()->getDateTime()))->format('Y-m-d H:i:s'));
                    $calendar->setEvent($event);
                }
                $calendarCollection->setItem($calendar);
            }
            return $calendarCollection;
        } else {
            throw new \Exception('api client not provided');
        }
    }

    /**
     * Create calendar
     *
     * @param \Vtiger_Request $request
     * @return CalendarInterface
     * @throws \Google_Exception
     */
    public function createCalendar(\Vtiger_Request $request): CalendarInterface
    {
        $this->getApiClient();

        $calendar = new CalendarGoogle($this->apiClient);
        $calendar->setTitle($request->get('title'));
        $calendar->setColor($request->get('color'));
        $calendar->setConnection($request->get('connector'));
        $calendar->save();

        $subscription = new Subscribe();
        $subscription->setConnectionId($calendar->getConnection());
        $subscription->setCalendarId($calendar->getId());
        $subscription->setVisible(true);
        $subscription->save();

        return $calendar;
    }

    public function getEvent(CalendarInterface $calendar, $eventId): EventInterface
    {
        $this->getApiClient();
        $calendarService = new \Google_Service_Calendar($this->apiClient);
        $_event = $calendarService->events->get($calendar->getId(), $eventId);
        $event = new EventGoogle();
        $event->setId($_event->getId());
        $event->setCalendarId($calendar->getId());
        $event->setDescription($_event->getDescription());
        $event->setTitle($_event->getSummary());
        $event->setDateStart((new \DateTime($_event->getStart()->getDateTime()))->format('Y-m-d H:i:s'));
        $event->setDateEnd((new \DateTime($_event->getEnd()->getDateTime()))->format('Y-m-d H:i:s'));

        return $event;
    }

    public function getCalendar(string $id): CalendarInterface
    {
        $this->getApiClient();
        $calendarService = new \Google_Service_Calendar($this->apiClient);
        $_calendar = $calendarService->calendarList->get($id);
        $calendar = new CalendarGoogle();
        $calendar->setId($_calendar->getId());
        $calendar->setTitle($_calendar->getSummary());
        $calendar->setColor($_calendar->getBackgroundColor());

        return $calendar;
    }

    public function createEvent(\Vtiger_Request $request): EventInterface
    {
        $this->getApiClient();
        $eventGoogle = new EventGoogle($this->apiClient);
        $eventGoogle->setConnector($request->get('connector'));
        $eventGoogle->setTitle($request->get('title'));
        $eventGoogle->setCalendarId($request->get('calendar_id'));
        $eventGoogle->setDateStart(
            (new DateTime('@' . $request->get('date_start_timestamp')))
                ->setTimezone(new DateTimeZone('Europe/Kiev'))
                ->format(\DateTime::ISO8601)
        );

        $eventGoogle->setDateEnd(
            (new DateTime('@' . $request->get('date_end_timestamp')))
                ->setTimezone(new DateTimeZone('Europe/Kiev'))
                ->format(\DateTime::ISO8601)
        );

        $eventGoogle->save();
        return $eventGoogle;
    }

    public function updateCalendar(CalendarInterface $calendar): CalendarInterface
    {
        // TODO: Implement updateCalendar() method.
    }

    public function updateEvent(\Vtiger_Request $request): EventInterface
    {
        $this->getApiClient();

        $eventGoogle = new EventGoogle($this->apiClient);

        $eventGoogle->setData($request->getAll());

        $eventGoogle->setDateStart(
            (new DateTime('@' . $request->get('date_start_timestamp')))
                ->setTimezone(new DateTimeZone('Europe/Kiev'))
                ->format(\DateTime::ISO8601)
        );

        if ($request->get('date_end_timestamp')) {
            $eventGoogle->setDateEnd(
                (new DateTime('@' . $request->get('date_end_timestamp')))
                    ->setTimezone(new DateTimeZone('Europe/Kiev'))
                    ->format(\DateTime::ISO8601)
            );
        }

        $eventGoogle->update();

        return $eventGoogle;
    }

    public function deleteCalendar(string $id): bool
    {
        $this->getApiClient();

        $calendar = new CalendarGoogle($this->apiClient);
        $calendar->setId($id);
        return $calendar->delete();
    }

    public function deleteEvent(EventInterface $event): bool
    {
        // TODO: Implement deleteEvent() method.
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
        $group->addField()
            ->setLabel('Share To')
            ->setBindValue('share_to_google')
            ->setName('share_to' . $this->getId())
            ->setRelated([
                'access_mode' => ['share'],
                'connector' => [$this->getId()]
            ])
            ->setChangeHandler('changeUsersList')
            ->setPlaceholder('email@domain.com, second.email@domain.com')
            ->setType(Field::INPUT_TEXT);
    }
}

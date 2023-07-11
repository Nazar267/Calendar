<?php

/**
 * Created by Stefan Warnat
 * User: Stefan
 * Date: 25.09.2017
 * Time: 17:22
 */

namespace RedooCalendar\Base\Connection;


use RedooCalendar\Base\Connection\ConnectorPlugin\ConnectorPluginInterface;
use RedooCalendar\Model\Base\CalendarInterface;
use RedooCalendar\Model\Base\EventInterface;
use RedooCalendar\Model\Calendar;
use RedooCalendar\Model\Event;

abstract class ConnectorPlugin implements ConnectorPluginInterface
{
    protected $eventConfig = [];
    protected $user;

    /**
     * Allow create new calendars
     *
     * @var bool
     */
    protected $allowCreateCalendars = true;
    protected $allowDeleteCalendars = true;
    protected $allowEditCalendars = true;

    protected $data = [];
    /**
     * @var string
     */
    protected $_EditorHeadline = 'Edit Event';
    /**
     * @var string
     */
    protected $_EditorSaveButtonText = 'Save Event';
    /**
     * @var string
     */
    protected $_EditorCreateButtonText = 'Create Event';

    /**
     * @var string
     */
    protected $_ServiceModule = 'RedooCalendar';

    /**
     * Support Connector the generator?
     *
     * @var bool
     */
    protected $_GeneratorSupport = false;

    /**
     * @var array
     */
    protected $_Settings = array();

    /**
     * Define, which features are supported
     *
     * @var array
     */
    protected $_Supports = array('time', 'allday');

    protected $_OAuth = false;

    public function __construct($data = [])
    {
        $this->_Settings = $data['settings'];
        $this->data = $data;
    }

    public function requireOAuth()
    {
        return $this->_OAuth;
    }

    public function setCalendar(Calendar $calendar)
    {
        $this->_Calendar = $calendar;
    }

    public function supports($key)
    {
        $key = strtolower($key);

        return in_array($key, $this->_Supports) !== false;
    }

    public function getEditorHeadline()
    {
        return vtranslate($this->_EditorHeadline, $this->_ServiceModule);
    }

    public function getEditorSaveButtontext()
    {
        return vtranslate($this->_EditorSaveButtonText, $this->_ServiceModule);
    }

    public function getEditorCreateButtontext()
    {
        return vtranslate($this->_EditorCreateButtonText, $this->_ServiceModule);
    }

    public function getFields()
    {
        $return = $this->getEditFields();

        return $return;
    }

    public function getServiceModule()
    {
        return $this->_ServiceModule;
    }

    public function processVCal(\Davaxi\VCalendar &$VCalendar)
    {
        // You can override this function to provide extra data
    }

    public function getConnectionFields()
    {
        return array();
    }

    public function getSettings(): array
    {
        return $this->_Settings;
    }

    public function getAccessToken(Connection $connection)
    {
        $settings = $connection->getSettings();
        $oAuthKey = $settings['access_token'];

        if (empty($oAuthKey)) {
            throw new \Exception('No Access Permission granted. Please authorize OAuth2 Application.');
        }

        $oauthObj = new OAuth($oAuthKey);
        $accessToken = $oauthObj->getAccessToken();

        return $accessToken;
    }

    public function getEventConfig(): array
    {
        return $this->eventConfig;
    }

    public function getRedirectUrl(): string
    {
        return '';
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getId(): int
    {
        return $this->getData()['id'];
    }

    public function getUserId(): int
    {
        return $this->getData()['user_id'];
    }

    public function getCurrentUser(): \Users_Record_Model
    {
        return \Users_Record_Model::getCurrentUserModel();
    }

    public function getUserTimeZone(\Users_Record_Model $user = null)
    {
        if (!$user) {
            $user = $this->getCurrentUser();
        }

        return new \DateTimeZone($user->get('time_zone'));
    }

    /**
     * Check if user can create new calendars
     *
     * @return bool
     */
    public function allowCreateCalendars(): bool
    {
        return $this->allowCreateCalendars;
    }
}

<?php

namespace RedooCalendar\Base\Connection\ConnectorPlugin;

use RedooCalendar\Base\Collection\CollectionInterface;
use RedooCalendar\Base\Form\Field;
use RedooCalendar\Base\Form\Group;
use RedooCalendar\Model\Base\CalendarInterface;
use RedooCalendar\Model\Base\EventInterface;
use RedooCalendar\Model\Calendar;
use RedooCalendar\Model\Event;
use RedooCalendar\Model\Base\CalendarInterface\Collection as CalendarCollectionInterface;

/**
 * Interface ConnectorPluginInterface
 * @package RedooCalendar\Base\Connection\ConnectorPlugin
 */
interface ConnectorPluginInterface
{

    /**
     * get event
     *
     * @param Calendar $calendar
     * @param $eventId
     * @return EventInterface
     */
    function getEvent(CalendarInterface $calendar, $eventId): EventInterface;

    /**
     * Create calendar event
     *
     * @param \Vtiger_Request $request
     * @return EventInterface
     */
    function createEvent(\Vtiger_Request $request): EventInterface;

    /**
     * Update event
     *
     * @param \Vtiger_Request $request
     * @return EventInterface
     */
    function updateEvent(\Vtiger_Request $request): EventInterface;

    /**
     * Delete event
     *
     * @param Event $event
     * @return bool
     */
    function deleteEvent(EventInterface $event): bool;

    /**
     * Get calendar collection
     *
     * @return CollectionInterface
     */
    function getCalendarCollection(): CollectionInterface;

    /**
     * Get subscribed calendar collection
     *
     * @return array
     */
    function getSubscribedCalendarCollection(): array;

    /**
     * Get calendar by ID
     *
     * @param string $id
     * @return CalendarInterface
     */
    function getCalendar(string $id): CalendarInterface;

    /**
     * Create calendar
     *
     * @param \Vtiger_Request $request
     * @return CalendarInterface
     */
    function createCalendar(\Vtiger_Request $request): CalendarInterface;

    /**
     * Update calendar
     *
     * @param CalendarInterface $calendar
     * @return CalendarInterface
     */
    function updateCalendar(CalendarInterface $calendar): CalendarInterface;

    /**
     * Delete calendar
     *
     * @param string $id
     * @return bool
     */
    function deleteCalendar(string $id): bool;

    function getEventConfig(): array;

    function getSettings(): array;

    function getRedirectUrl(): string;

    function getId(): int;

    /**
     * Get calendar collection
     *
     * @return array
     */
    function getUnsubscribedCalendarCollection(): array;

    function getShareUserList(Group &$group);

    /**
     * Check if user can create new calendars
     *
     * @return bool
     */
    function allowCreateCalendars(): bool;
}
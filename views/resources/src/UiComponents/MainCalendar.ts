///<reference path="../../node_modules/@types/jquery.contextmenu/index.d.ts" />

import {UiComponent} from '../Core/UiComponent/UiComponent'
import {Calendar} from '@fullcalendar/core';
import {Calendar as CalendarModel} from '../Model/Calendar';
import interactionPlugin from '@fullcalendar/interaction';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import {Event} from "../Model/Event";
import {App} from "../app";
import Dialog = kendo.ui.Dialog;
import {ConnectorCollection} from "../Model/Collection/ConnectorCollection";
import {Connector} from "../Model/Connector";
import {EventTypes} from "../Model/EventTypes";
import {ServiceProvider} from "../Core/ServiceProvider/ServiceProvider";
import {Loading} from "./Loading";
import {FORMGenerator} from "../Core/Form/FORMGenerator";
import {DateTime} from "../Helpers/DateTime";
import {EventApi} from "@fullcalendar/core/api/EventApi";


export class MainCalendar extends UiComponent {

    protected calendar: Calendar;
    protected createEventDialog: Dialog;
    protected editEventDialog: Dialog;
    protected viewEventDialog: Dialog;
    protected connectorCollection: ConnectorCollection;
    protected newEvent: Event;
    protected loading: Loading;
    protected systemTimeZone: string;

    public constructor(props: any) {
        super(props);
        this.connectorCollection = new ConnectorCollection();
    }

    public init() {
        this.loading = ServiceProvider.getInstance().get('loading') as Loading;
        let _this = this;

        RedooAjax(App.SCOPE_NAME).postAction('GetUserTimeZone', {}, false, 'json').then(function (response) {
            _this.systemTimeZone = response.user_time_zone;
        });
        _this.calendar = new Calendar(_this.element, {
            locale: _this.appLanguage.substring(0, 2),
            plugins: [timeGridPlugin, dayGridPlugin, interactionPlugin],
            defaultView: 'timeGridWeek',
            selectable: true,
            themeSystem: 'bootstrap',
            header: {
                right: 'prev,today,next',
                center: 'title',
                left: 'dayGridMonth,agendaFourWeeks,timeGridWeek,timeGridDay'
            },
            views: {
                dayGridMonth: {
                    buttonText: _this.translator('Month'),
                },
                timeGridWeek: {
                    buttonText: _this.translator('Week'),
                },
                timeGridDay: {
                    buttonText: _this.translator('Day'),
                },
                agendaFourWeeks: {
                    type: 'dayGridMonth',
                    fixedWeekCount: false,
                    duration: {
                        weeks: 4
                    },
                    buttonText: _this.translator('4 Weeks'),
                    dateIncrement: {
                        weeks: 1
                    },
                }
            },
            select: function (event) {
                let start = DateTime.getSystemDate(event.start as Date);
                let end = DateTime.getSystemDate(event.end as Date);
                if (event.allDay) {
                    start.setHours(12);
                    end.setHours(12);
                }

                _this.initCreateEventDialog(start, end);
            },

            eventClick: function (event) {
                _this.eventClick(event);
            },
            eventDrop: function (event) {
                _this.eventChange(event);
            },
            eventResize: function (event) {
                _this.eventChange(event);
            },
            eventRender: function (arg) {
                jQuery(arg.el).attr('data-connector-id', arg.event.extendedProps.connector);
                jQuery(arg.el).attr('data-calendar-id', arg.event.extendedProps.calendar_id);
                jQuery(arg.el).attr('data-event-id', arg.event.extendedProps.event.data.id);
            },
            editable: true,
            allDaySlot: true,
        });

       jQuery.contextMenu({
            selector: '.fc-event',//note the selector this will apply context to all events
            trigger: 'right',
            callback: function (key, options) {
                switch (key) {
                    case 'edit_event':
                        _this.initEditEventDialog(jQuery(options.$trigger.context));
                        break;
                    case 'del_event':
                        _this.eventDelete(jQuery(options.$trigger.context))
                        break;
                }

            },
            items: {
                'edit_event': {name: _this.translator('Edit Event')},
                'del_event': {name: _this.translator('Delete Event')},
            }
        });

        _this.calendar.render();

        return this;
    }

    public setDate(date: Date) {
        this.calendar.gotoDate(date);
        return this;
    }

    public getDate(): Date {
        return this.calendar.getDate();
    }

    public setConnectorCollection(collection: ConnectorCollection): MainCalendar {
        this.connectorCollection = collection;
        this.renderEventsByEnabledCalendars();
        return this;
    }

    protected renderEventsByEnabledCalendars(): MainCalendar {
        this.loading.startLoading();
        this.calendar.removeAllEvents();

        for (let _connectorIndex = 0; _connectorIndex < this.connectorCollection.items.length; _connectorIndex++) {
            let connector = this.connectorCollection.items[_connectorIndex] as Connector;

            for (let _calendarIndex = 0; _calendarIndex < connector.calendars.items.length; _calendarIndex++) {
                let calendar = connector.calendars.items[_calendarIndex] as CalendarModel;

                if (!calendar.data.visible) continue;

                for (let _eventIndex = 0; _eventIndex < calendar.getEvents().length; _eventIndex++) {
                    let event = calendar.getEvents()[_eventIndex] as Event;
                    this.calendar.addEvent({
                        allDay: event.getData('all_day_event'),
                        start: event.getData('date_start'),
                        connector: event.getData('connector'),
                        calendar_id: event.getData('calendar_id'),
                        end: event.getData('date_end'),
                        title: event.getData('title'),
                        color: calendar.data.color,
                        id: event.getData('id'),
                        extendedProps: {
                            calendar: calendar,
                            event: event
                        }
                    });
                }
            }
        }
        this.loading.stopLoading();
        return this;
    }

    public initCreateEventDialog(start: Date, end: Date) {
        this.loading.startLoading();
        let _this = this;
        let event: Event;
        let form: FORMGenerator;
        let connector: Connector;

        RedooAjax(App.SCOPE_NAME).postView('AddEventSelectCalendarModal', {}, false, 'json').then(function (response) {
            let html = response.html;

            let mainBlock = document.createElement('div') as HTMLDivElement;
            mainBlock.id = 'create-event-main-form';

            html += mainBlock.outerHTML;

            _this.createEventDialog = jQuery('#create-event-dialog').kendoDialog({
                    width: '50%',
                    animation: {
                        open: {
                            duration: 100,
                            effects: "fade:in"
                        }
                    },
                    title: _this.translator('Create Event'),
                    visible: false,
                    content: html,
                    actions: [
                        {
                            text: _this.translator('Cancel')
                        },
                        {
                            text: _this.translator('Create'),
                            primary: true,
                            action: function (e: any) {
                                return _this.createEvent(event, connector, form);
                            },
                        },
                    ],
                    close: function () {
                        _this.createEventDialog.destroy();
                        jQuery('#modals').append('<div id="create-event-dialog"></div>')
                    },
                    initOpen: function () {
                        let buttonsBlock = _this.createEventDialog.element.parent().find('.k-dialog-button-layout-stretched');
                        buttonsBlock.hide();
                        FORMGenerator.start();
                        eval(response.js);
                        FORMGenerator.init();

                        let calendarModel = kendo.observable({
                                calendar_id: '',
                                changeCalendar: function (e: any) {

                                    _this.loading.startLoading();
                                    _this.createEventDialog.element.find('.k-dialog-button-layout-stretched').slideDown(200);

                                    jQuery('#create-event-main-form').html('');
                                    if (e.data.calendar_id) {
                                        let loader = new Loading('create-event-dialog');
                                        loader.startLoading();

                                        let calendarData = e.data.calendar_id.split(':');

                                        RedooAjax(App.SCOPE_NAME).postView('AddEventModal', {
                                            calendar_id: calendarData[1],
                                            connector: calendarData[0]
                                        }, false, 'json').then(function (_response) {

                                            connector = _this.connectorCollection.getItem(_response.connector.id) as Connector;

                                            jQuery('#create-event-main-form').html(_response.form.html);
                                            form = FORMGenerator.start();

                                            form.setValidators(_response.form.validators);
                                            eval(_response.form.js);
                                            form.init();
                                            form.setContainer('#create-event-main-form');

                                            setTimeout(function () {
                                                let switchModelHandler = function (model: string) {
                                                    event = new (<any>EventTypes)[model]();
                                                    event.setObservable(jQuery('#create-event #' + model)[0]);
                                                    event.addData('date_start', start);
                                                    event.addData('date_end', end);
                                                    event.addData('calendar_id', calendarData[1]);
                                                    event.addData('connector', connector.getData('id'));
                                                    _this.createEventDialog.toFront();
                                                };

                                                let switchModel = kendo.observable({
                                                    switchModel: function (switchModelEvent: any) {
                                                        switchModelHandler(switchModelEvent.target.dataset.model)
                                                    }
                                                });

                                                switchModelHandler(jQuery('#create-event-tabs li').first().data('model'));

                                                kendo.bind(jQuery('#create-event-tabs').first(), switchModel);
                                                buttonsBlock.slideDown(200);

                                                loader.stopLoading();
                                            });
                                            _this.loading.stopLoading();
                                        });
                                    }
                                },
                            }
                        );

                        kendo.bind(jQuery('#create-event-select-calendar'), calendarModel);
                        _this.loading.stopLoading();
                    }
                }
            ).data("kendoDialog");

            _this.createEventDialog.open();
        });

    }

    public createEvent(event: Event, connector: Connector, form: FORMGenerator): boolean {
        if (!form.isValid()) return false;
        this.loading.startLoading();
        let _this = this;
        let calendar = connector.calendars.getItem(event.getData('calendar_id')) as CalendarModel;

        // Will set UTC times
        event.addData('date_start_timestamp', DateTime.getTimestamp(event.getData('date_start')));
        event.addData('date_end_timestamp', DateTime.getTimestamp(event.getData('date_end')));

        RedooAjax(App.SCOPE_NAME).postAction(
            'EventAdd',
            event.getAll(),
            false,
            'json'
        ).then(function (response) {
            event.setData(response.event);
            calendar.addEvent(event);
            _this.renderEventsByEnabledCalendars();
            _this.createEventDialog.close();
            _this.loading.stopLoading();
        });
        return true;
    }

    protected eventClick(event: any) {
        let _this = this;
        _this.loading.startLoading();

        RedooAjax(App.SCOPE_NAME).postView('ViewEventModal', event.event.extendedProps.event.getAll(), false).then(function (response) {
            _this.viewEventDialog = jQuery('#event-content-dialog').kendoDialog({
                width: '50%',
                title: _this.translator('Event'),
                visible: false,
                content: response,
                close: function () {
                    _this.viewEventDialog.destroy();
                    jQuery('#modals').append('<div id="event-content-dialog"></div>')
                },
                initOpen: function () {
                    jQuery("#event-toolbar").kendoToolBar({
                        items: [

                            {
                                type: "button",
                                icon: "edit",
                                text: "Edit",
                                click: function (event: any) {

                                }
                            },
                            {
                                type: "button",
                                icon: "delete",
                                text: "Delete",
                                click: function (event: any) {

                                }
                            },

                        ]
                    });
                }
            }).data("kendoDialog");

            _this.viewEventDialog.open();
            _this.loading.stopLoading();
        });
    }

    protected eventChange(event: any) {
        this.loading.startLoading();
        let _this = this;

        let eventModel = event.event.extendedProps.event;


        let start = DateTime.getSystemDate(event.event.start);

        let end;
        if (event.event.end) {
            end = DateTime.getSystemDate(event.event.end);
        }

        if (eventModel.getData('all_day_event')) {
            start.setHours(12);
//            end = start;
        }

        let connector = this.connectorCollection.getItem(eventModel.data.connector) as Connector;
        eventModel.data.date_start_timestamp = DateTime.getTimestamp(start);
        if (end) {
            eventModel.data.date_end_timestamp = DateTime.getTimestamp(end);
        } else {
            eventModel.data.date_end_timestamp = eventModel.data.date_start_timestamp
        }
        RedooAjax(App.SCOPE_NAME).postAction('EventUpdate', {
                connector: connector.data.id,
                calendar_id: eventModel.data.calendar_id,
                id: eventModel.data.id,
                title: eventModel.data.title,
                date_start_timestamp: eventModel.data.date_start_timestamp,
                date_end_timestamp: eventModel.data.date_end_timestamp,
            },

            false, 'json').then(function (response) {
            if (response.status) {
                _this.notifications.info(response.message);
            } else {
                _this.notifications.error(response.message);
            }

            _this.loading.stopLoading();
        });
    }

    public getSystemTimeZone(): string {
        return this.systemTimeZone;
    }

    protected eventDelete(element: JQuery) {
        if (!window.confirm(app.vtranslate('Delete event?'))) return false;

        this.loading.startLoading();
        let _this = this;
        let eventId = element.data('event-id');
        let calendarId = element.data('calendar-id');
        let connectorId = element.data('connector-id');

        let connection = _this.connectorCollection.getItem(connectorId) as Connector;
        let calendar = connection.calendars.getItem(calendarId) as CalendarModel;

        RedooAjax(App.SCOPE_NAME).postAction('EventDelete', {
            id: eventId,
            calendar_id: calendarId,
            connector: connectorId
        }, false, 'json').then(function (response) {
            if (response.status) {
                calendar.removeEvent(eventId);
                connection.calendars.updateItem(calendar);
                _this.connectorCollection.updateItem(connection);
                _this.renderEventsByEnabledCalendars();
                _this.notifications.info(response.message);
            } else {
                _this.notifications.error(response.message);
            }
            _this.loading.stopLoading();
        })

    }


    public initEditEventDialog(element: JQuery) {
        //if (!window.confirm(app.vtranslate('Do you want to change the title of the event?'))) return false;
        this.loading.startLoading();
        let _this = this;

        let eventId = element.data('event-id');
        let calendarId = element.data('calendar-id');
        let connectorId = element.data('connector-id');



        let connection = _this.connectorCollection.getItem(connectorId) as Connector;
        let calendar = connection.calendars.getItem(calendarId) as CalendarModel;

        let form: FORMGenerator;
        let event: Event;

        RedooAjax(App.SCOPE_NAME).postView('EditEventModal', {
            id: eventId,
            calendar_id: calendarId,
            connector: connectorId
        }, false, 'json').then(function (response) {

            _this.editEventDialog = jQuery('#edit-event-dialog').kendoDialog({
                width: '50%',
                animation: {
                    open: {
                        duration: 100,
                        effects: "fade:in"
                    }
                },
                title: _this.translator('Edit Event'),
                visible: false,
                content: response.form.html,
                actions: [
                    {
                        text: _this.translator('Cancel')
                    },
                    {
                        text: _this.translator('Update'),
                        primary: true,
                        action: function (e: any) {
                            console.log(event);
                            return _this.updateEvent(event, form, connection, calendar);
                        },
                    },
                ],
                close: function () {
                    _this.editEventDialog.destroy();
                    jQuery('#modals').append('<div id="edit-event-dialog"></div>')
                },
                initOpen: function () {

                    form = FORMGenerator.start();

                    form.setValidators(response.form.validators);
                    eval(response.form.js);
                    form.init();
                    form.setContainer('#edit-event-dialog');

                    event = new Event();
                    event.setObservable(document.getElementById('edit-event-dialog') as HTMLElement);
                }
            }).data("kendoDialog");

            _this.editEventDialog.open();
            _this.loading.stopLoading();
        });
    }


    protected updateEvent(event: Event, form: FORMGenerator, connection: Connector, calendar: CalendarModel) {
        event.addData('date_start', DateTime.getSystemDate(event.getData('date_start')));
        event.addData('date_end', DateTime.getSystemDate(event.getData('date_end')));

        event.addData('date_start_timestamp', DateTime.getTimestamp(event.getData('date_start')));
        event.addData('date_end_timestamp', DateTime.getTimestamp(event.getData('date_end')));

        this.loading.startLoading();
        let _this = this;

        RedooAjax(App.SCOPE_NAME).postAction('EventUpdate', event.getAll(),
            false, 'json').then(function (response) {
            if (response.status) {
                event.setData(response.event);
                calendar.updateEvent(event);
                connection.calendars.updateItem(calendar);
                _this.renderEventsByEnabledCalendars();

                _this.notifications.info(response.message);
            } else {
                _this.notifications.error(response.message);
            }

            _this.loading.stopLoading();
        });
    }
}
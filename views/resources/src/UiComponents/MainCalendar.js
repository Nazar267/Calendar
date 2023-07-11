"use strict";
///<reference path="../../node_modules/@types/jquery.contextmenu/index.d.ts" />
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.MainCalendar = void 0;
var UiComponent_1 = require("../Core/UiComponent/UiComponent");
var core_1 = require("@fullcalendar/core");
var interaction_1 = require("@fullcalendar/interaction");
var daygrid_1 = require("@fullcalendar/daygrid");
var timegrid_1 = require("@fullcalendar/timegrid");
var Event_1 = require("../Model/Event");
var app_1 = require("../app");
var ConnectorCollection_1 = require("../Model/Collection/ConnectorCollection");
var EventTypes_1 = require("../Model/EventTypes");
var ServiceProvider_1 = require("../Core/ServiceProvider/ServiceProvider");
var Loading_1 = require("./Loading");
var DateTime_1 = require("../Helpers/DateTime");
var MainCalendar = /** @class */ (function (_super) {
    __extends(MainCalendar, _super);
    function MainCalendar(props) {
        var _this_1 = _super.call(this, props) || this;
        _this_1.connectorCollection = new ConnectorCollection_1.ConnectorCollection();
        return _this_1;
    }
    MainCalendar.prototype.init = function () {
        this.loading = ServiceProvider_1.ServiceProvider.getInstance().get('loading');
        var _this = this;
        RedooAjax(app_1.App.SCOPE_NAME).postAction('GetUserTimeZone', {}, false, 'json').then(function (response) {
            _this.systemTimeZone = response.user_time_zone;
        });
        _this.calendar = new core_1.Calendar(_this.element, {
            locale: _this.appLanguage.substring(0, 2),
            plugins: [timegrid_1.default, daygrid_1.default, interaction_1.default],
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
                var start = DateTime_1.DateTime.getSystemDate(event.start);
                var end = DateTime_1.DateTime.getSystemDate(event.end);
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
            selector: '.fc-event',
            trigger: 'right',
            callback: function (key, options) {
                switch (key) {
                    case 'edit_event':
                        _this.initEditEventDialog(jQuery(options.$trigger.context));
                        break;
                    case 'del_event':
                        _this.eventDelete(jQuery(options.$trigger.context));
                        break;
                }
            },
            items: {
                'edit_event': { name: _this.translator('Edit Event') },
                'del_event': { name: _this.translator('Delete Event') },
            }
        });
        _this.calendar.render();
        return this;
    };
    MainCalendar.prototype.setDate = function (date) {
        this.calendar.gotoDate(date);
        return this;
    };
    MainCalendar.prototype.getDate = function () {
        return this.calendar.getDate();
    };
    MainCalendar.prototype.setConnectorCollection = function (collection) {
        this.connectorCollection = collection;
        this.renderEventsByEnabledCalendars();
        return this;
    };
    MainCalendar.prototype.renderEventsByEnabledCalendars = function () {
        this.loading.startLoading();
        this.calendar.removeAllEvents();
        for (var _connectorIndex = 0; _connectorIndex < this.connectorCollection.items.length; _connectorIndex++) {
            var connector = this.connectorCollection.items[_connectorIndex];
            for (var _calendarIndex = 0; _calendarIndex < connector.calendars.items.length; _calendarIndex++) {
                var calendar = connector.calendars.items[_calendarIndex];
                if (!calendar.data.visible)
                    continue;
                for (var _eventIndex = 0; _eventIndex < calendar.getEvents().length; _eventIndex++) {
                    var event_1 = calendar.getEvents()[_eventIndex];
                    this.calendar.addEvent({
                        allDay: event_1.getData('all_day_event'),
                        start: event_1.getData('date_start'),
                        connector: event_1.getData('connector'),
                        calendar_id: event_1.getData('calendar_id'),
                        end: event_1.getData('date_end'),
                        title: event_1.getData('title'),
                        color: calendar.data.color,
                        id: event_1.getData('id'),
                        extendedProps: {
                            calendar: calendar,
                            event: event_1
                        }
                    });
                }
            }
        }
        this.loading.stopLoading();
        return this;
    };
    MainCalendar.prototype.initCreateEventDialog = function (start, end) {
        this.loading.startLoading();
        var _this = this;
        var event;
        var form;
        var connector;
        RedooAjax(app_1.App.SCOPE_NAME).postView('AddEventSelectCalendarModal', {}, false, 'json').then(function (response) {
            var html = response.html;
            var mainBlock = document.createElement('div');
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
                        action: function (e) {
                            return _this.createEvent(event, connector, form);
                        },
                    },
                ],
                close: function () {
                    _this.createEventDialog.destroy();
                    jQuery('#modals').append('<div id="create-event-dialog"></div>');
                },
                initOpen: function () {
                    var buttonsBlock = _this.createEventDialog.element.parent().find('.k-dialog-button-layout-stretched');
                    buttonsBlock.hide();
                    FORMGenerator.start();
                    eval(response.js);
                    FORMGenerator.init();
                    var calendarModel = kendo.observable({
                        calendar_id: '',
                        changeCalendar: function (e) {
                            _this.loading.startLoading();
                            _this.createEventDialog.element.find('.k-dialog-button-layout-stretched').slideDown(200);
                            jQuery('#create-event-main-form').html('');
                            if (e.data.calendar_id) {
                                var loader_1 = new Loading_1.Loading('create-event-dialog');
                                loader_1.startLoading();
                                var calendarData_1 = e.data.calendar_id.split(':');
                                RedooAjax(app_1.App.SCOPE_NAME).postView('AddEventModal', {
                                    calendar_id: calendarData_1[1],
                                    connector: calendarData_1[0]
                                }, false, 'json').then(function (_response) {
                                    connector = _this.connectorCollection.getItem(_response.connector.id);
                                    jQuery('#create-event-main-form').html(_response.form.html);
                                    form = FORMGenerator.start();
                                    form.setValidators(_response.form.validators);
                                    eval(_response.form.js);
                                    form.init();
                                    form.setContainer('#create-event-main-form');
                                    setTimeout(function () {
                                        var switchModelHandler = function (model) {
                                            event = new EventTypes_1.EventTypes[model]();
                                            event.setObservable(jQuery('#create-event #' + model)[0]);
                                            event.addData('date_start', start);
                                            event.addData('date_end', end);
                                            event.addData('calendar_id', calendarData_1[1]);
                                            event.addData('connector', connector.getData('id'));
                                            _this.createEventDialog.toFront();
                                        };
                                        var switchModel = kendo.observable({
                                            switchModel: function (switchModelEvent) {
                                                switchModelHandler(switchModelEvent.target.dataset.model);
                                            }
                                        });
                                        switchModelHandler(jQuery('#create-event-tabs li').first().data('model'));
                                        kendo.bind(jQuery('#create-event-tabs').first(), switchModel);
                                        buttonsBlock.slideDown(200);
                                        loader_1.stopLoading();
                                    });
                                    _this.loading.stopLoading();
                                });
                            }
                        },
                    });
                    kendo.bind(jQuery('#create-event-select-calendar'), calendarModel);
                    _this.loading.stopLoading();
                }
            }).data("kendoDialog");
            _this.createEventDialog.open();
        });
    };
    MainCalendar.prototype.createEvent = function (event, connector, form) {
        if (!form.isValid())
            return false;
        this.loading.startLoading();
        var _this = this;
        var calendar = connector.calendars.getItem(event.getData('calendar_id'));
        // Will set UTC times
        event.addData('date_start_timestamp', DateTime_1.DateTime.getTimestamp(event.getData('date_start')));
        event.addData('date_end_timestamp', DateTime_1.DateTime.getTimestamp(event.getData('date_end')));
        RedooAjax(app_1.App.SCOPE_NAME).postAction('EventAdd', event.getAll(), false, 'json').then(function (response) {
            event.setData(response.event);
            calendar.addEvent(event);
            _this.renderEventsByEnabledCalendars();
            _this.createEventDialog.close();
            _this.loading.stopLoading();
        });
        return true;
    };
    MainCalendar.prototype.eventClick = function (event) {
        var _this = this;
        _this.loading.startLoading();
        RedooAjax(app_1.App.SCOPE_NAME).postView('ViewEventModal', event.event.extendedProps.event.getAll(), false).then(function (response) {
            _this.viewEventDialog = jQuery('#event-content-dialog').kendoDialog({
                width: '50%',
                title: _this.translator('Event'),
                visible: false,
                content: response,
                close: function () {
                    _this.viewEventDialog.destroy();
                    jQuery('#modals').append('<div id="event-content-dialog"></div>');
                },
                initOpen: function () {
                    jQuery("#event-toolbar").kendoToolBar({
                        items: [
                            {
                                type: "button",
                                icon: "edit",
                                text: "Edit",
                                click: function (event) {
                                }
                            },
                            {
                                type: "button",
                                icon: "delete",
                                text: "Delete",
                                click: function (event) {
                                }
                            },
                        ]
                    });
                }
            }).data("kendoDialog");
            _this.viewEventDialog.open();
            _this.loading.stopLoading();
        });
    };
    MainCalendar.prototype.eventChange = function (event) {
        this.loading.startLoading();
        var _this = this;
        var eventModel = event.event.extendedProps.event;
        var start = DateTime_1.DateTime.getSystemDate(event.event.start);
        var end;
        if (event.event.end) {
            end = DateTime_1.DateTime.getSystemDate(event.event.end);
        }
        if (eventModel.getData('all_day_event')) {
            start.setHours(12);
            //            end = start;
        }
        var connector = this.connectorCollection.getItem(eventModel.data.connector);
        eventModel.data.date_start_timestamp = DateTime_1.DateTime.getTimestamp(start);
        if (end) {
            eventModel.data.date_end_timestamp = DateTime_1.DateTime.getTimestamp(end);
        }
        else {
            eventModel.data.date_end_timestamp = eventModel.data.date_start_timestamp;
        }
        RedooAjax(app_1.App.SCOPE_NAME).postAction('EventUpdate', {
            connector: connector.data.id,
            calendar_id: eventModel.data.calendar_id,
            id: eventModel.data.id,
            title: eventModel.data.title,
            date_start_timestamp: eventModel.data.date_start_timestamp,
            date_end_timestamp: eventModel.data.date_end_timestamp,
        }, false, 'json').then(function (response) {
            if (response.status) {
                _this.notifications.info(response.message);
            }
            else {
                _this.notifications.error(response.message);
            }
            _this.loading.stopLoading();
        });
    };
    MainCalendar.prototype.getSystemTimeZone = function () {
        return this.systemTimeZone;
    };
    MainCalendar.prototype.eventDelete = function (element) {
        if (!window.confirm(app.vtranslate('Delete event?')))
            return false;
        this.loading.startLoading();
        var _this = this;
        var eventId = element.data('event-id');
        var calendarId = element.data('calendar-id');
        var connectorId = element.data('connector-id');
        var connection = _this.connectorCollection.getItem(connectorId);
        var calendar = connection.calendars.getItem(calendarId);
        RedooAjax(app_1.App.SCOPE_NAME).postAction('EventDelete', {
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
            }
            else {
                _this.notifications.error(response.message);
            }
            _this.loading.stopLoading();
        });
    };
    MainCalendar.prototype.initEditEventDialog = function (element) {
        //if (!window.confirm(app.vtranslate('Do you want to change the title of the event?'))) return false;
        this.loading.startLoading();
        var _this = this;
        var eventId = element.data('event-id');
        var calendarId = element.data('calendar-id');
        var connectorId = element.data('connector-id');
        var connection = _this.connectorCollection.getItem(connectorId);
        var calendar = connection.calendars.getItem(calendarId);
        var form;
        var event;
        RedooAjax(app_1.App.SCOPE_NAME).postView('EditEventModal', {
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
                        action: function (e) {
                            console.log(event);
                            return _this.updateEvent(event, form, connection, calendar);
                        },
                    },
                ],
                close: function () {
                    _this.editEventDialog.destroy();
                    jQuery('#modals').append('<div id="edit-event-dialog"></div>');
                },
                initOpen: function () {
                    form = FORMGenerator.start();
                    form.setValidators(response.form.validators);
                    eval(response.form.js);
                    form.init();
                    form.setContainer('#edit-event-dialog');
                    event = new Event_1.Event();
                    event.setObservable(document.getElementById('edit-event-dialog'));
                }
            }).data("kendoDialog");
            _this.editEventDialog.open();
            _this.loading.stopLoading();
        });
    };
    MainCalendar.prototype.updateEvent = function (event, form, connection, calendar) {
        event.addData('date_start', DateTime_1.DateTime.getSystemDate(event.getData('date_start')));
        event.addData('date_end', DateTime_1.DateTime.getSystemDate(event.getData('date_end')));
        event.addData('date_start_timestamp', DateTime_1.DateTime.getTimestamp(event.getData('date_start')));
        event.addData('date_end_timestamp', DateTime_1.DateTime.getTimestamp(event.getData('date_end')));
        this.loading.startLoading();
        var _this = this;
        RedooAjax(app_1.App.SCOPE_NAME).postAction('EventUpdate', event.getAll(), false, 'json').then(function (response) {
            if (response.status) {
                event.setData(response.event);
                calendar.updateEvent(event);
                connection.calendars.updateItem(calendar);
                _this.renderEventsByEnabledCalendars();
                _this.notifications.info(response.message);
            }
            else {
                _this.notifications.error(response.message);
            }
            _this.loading.stopLoading();
        });
    };
    return MainCalendar;
}(UiComponent_1.UiComponent));
exports.MainCalendar = MainCalendar;

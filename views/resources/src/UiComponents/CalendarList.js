"use strict";
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
exports.CalendarList = void 0;
var UiComponent_1 = require("../Core/UiComponent/UiComponent");
var Calendar_1 = require("../Model/Calendar");
var ServiceProvider_1 = require("../Core/ServiceProvider/ServiceProvider");
var app_1 = require("../app");
var Event_1 = require("../Model/Event");
var ConnectorCollection_1 = require("../Model/Collection/ConnectorCollection");
var Connector_1 = require("../Model/Connector");
var Subscribe_1 = require("../Model/Subscribe");
var GenerateCalendar_1 = require("../Model/GenerateCalendar");
var CalendarList = /** @class */ (function (_super) {
    __extends(CalendarList, _super);
    function CalendarList() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    CalendarList.prototype.init = function () {
        this.loading = ServiceProvider_1.ServiceProvider.getInstance().get('loading');
        this.initModel();
        return this;
    };
    CalendarList.prototype.initModel = function () {
        this.loading.startLoading();
        var _this = this;
        RedooAjax('RedooCalendar').postAction('GetSubscribedCalendars', {}, false, 'json').then(function (response) {
            _this.viewModel = kendo.observable({
                connectorCollection: {},
                calendarListChange: function (event) {
                    _this.calendarListChangeHandler(event);
                },
                createConnection: function (event) {
                    _this.createConnectionInitDialog(event);
                },
                subscribeCalendar: function () {
                    _this.openSubscribeCalendar();
                }
            });
            jQuery("#main-list-menu").kendoToolBar({
                items: [
                    {
                        type: "button",
                        icon: "link-horizontal",
                        text: _this.translator('New Connection'),
                        click: function (event) {
                            _this.createConnectionInitDialog(event);
                        }
                    },
                    {
                        type: "button",
                        icon: "plus-outline",
                        text: _this.translator("New Calendar"),
                        click: function (event) {
                            _this.initCreateCalendarDialog();
                        }
                    },
                    {
                        type: "button",
                        icon: "plus-outline",
                        text: _this.translator("Generate Calendar"),
                        click: function (event) {
                            _this.initGenerateCalendarDialog();
                        }
                    },
                    {
                        type: "button",
                        icon: "zoom",
                        text: _this.translator("More Calendars"),
                        click: function (event) {
                            _this.openSubscribeCalendar();
                        }
                    },
                    {
                        type: "button",
                        icon: "zoom",
                        text: _this.translator("Configuration"),
                        click: function (event) {
                            window.location.href = 'index.php?module=RedooCalendar&parent=Settings&view=Config';
                        }
                    },
                ]
            });
            _this.initCollection(response.connectors);
            _this.bindViewElement();
            _this.loading.stopLoading();
        });
        return this;
    };
    CalendarList.prototype.initCollection = function (data) {
        if (data === void 0) { data = null; }
        var _this = this;
        if (data) {
            var connectorCollection = new ConnectorCollection_1.ConnectorCollection();
            for (var connectorIndex = 0; connectorIndex < data.length; connectorIndex++) {
                var connectorData = data[connectorIndex];
                var connector = new Connector_1.Connector();
                connector.data.code = connectorData.connector.code;
                connector.data.title = connectorData.connector.title;
                connector.data.default = connectorData.connector.default;
                connector.data.id = connectorData.connector.id;
                for (var key = 0; key < connectorData.collection.length; key++) {
                    var calendar = void 0;
                    if (connectorData.collection[key].generated) {
                        calendar = new GenerateCalendar_1.GenerateCalendar();
                    }
                    else {
                        calendar = new Calendar_1.Calendar();
                    }
                    connectorData.collection[key].visible = connectorData.collection[key].visible === "1";
                    calendar.setData(connectorData.collection[key]);
                    calendar.data.connector = connector.data.id;
                    if (calendar.data.relations) {
                        for (var index = 0; index < calendar.data.relations.events.length; index++) {
                            var eventData = calendar.data.relations.events[index];
                            var event_1 = new Event_1.Event();
                            event_1.setData(eventData);
                            event_1.addData('calendar_id', calendar.getData('id'));
                            event_1.data.connector = connector.data.id;
                            calendar.addEvent(event_1);
                        }
                    }
                    connector.addCalendar(calendar);
                }
                connectorCollection.addItem(connector);
            }
            this.viewModel.set('connectorCollection', connectorCollection);
            var mainCalendar = ServiceProvider_1.ServiceProvider.getInstance().get('mainCalendar');
            mainCalendar.setConnectorCollection(connectorCollection);
        }
        else {
            this.viewModel.set('connectorCollection', this.getCollection());
        }
        this.initMenu();
    };
    CalendarList.prototype.initMenu = function () {
        var _this = this;
        var mainCalendar = ServiceProvider_1.ServiceProvider.getInstance().get('mainCalendar');
        setTimeout(function () {
            jQuery('#calendar-list .calendar-label').each(function (index, element) {
                var id = jQuery(element).data('calendar');
                var connectionId = jQuery(element).data('connector');
                var connectorCollection = _this.getCollection();
                var connection = connectorCollection.getItem(connectionId);
                var calendar = connection.calendars.getItem(id);
                var menuItems = {
                    'hide': {
                        name: _this.translator("Hide Calendar")
                    }
                };
                if (!calendar.getData('read_only')) {
                    //@ts-ignore
                    menuItems['edit'] = {
                        name: _this.translator('Edit Calendar')
                    };
                }
                if (!calendar.getData('prevent_delete')) {
                    //@ts-ignore
                    menuItems['delete'] = {
                        name: _this.translator('Delete Calendar')
                    };
                }
                // console.log(menuItems);
                jQuery.contextMenu({
                    selector: '#calendar-headline-' + connectionId + '-' + id,
                    trigger: 'right',
                    callback: function (key, options) {
                        _this.loading.startLoading();
                        switch (key) {
                            // case 'show_events':
                            //     calendar.addData('visible', true);
                            //     connection.calendars.updateItem(calendar);
                            //     connectorCollection.updateItem(connection);
                            //     _this.setCollection(connectorCollection);
                            //     mainCalendar.setConnectorCollection(connectorCollection);
                            //     _this.initCollection();
                            //     _this.loading.stopLoading();
                            //     break;
                            // case 'hide_events':
                            //     calendar.addData('visible', false);
                            //     connection.calendars.updateItem(calendar);
                            //     connectorCollection.updateItem(connection);
                            //     _this.setCollection(connectorCollection);
                            //     mainCalendar.setConnectorCollection(connectorCollection);
                            //     _this.initCollection();
                            //     _this.loading.stopLoading();
                            // break;
                            case 'hide':
                                _this.hideCalendar(connection, calendar);
                                break;
                            case 'edit':
                                // console.log(calendar);
                                _this.initEditCalendarDialog(calendar);
                                break;
                            case 'delete':
                                _this.deleteCalendar(connection, calendar);
                                break;
                        }
                    },
                    items: menuItems
                });
            });
        });
    };
    CalendarList.prototype.bindViewElement = function () {
        kendo.bind(jQuery(this.element), this.viewModel);
        return this;
    };
    /**
     * @typedef {Calendar} element
     */
    CalendarList.prototype.calendarListChangeHandler = function (event) {
        this.loading.startLoading();
        var _this = this;
        var calendar = event.data;
        if (calendar !== null) {
            calendar.data.visible = event.target.checked;
            RedooAjax(app_1.App.SCOPE_NAME).postAction('CalendarSetVisible', calendar.getAll(), false, 'json').then(function (response) {
                if (response.status) {
                    var mainCalendar = ServiceProvider_1.ServiceProvider.getInstance().get('mainCalendar');
                    if (mainCalendar !== null) {
                        mainCalendar.setConnectorCollection(_this.getCollection());
                    }
                }
                else {
                    _this.notifications.error(response.message);
                }
                _this.loading.stopLoading();
            });
        }
    };
    CalendarList.prototype.initGenerateCalendarDialog = function () {
        var _this = this;
        var calendar;
        var colorPalette;
        var form;
        this.loading.startLoading();
        RedooAjax(app_1.App.SCOPE_NAME).postView('GenerateCalendarModal', {}, false, 'json').then(function (response) {
            _this.generateCalendarDialog = jQuery('#generate-calendar-dialog').kendoDialog({
                width: '50%',
                title: _this.translator('Generate Calendar'),
                visible: false,
                content: response.html,
                actions: [
                    {
                        text: _this.translator('Cancel')
                    },
                    {
                        text: _this.translator('Create'), primary: true, action: function () {
                            return _this.generateCalendar(calendar, form);
                        }
                    },
                ],
                close: function () {
                    _this.generateCalendarDialog.destroy();
                    jQuery('#modals').append('<div id="generate-calendar-dialog"></div>');
                },
                initOpen: function () {
                    calendar = new GenerateCalendar_1.GenerateCalendar();
                    calendar.setGenerateCalendarDialig(_this.generateCalendarDialog);
                    calendar.setObservable(document.getElementById('generate-calendar-form'));
                    form = FORMGenerator.start();
                    form.setValidators(response.validators);
                    eval(response.js);
                    form.init();
                    form.setContainer('#generate-calendar-form');
                    _this.loading.stopLoading();
                },
            }).data("kendoDialog");
            _this.generateCalendarDialog.open();
        });
    };
    CalendarList.prototype.initCreateCalendarDialog = function () {
        var _this = this;
        var calendar;
        var colorPalette;
        var form;
        this.loading.startLoading();
        RedooAjax(app_1.App.SCOPE_NAME).postView('AddCalendarModal', {}, false, 'json').then(function (response) {
            _this.createCalendarDialog = jQuery('#create-calendar-dialog').kendoDialog({
                width: '50%',
                title: _this.translator('New Calendar'),
                visible: false,
                content: response.html,
                actions: [
                    {
                        text: _this.translator('Cancel')
                    },
                    {
                        text: _this.translator('Create'), primary: true, action: function () {
                            return _this.createCalendar(calendar, form);
                        }
                    },
                ],
                close: function () {
                    _this.createCalendarDialog.destroy();
                    jQuery('#modals').append('<div id="create-calendar-dialog"></div>');
                },
                initOpen: function () {
                    calendar = new Calendar_1.Calendar();
                    calendar.setObservable(document.getElementById('create-calendar-form'));
                    form = FORMGenerator.start();
                    form.setValidators(response.validators);
                    eval(response.js);
                    form.init();
                    form.setContainer('#create-calendar-form');
                    colorPalette = jQuery("#create-calendar-dialog #palette").kendoColorPalette({
                        columns: 20,
                        tileSize: {
                            width: 25,
                            height: 25
                        },
                        palette: [
                            "#f0d0c9", "#e2a293", "#d4735e", "#65281a",
                            "#eddfda", "#dcc0b6", "#cba092", "#7b4b3a",
                            "#fcecd5", "#f9d9ab", "#f6c781", "#c87d0e",
                            "#e1dca5", "#d0c974", "#a29a36", "#514d1b",
                            "#c6d9f0", "#8db3e2", "#548dd4", "#17365d"
                        ],
                        change: function (event) {
                            // @ts-ignore
                            calendar.getObservable().set('color', event.value);
                        }
                    }).data('kendoColorPalette');
                    _this.loading.stopLoading();
                },
            }).data("kendoDialog");
            _this.createCalendarDialog.open();
        });
    };
    CalendarList.prototype.initEditCalendarDialog = function (calendar) {
        var _this = this;
        var form;
        this.loading.startLoading();
        var colorPalette;
        // console.log(calendar);
        RedooAjax(app_1.App.SCOPE_NAME).postView('EditCalendarModal', calendar.getAll(), false, 'json').then(function (response) {
            if (response.readonly) {
                _this.notifications.info(response.message);
                _this.loading.stopLoading();
                return;
            }
            _this.editCalendarDialog = jQuery('#edit-calendar-dialog').kendoDialog({
                width: '50%',
                title: _this.translator('Edit Calendar'),
                visible: false,
                content: response.form.html,
                actions: [
                    {
                        text: _this.translator('Cancel'), action: function () {
                            _this.editCalendarDialog.destroy();
                        }
                    },
                    {
                        text: _this.translator('Update'), primary: true, action: function () {
                            return _this.saveCalendar(calendar, form);
                        }
                    },
                ],
                close: function () {
                    _this.editCalendarDialog.destroy();
                    jQuery('#modals').append('<div id="edit-calendar-dialog"></div>');
                },
                open: function () {
                    if (calendar.getData('generated')) {
                        _this.editCalendarDialog.content('<div id="generate-calendar-main-container"></div>');
                        jQuery('#generate-calendar-main-container').append(response.form.html);
                        jQuery('#generate-calendar-main-container').append(response.condition_component);
                        //@ts-ignore
                        var complexCondition = new ComplexeCondition('#generate-calendar-complex-condition .container', 'settings[condition]');
                        form = FORMGenerator.start();
                        form.setValidators(response.form.validators);
                        eval(response.form.js);
                        form.init();
                        form.setContainer('#generate-calendar-main-form');
                        var generateCalendar = new GenerateCalendar_1.GenerateCalendar();
                        generateCalendar.setObservable(document.getElementById('generate-calendar-main-form'));
                        complexCondition.setTranslation({
                            'LBL_STATIC_VALUE': 'static value',
                            'LBL_FUNCTION_VALUE': 'function',
                            'LBL_EMPTY_VALUE': 'empty value',
                            'LBL_VALUES': 'values',
                            'LBL_ADD_GROUP': 'add Group',
                            'LBL_ADD_CONDITION': 'add Condition',
                            'LBL_REMOVE_GROUP': 'remove Group',
                            'LBL_NOT': 'NOT',
                            'LBL_AND': 'AND',
                            'LBL_OR': 'OR',
                            'LBL_COND_EQUAL': 'LBL_COND_EQUAL',
                            'LBL_COND_IS_CHECKED': 'LBL_COND_IS_CHECKED',
                            'LBL_COND_CONTAINS': 'LBL_COND_CONTAINS',
                            'LBL_COND_BIGGER': 'LBL_COND_BIGGER',
                            'LBL_COND_DATE_EMPTY': 'LBL_COND_DATE_EMPTY',
                            'LBL_COND_LOWER': 'LBL_COND_LOWER',
                            'LBL_COND_STARTS_WITH': 'LBL_COND_STARTS_WITH',
                            'LBL_COND_ENDS_WITH': 'LBL_COND_ENDS_WITH',
                            'LBL_COND_IS_EMPTY': 'LBL_COND_IS_EMPTY',
                            'LBL_CANCEL': 'LBL_CANCEL',
                            'LBL_SAVE': 'LBL_SAVE'
                        });
                        complexCondition.setEnabledTemplateFields(true);
                        complexCondition.setMainCheckModule(generateCalendar.getData('module_id'));
                        complexCondition.setMainSourceModule(generateCalendar.getData('module_id'));
                        complexCondition.setConditionMode('mysql');
                        complexCondition.setScopeName('RedooCalendar');
                        //@ts-ignore
                        complexCondition.setCondition(InitReportCondition);
                        complexCondition.init();
                        calendar = generateCalendar;
                    }
                    else {
                        form = FORMGenerator.start();
                        form.setValidators(response.form.validators);
                        eval(response.form.js);
                        form.init();
                        form.setContainer('#edit-calendar-form');
                        calendar.setObservable(document.getElementById('edit-calendar-form'));
                    }
                    _this.loading.stopLoading();
                },
            }).data("kendoDialog");
            _this.editCalendarDialog.open();
        });
    };
    CalendarList.prototype.createCalendar = function (calendar, form) {
        var _this = this;
        var mainCalendar = ServiceProvider_1.ServiceProvider.getInstance().get('mainCalendar');
        if (!form.isValid())
            return false;
        this.loading.startLoading();
        RedooAjax(app_1.App.SCOPE_NAME).postAction('CalendarAdd', calendar.getAll()).then(function (response) {
            response = JSON.parse(response);
            if (response.status) {
                _this.notifications.info(response.message);
                _this.createCalendarDialog.close();
                var connector = _this.getCollection().getItem(response.connector);
                calendar.setData(response.calendar);
                calendar.data.connector = connector.data.id;
                connector.calendars.addItem(calendar);
                _this.viewModel.trigger('calendarListChange');
                mainCalendar.setConnectorCollection(_this.getCollection());
                _this.initCollection();
            }
            else {
                _this.notifications.error(response.message);
                _this.createCalendarDialog.close();
            }
            _this.loading.stopLoading();
        });
        return true;
    };
    CalendarList.prototype.saveCalendar = function (calendar, form) {
        this.loading.startLoading();
        var _this = this;
        var conditions = {};
        var mainCalendar = ServiceProvider_1.ServiceProvider.getInstance().get('mainCalendar');
        mainCalendar.setConnectorCollection(this.getCollection());
        if (!form.isValid())
            return false;
        var request;
        if (calendar instanceof GenerateCalendar_1.GenerateCalendar) {
            jQuery('#generate-calendar-complex-condition').serializeArray().forEach(function (item) {
                //@ts-ignore
                conditions[item['name']] = item['value'];
            });
            request = Object.assign({
                form: calendar.getAll()
            }, conditions);
        }
        else {
            request = calendar.getAll();
        }
        RedooAjax(app_1.App.SCOPE_NAME).postAction(calendar instanceof GenerateCalendar_1.GenerateCalendar ? 'GeneratedCalendarUpdate' : 'CalendarUpdate', request, false, 'json').then(function (response) {
            if (response.status) {
                _this.notifications.info(response.message);
                _this.editCalendarDialog.close();
                var connector = _this.getCollection().getItem(calendar.data.connector);
                if (calendar instanceof GenerateCalendar_1.GenerateCalendar) {
                    calendar = new GenerateCalendar_1.GenerateCalendar();
                    calendar.setData(response.calendar);
                    calendar.addData('generated', true);
                    calendar.data.connector = connector.data.id;
                    for (var index = 0; index < response.calendar.relations.events.length; index++) {
                        var eventData = calendar.data.relations.events[index];
                        var event_2 = new Event_1.Event();
                        event_2.setData(eventData);
                        event_2.addData('calendar_id', calendar.getData('id'));
                        event_2.data.connector = connector.data.id;
                        calendar.addEvent(event_2);
                    }
                }
                connector.calendars.updateItem(calendar);
                _this.viewModel.trigger('calendarListChange');
                mainCalendar.setConnectorCollection(_this.getCollection());
                _this.initCollection();
                _this.initMenu();
            }
            else {
                _this.notifications.error(response.message);
                _this.editCalendarDialog.close();
            }
            _this.loading.stopLoading();
        });
        return true;
    };
    CalendarList.prototype.deleteCalendar = function (connector, calendar) {
        this.loading.startLoading();
        if (window.confirm(this.translator('Delete calendar?'))) {
            var _this_1 = this;
            var mainCalendar_1 = ServiceProvider_1.ServiceProvider.getInstance().get('mainCalendar');
            // @ts-ignore
            RedooAjax(app_1.App.SCOPE_NAME).postAction('CalendarDelete', {
                calendar_id: calendar.data.id,
                connector: calendar.data.connector
            }, false, 'json').then(function (response) {
                if (response.readonly) {
                    _this_1.notifications.info(response.message);
                    _this_1.loading.stopLoading();
                    return;
                }
                if (response.status) {
                    _this_1.notifications.info(response.message);
                    var connector_1 = _this_1.getCollection().getItem(calendar.data.connector);
                    // @ts-ignore
                    connector_1.calendars.removeItem(calendar.getId());
                    mainCalendar_1.setConnectorCollection(_this_1.getCollection());
                }
                else {
                    _this_1.notifications.error(response.message);
                }
            });
        }
        this.loading.stopLoading();
    };
    CalendarList.prototype.hideCalendar = function (connection, calendar) {
        this.loading.startLoading();
        var _this = this;
        var mainCalendar = ServiceProvider_1.ServiceProvider.getInstance().get('mainCalendar');
        // @ts-ignore
        RedooAjax(app_1.App.SCOPE_NAME).postAction('CalendarHide', {
            calendar_id: calendar.data.id,
            connector: connection.getId()
        }, true).then(function (response) {
            response = JSON.parse(response);
            if (response.status) {
                _this.notifications.info(response.message);
                var connection_1 = _this.getCollection().getItem(calendar.data.connector);
                // @ts-ignore
                connection_1.calendars.removeItem(calendar.getId());
                mainCalendar.setConnectorCollection(_this.getCollection());
                _this.initCollection();
            }
            else {
                _this.notifications.error(response.message);
            }
            _this.loading.stopLoading();
        });
    };
    CalendarList.prototype.getCollection = function () {
        return this.viewModel.get('connectorCollection');
    };
    CalendarList.prototype.openSubscribeCalendar = function () {
        this.loading.startLoading();
        var _this = this;
        var form;
        var calendar;
        var subscribe;
        RedooAjax(app_1.App.SCOPE_NAME).postView('SubscribeCalendarModal', {}, false, 'json').then(function (response) {
            _this.subscribeCalendarDialog = jQuery('#subscribe-calendar-dialog').kendoDialog({
                width: '50%',
                title: _this.translator('More Calendars'),
                visible: false,
                content: response.html,
                actions: [
                    {
                        text: _this.translator('Cancel')
                    },
                    {
                        text: _this.translator('Add'), primary: true, action: function (event) {
                            return _this.subscribeCalendar(subscribe, form);
                        }
                    },
                ],
                close: function () {
                    _this.subscribeCalendarDialog.destroy();
                    jQuery('#modals').append('<div id="subscribe-calendar-dialog"></div>');
                },
                initOpen: function () {
                    form = FORMGenerator.start();
                    form.setValidators(response.validators);
                    eval(response.js);
                    form.init();
                    form.setContainer('#subscribe-calendar');
                    subscribe = new Subscribe_1.Subscribe();
                    subscribe.setObservable(document.getElementById('subscribe-calendar'));
                    _this.loading.stopLoading();
                },
            }).data("kendoDialog");
            _this.subscribeCalendarDialog.open();
        });
    };
    CalendarList.prototype.subscribeCalendar = function (subscribe, form) {
        if (!form.isValid())
            return false;
        this.loading.startLoading();
        var _this = this;
        RedooAjax(app_1.App.SCOPE_NAME).postAction('CalendarSubscribe', subscribe.getAll(), false, 'json').then(function (response) {
            if (response.status) {
                _this.initCollection(response.connectors);
                _this.notifications.info(response.message);
            }
            else {
                _this.notifications.error(response.message);
            }
            _this.loading.stopLoading();
        });
        return true;
    };
    CalendarList.prototype.createConnectionInitDialog = function (event) {
        this.loading.startLoading();
        var _this = this;
        var form;
        var connection;
        RedooAjax(app_1.App.SCOPE_NAME).postView('CreateConnectionModal', {}, false, 'json').then(function (response) {
            _this.createConnectionDialog = jQuery('#create-connection-dialog').kendoDialog({
                width: '50%',
                title: _this.translator('New Connection'),
                visible: false,
                content: response.html,
                actions: [
                    {
                        text: _this.translator('Cancel')
                    },
                    {
                        text: _this.translator('Create'), primary: true, action: function (event) {
                            return _this.createConnection(connection, form);
                        }
                    },
                ],
                close: function () {
                    _this.createConnectionDialog.destroy();
                    jQuery('#modals').append('<div id="create-connection-dialog"></div>');
                },
                initOpen: function () {
                    form = FORMGenerator.start();
                    form.setValidators(response.validators);
                    eval(response.js);
                    form.init();
                    form.setContainer('#create-connection');
                    console.log('HIER');
                    connection = new Connector_1.Connector();
                    connection.setObservable(document.getElementById('create-connection'));
                    _this.loading.stopLoading();
                },
            }).data("kendoDialog");
            _this.createConnectionDialog.open();
        });
    };
    CalendarList.prototype.createConnection = function (connection, form) {
        if (!form.isValid())
            return false;
        this.loading.startLoading();
        var _this = this;
        RedooAjax(app_1.App.SCOPE_NAME).postAction('ConnectionCreate', connection.getAll(), false, 'json').then(function (response) {
            if (response.redirect_url !== '') {
                location.href = response.redirect_url;
            }
            else {
                _this.getCollection().addItem(connection);
            }
            _this.loading.stopLoading();
        });
        return true;
    };
    CalendarList.prototype.deleteConnection = function (event) {
        this.loading.startLoading();
        var _this = this;
        RedooAjax(app_1.App.SCOPE_NAME).postAction('ConnectionDelete', {
            connection_id: event.sender.element.context.dataset.connection_id
        }, false, 'json').then(function (response) {
            if (response.status) {
                _this.notifications.info(response.message);
                var connector = _this.getCollection().getItem(event.sender.element.context.dataset.connection_id);
                // @ts-ignore
                _this.getCollection().removeItem(calendar.getId());
                _this.initCollection();
            }
            else {
                _this.notifications.error(response.message);
            }
            _this.loading.stopLoading();
        });
        return this;
    };
    CalendarList.prototype.generateCalendar = function (generateCalendar, form) {
        var _this = this;
        if (!form.isValid())
            return false;
        var mainCalendar = ServiceProvider_1.ServiceProvider.getInstance().get('mainCalendar');
        var conditions = {};
        jQuery('#generate-calendar-complex-condition').serializeArray().forEach(function (item) {
            //@ts-ignore
            conditions[item['name']] = item['value'];
        });
        var form_data = generateCalendar.getAll();
        if (form_data.module_id != -1) {
            RedooAjax(app_1.App.SCOPE_NAME).postAction('CalendarGenerate', Object.assign({
                form: form_data
            }, conditions), false, 'json').then(function (response) {
                if (response.status) {
                    _this.notifications.info(response.message);
                    _this.generateCalendarDialog.close();
                    var connector = _this.getCollection().getItem(response.connector);
                    var calendar = new GenerateCalendar_1.GenerateCalendar();
                    calendar.setData(response.calendar);
                    calendar.addData('generated', true);
                    calendar.data.connector = connector.data.id;
                    for (var index = 0; index < response.calendar.relations.events.length; index++) {
                        var eventData = calendar.data.relations.events[index];
                        var event_3 = new Event_1.Event();
                        event_3.setData(eventData);
                        event_3.addData('calendar_id', calendar.getData('id'));
                        event_3.data.connector = connector.data.id;
                        calendar.addEvent(event_3);
                    }
                    connector.calendars.addItem(calendar);
                    _this.viewModel.trigger('calendarListChange');
                    mainCalendar.setConnectorCollection(_this.getCollection());
                    _this.initCollection();
                }
                else {
                    _this.notifications.error(response.message);
                    _this.generateCalendarDialog.close();
                }
            });
        }
        else {
            var error_container = $(".errorMsg");
            error_container.attr('style', 'display: inline');
            error_container.html('<i class="fa fa fa-exclamation-triangle"></i> Dieses Feld ist ein Pflichtfeld');
            return false;
        }
        return true;
    };
    CalendarList.prototype.setCollection = function (collection) {
        this.viewModel.set('connectorCollection', collection);
        this.viewModel.trigger('calendarListChange');
        return this;
    };
    return CalendarList;
}(UiComponent_1.UiComponent));
exports.CalendarList = CalendarList;

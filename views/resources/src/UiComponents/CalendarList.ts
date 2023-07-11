import {UiComponent} from "../Core/UiComponent/UiComponent";
import {CalendarCollection} from "../Model/Collection/CalendarCollection";
import {Calendar} from "../Model/Calendar";
import {MainCalendar} from "./MainCalendar";
import {ServiceProvider} from "../Core/ServiceProvider/ServiceProvider";
import {App} from "../app";
import ObservableObject = kendo.data.ObservableObject;
import Dialog = kendo.ui.Dialog;
import {Event} from "../Model/Event";
import {Collection} from "../Core/Model/Collection/Collection";
import {ConnectorCollection} from "../Model/Collection/ConnectorCollection";
import {Connector} from "../Model/Connector";
import {Subscribe} from "../Model/Subscribe";
import progress = kendo.ui.progress;
import {Loading} from "./Loading";
import {FORMGenerator} from "../Core/Form/FORMGenerator";
import {Model} from "../Core/Model/Model";
import {GenerateCalendar} from "../Model/GenerateCalendar";
import {CollectionItemInterface} from "../Core/Model/Collection/Interface/CollectionItemInterface";

export class CalendarList extends UiComponent {

    public viewModel: kendo.data.ObservableObject;
    protected createCalendarDialog: Dialog;
    protected generateCalendarDialog: Dialog;
    protected createConnectionDialog: Dialog;
    protected subscribeCalendarDialog: Dialog;
    protected editCalendarDialog: Dialog;
    protected connectorCollection: Collection;
    protected loading: Loading;

    public init(): CalendarList {
        this.loading = ServiceProvider.getInstance().get('loading') as Loading;

        this.initModel();

        return this;
    }

    protected initModel(): CalendarList {
        this.loading.startLoading();
        let _this = this;

        RedooAjax('RedooCalendar').postAction('GetSubscribedCalendars', {}, false, 'json').then(function (response) {
                _this.viewModel = kendo.observable({
                    connectorCollection: {},
                    calendarListChange: function (event: any) {
                        _this.calendarListChangeHandler(event);
                    },
                    createConnection: function (event: any) {
                        _this.createConnectionInitDialog(event);
                    },
                    subscribeCalendar: function () {
                        _this.openSubscribeCalendar()
                    }
                });

                jQuery("#main-list-menu").kendoToolBar({
                    items: [
                        {
                            type: "button",
                            icon: "link-horizontal",
                            text: _this.translator('New Connection'),
                            click: function (event: any) {
                                _this.createConnectionInitDialog(event);
                            }
                        },
                        {
                            type: "button",
                            icon: "plus-outline",
                            text: _this.translator("New Calendar"),
                            click: function (event: any) {
                                _this.initCreateCalendarDialog();
                            }
                        },
                        {
                            type: "button",
                            icon: "plus-outline",
                            text: _this.translator("Generate Calendar"),
                            click: function (event: any) {
                                _this.initGenerateCalendarDialog();
                            }
                        },
                        {
                            type: "button",
                            icon: "zoom",
                            text: _this.translator("More Calendars"),
                            click: function (event: any) {
                                _this.openSubscribeCalendar();
                            }
                        },
                        {
                            type: "button",
                            icon: "zoom",
                            text: _this.translator("Configuration"),
                            click: function (event: any) {
                                window.location.href = 'index.php?module=RedooCalendar&parent=Settings&view=Config';
                            }
                        },
                    ]
                });

                _this.initCollection(response.connectors);

                _this.bindViewElement();

                _this.loading.stopLoading();

            }
        );


        return this;
    }

    protected initCollection(data: any = null) {
        let _this = this;

        if (data) {
            let connectorCollection = new ConnectorCollection();
            for (let connectorIndex = 0; connectorIndex < data.length; connectorIndex++) {
                let connectorData = data[connectorIndex];
                let connector = new Connector();

                connector.data.code = connectorData.connector.code;
                connector.data.title = connectorData.connector.title;
                connector.data.default = connectorData.connector.default;
                connector.data.id = connectorData.connector.id;

                for (let key = 0; key < connectorData.collection.length; key++) {
                    let calendar: Calendar;
                    if (connectorData.collection[key].generated) {
                        calendar = new GenerateCalendar();
                    } else {
                        calendar = new Calendar();
                    }

                    connectorData.collection[key].visible = connectorData.collection[key].visible === "1";

                    calendar.setData(connectorData.collection[key]);
                    calendar.data.connector = connector.data.id;
                    
                    if (calendar.data.relations) {
                        for (let index = 0; index < calendar.data.relations.events.length; index++) {
                            let eventData = calendar.data.relations.events[index];
                            let event = new Event();
                            event.setData(eventData);
                            event.addData('calendar_id', calendar.getData('id'));
                            event.data.connector = connector.data.id;
                            calendar.addEvent(event);
                        }
                    }

                    connector.addCalendar(calendar);
                }

                connectorCollection.addItem(connector);
            }
            this.viewModel.set('connectorCollection', connectorCollection);
            let mainCalendar = ServiceProvider.getInstance().get('mainCalendar') as MainCalendar;
            mainCalendar.setConnectorCollection(connectorCollection);
        } else {
            this.viewModel.set('connectorCollection', this.getCollection());
        }

        this.initMenu();
    }

    protected initMenu() {

        let _this = this;
        let mainCalendar = ServiceProvider.getInstance().get('mainCalendar') as MainCalendar;

        setTimeout(function () {
            jQuery('#calendar-list .calendar-label').each(function (index, element) {

                let id = jQuery(element).data('calendar');
                let connectionId = jQuery(element).data('connector');

                let connectorCollection = _this.getCollection();

                let connection = connectorCollection.getItem(connectionId) as Connector;

                let calendar = connection.calendars.getItem(id) as Calendar;

                let menuItems = {
                    'hide': {
                        name: _this.translator("Hide Calendar")
                    }
                };

                if (!calendar.getData('read_only')) {
                    //@ts-ignore
                    menuItems['edit'] = {
                        name: _this.translator('Edit Calendar')
                    }
                }

                if (!calendar.getData('prevent_delete')) {
                    //@ts-ignore
                    menuItems['delete'] = {
                        name: _this.translator('Delete Calendar')
                    }
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
            })


        });
    }


    protected bindViewElement(): CalendarList {
        kendo.bind(jQuery(this.element), this.viewModel);
        return this;
    }

    /**
     * @typedef {Calendar} element
     */
    protected calendarListChangeHandler(event: any): void {
        this.loading.startLoading();
        let _this = this;
        let calendar = event.data as Calendar;
        if (calendar !== null) {
            calendar.data.visible = event.target.checked;

            RedooAjax(App.SCOPE_NAME).postAction('CalendarSetVisible', calendar.getAll(), false, 'json').then(function (response) {
                if (response.status) {
                    let mainCalendar = ServiceProvider.getInstance().get('mainCalendar') as MainCalendar;
                    if (mainCalendar !== null) {
                        mainCalendar.setConnectorCollection(_this.getCollection());
                    }
                } else {
                    _this.notifications.error(response.message);
                }
                _this.loading.stopLoading();
            });
        }

    }

    public initGenerateCalendarDialog() {
        let _this = this;
        let calendar: GenerateCalendar;
        let colorPalette: kendo.ui.ColorPalette;
        let form: FORMGenerator;
        this.loading.startLoading();
        RedooAjax(App.SCOPE_NAME).postView('GenerateCalendarModal', {}, false, 'json').then(function (response) {
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
                            return _this.generateCalendar(calendar, form)
                        }
                    },
                ],
                close: function () {
                    _this.generateCalendarDialog.destroy();
                    jQuery('#modals').append('<div id="generate-calendar-dialog"></div>');
                },
                initOpen: function () {
                    calendar = new GenerateCalendar();
                    calendar.setGenerateCalendarDialig(_this.generateCalendarDialog);
                    calendar.setObservable(document.getElementById('generate-calendar-form') as HTMLElement);

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
    }

    public initCreateCalendarDialog() {
        let _this = this;
        let calendar: Calendar;
        let colorPalette: kendo.ui.ColorPalette;
        let form: FORMGenerator;
        this.loading.startLoading();
        RedooAjax(App.SCOPE_NAME).postView('AddCalendarModal', {}, false, 'json').then(function (response) {
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
                            return _this.createCalendar(calendar, form)
                        }
                    },
                ],
                close: function () {
                    _this.createCalendarDialog.destroy();
                    jQuery('#modals').append('<div id="create-calendar-dialog"></div>');
                },
                initOpen: function () {
                    calendar = new Calendar();
                    calendar.setObservable(document.getElementById('create-calendar-form') as HTMLElement);


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
                        change(event: kendo.ui.ColorPaletteEvent): void {
                            // @ts-ignore
                            calendar.getObservable().set('color', event.value);
                        }
                    }).data('kendoColorPalette');
                    _this.loading.stopLoading();
                },
            }).data("kendoDialog");

            _this.createCalendarDialog.open();
        });
    }

    public initEditCalendarDialog(calendar: Calendar) {
        let _this = this;
        let form: FORMGenerator;
        this.loading.startLoading();
        let colorPalette: kendo.ui.ColorPalette;
        // console.log(calendar);
        RedooAjax(App.SCOPE_NAME).postView('EditCalendarModal', calendar.getAll(), false, 'json').then(function (response) {

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
                        let complexCondition = new ComplexeCondition('#generate-calendar-complex-condition .container', 'settings[condition]');

                        form = FORMGenerator.start();
                        form.setValidators(response.form.validators);
                        eval(response.form.js);
                        form.init();
                        form.setContainer('#generate-calendar-main-form');

                        let generateCalendar = new GenerateCalendar();
                        generateCalendar.setObservable(document.getElementById('generate-calendar-main-form') as HTMLElement);

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


                    } else {
                        form = FORMGenerator.start();
                        form.setValidators(response.form.validators);
                        eval(response.form.js);
                        form.init();
                        form.setContainer('#edit-calendar-form');
                        calendar.setObservable(document.getElementById('edit-calendar-form') as HTMLElement);
                    }


                    _this.loading.stopLoading();
                },
            }).data("kendoDialog");

            _this.editCalendarDialog.open();


        });
    }

    protected createCalendar(calendar: Calendar, form: FORMGenerator) {
        let _this = this;
        let mainCalendar = ServiceProvider.getInstance().get('mainCalendar') as MainCalendar;

        if (!form.isValid()) return false;

        this.loading.startLoading();
        RedooAjax(App.SCOPE_NAME).postAction('CalendarAdd', calendar.getAll()).then(function (response) {
            response = JSON.parse(response);
            if (response.status) {
                _this.notifications.info(response.message);
                _this.createCalendarDialog.close();
                let connector = _this.getCollection().getItem(response.connector) as Connector;
                calendar.setData(response.calendar);
                calendar.data.connector = connector.data.id;
                connector.calendars.addItem(calendar);

                _this.viewModel.trigger('calendarListChange');
                mainCalendar.setConnectorCollection(_this.getCollection());
                _this.initCollection();
            } else {
                _this.notifications.error(response.message);
                _this.createCalendarDialog.close();
            }
            _this.loading.stopLoading();
        });
        return true;
    }

    protected saveCalendar(calendar: Calendar, form: FORMGenerator) {
        this.loading.startLoading();
        let _this = this;
        let conditions = {};

        let mainCalendar = ServiceProvider.getInstance().get('mainCalendar') as MainCalendar;
        mainCalendar.setConnectorCollection(this.getCollection());

        if (!form.isValid()) return false;

        let request;
        if (calendar instanceof GenerateCalendar) {
            jQuery('#generate-calendar-complex-condition').serializeArray().forEach(function (item) {
                //@ts-ignore
                conditions[item['name']] = item['value'];
            });

            request = Object.assign(
                {
                    form: calendar.getAll()
                },
                conditions
            );
        } else {
            request = calendar.getAll()
        }

        RedooAjax(App.SCOPE_NAME).postAction(
            calendar instanceof GenerateCalendar ? 'GeneratedCalendarUpdate' : 'CalendarUpdate', request, false, 'json'
        ).then(function (response) {
            if (response.status) {
                _this.notifications.info(response.message);
                _this.editCalendarDialog.close();
                let connector = _this.getCollection().getItem(calendar.data.connector) as Connector;

                if (calendar instanceof GenerateCalendar) {
                    calendar = new GenerateCalendar();

                    calendar.setData(response.calendar);
                    calendar.addData('generated', true);
                    calendar.data.connector = connector.data.id;
                    for (let index = 0; index < response.calendar.relations.events.length; index++) {
                        let eventData = calendar.data.relations.events[index];
                        let event = new Event();
                        event.setData(eventData);
                        event.addData('calendar_id', calendar.getData('id'));
                        event.data.connector = connector.data.id;
                        calendar.addEvent(event);
                    }
                }

                connector.calendars.updateItem(calendar);
                _this.viewModel.trigger('calendarListChange');
                mainCalendar.setConnectorCollection(_this.getCollection());
                _this.initCollection();
                _this.initMenu();
            } else {
                _this.notifications.error(response.message);
                _this.editCalendarDialog.close();
            }
            _this.loading.stopLoading();
        });
        return true;
    }

    protected deleteCalendar(connector: Connector, calendar: Calendar) {

        this.loading.startLoading();
        if (window.confirm(this.translator('Delete calendar?'))) {
            let _this = this;

            let mainCalendar = ServiceProvider.getInstance().get('mainCalendar') as MainCalendar;
            // @ts-ignore
            RedooAjax(App.SCOPE_NAME).postAction('CalendarDelete', {
                calendar_id: calendar.data.id,
                connector: calendar.data.connector
            }, false, 'json').then(function (response) {
                if (response.readonly) {
                    _this.notifications.info(response.message);
                    _this.loading.stopLoading();
                    return;
                }

                if (response.status) {
                    _this.notifications.info(response.message);

                    let connector = _this.getCollection().getItem(calendar.data.connector) as Connector;
                    // @ts-ignore
                    connector.calendars.removeItem(calendar.getId());
                    mainCalendar.setConnectorCollection(_this.getCollection());
                } else {
                    _this.notifications.error(response.message);
                }

            });
        }
        this.loading.stopLoading();

    }

    protected hideCalendar(connection: Connector, calendar: Calendar) {
        this.loading.startLoading();
        let _this = this;

        let mainCalendar = ServiceProvider.getInstance().get('mainCalendar') as MainCalendar;
        // @ts-ignore
        RedooAjax(App.SCOPE_NAME).postAction('CalendarHide', {
            calendar_id: calendar.data.id,
            connector: connection.getId()
        }, true).then(function (response) {
            response = JSON.parse(response);

            if (response.status) {
                _this.notifications.info(response.message);

                let connection = _this.getCollection().getItem(calendar.data.connector) as Connector;
                // @ts-ignore
                connection.calendars.removeItem(calendar.getId());
                mainCalendar.setConnectorCollection(_this.getCollection());
                _this.initCollection();
            } else {
                _this.notifications.error(response.message);
            }
            _this.loading.stopLoading();
        });
    }


    public getCollection(): ConnectorCollection {
        return this.viewModel.get('connectorCollection')
    }

    public openSubscribeCalendar() {
        this.loading.startLoading();
        let _this = this;
        let form: FORMGenerator;
        let calendar: Calendar;
        let subscribe: Subscribe;

        RedooAjax(App.SCOPE_NAME).postView('SubscribeCalendarModal', {}, false, 'json').then(function (response) {
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
                        text: _this.translator('Add'), primary: true, action: function (event: any) {
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

                    subscribe = new Subscribe();
                    subscribe.setObservable(document.getElementById('subscribe-calendar') as HTMLElement)
                    _this.loading.stopLoading();
                },
            }).data("kendoDialog");


            _this.subscribeCalendarDialog.open();
        });


    }


    protected subscribeCalendar(subscribe: Subscribe, form: FORMGenerator): boolean {
        if (!form.isValid()) return false
        this.loading.startLoading();
        let _this = this;
        RedooAjax(App.SCOPE_NAME).postAction('CalendarSubscribe', subscribe.getAll(), false, 'json').then(function (response: any) {
            if (response.status) {
                _this.initCollection(response.connectors);
                _this.notifications.info(response.message);
            } else {
                _this.notifications.error(response.message);
            }
            _this.loading.stopLoading();
        });
        return true;
    }

    protected createConnectionInitDialog(event: any) {
        this.loading.startLoading();
        let _this = this;
        let form: FORMGenerator;
        let connection: Connector;
        RedooAjax(App.SCOPE_NAME).postView('CreateConnectionModal', {}, false, 'json').then(function (response) {
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
                        text: _this.translator('Create'), primary: true, action: function (event: any) {
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

                    connection = new Connector();
                    connection.setObservable(document.getElementById('create-connection') as HTMLElement);
                    _this.loading.stopLoading();
                },
            }).data("kendoDialog");

            _this.createConnectionDialog.open();
        });
    }

    protected createConnection(connection: Connector, form: FORMGenerator): boolean {
        if (!form.isValid()) return false;

        this.loading.startLoading();
        let _this = this;
        RedooAjax(App.SCOPE_NAME).postAction('ConnectionCreate', connection.getAll(), false, 'json').then(function (response: any) {
            if (response.redirect_url !== '') {
                location.href = response.redirect_url;
            } else {
                _this.getCollection().addItem(connection);
            }

            _this.loading.stopLoading();
        });
        return true;
    }

    protected deleteConnection(event: any) {
        this.loading.startLoading();
        let _this = this;
        RedooAjax(App.SCOPE_NAME).postAction('ConnectionDelete', {
            connection_id: event.sender.element.context.dataset.connection_id
        }, false, 'json').then(function (response: any) {
            if (response.status) {
                _this.notifications.info(response.message);

                let connector = _this.getCollection().getItem(event.sender.element.context.dataset.connection_id) as Connector;
                // @ts-ignore
                _this.getCollection().removeItem(calendar.getId());
                _this.initCollection();
            } else {
                _this.notifications.error(response.message);
            }
            _this.loading.stopLoading();
        });
        return this;
    }

    protected generateCalendar(generateCalendar: GenerateCalendar, form: FORMGenerator) {
        let _this = this;
        if (!form.isValid()) return false;
        let mainCalendar = ServiceProvider.getInstance().get('mainCalendar') as MainCalendar;
        let conditions = {};

        jQuery('#generate-calendar-complex-condition').serializeArray().forEach(function (item) {
            //@ts-ignore
            conditions[item['name']] = item['value'];
        });

        const form_data = generateCalendar.getAll();
        if(form_data.module_id != -1)
        {
            RedooAjax(App.SCOPE_NAME).postAction('CalendarGenerate',
                Object.assign(
                    {
                        form: form_data
                    },
                    conditions
                ), false, 'json').then(function (response) {
                if (response.status) {
                    _this.notifications.info(response.message);
                    _this.generateCalendarDialog.close();
                    let connector = _this.getCollection().getItem(response.connector) as Connector;

                    let calendar = new GenerateCalendar();

                    calendar.setData(response.calendar);
                    calendar.addData('generated', true);
                    calendar.data.connector = connector.data.id;
                    for (let index = 0; index < response.calendar.relations.events.length; index++) {
                        let eventData = calendar.data.relations.events[index];
                        let event = new Event();
                        event.setData(eventData);
                        event.addData('calendar_id', calendar.getData('id'));
                        event.data.connector = connector.data.id;
                        calendar.addEvent(event);
                    }

                    connector.calendars.addItem(calendar);

                    _this.viewModel.trigger('calendarListChange');
                    mainCalendar.setConnectorCollection(_this.getCollection());
                    _this.initCollection();
                } else {
                    _this.notifications.error(response.message);
                    _this.generateCalendarDialog.close();
                }
            });
        }
        else
        {
            const error_container = $(".errorMsg");
            error_container.attr('style', 'display: inline')
            error_container.html('<i class="fa fa fa-exclamation-triangle"></i> Dieses Feld ist ein Pflichtfeld');
            return false;
        }

        return true;
    }

    protected setCollection(collection: CollectionItemInterface): CalendarList {
        this.viewModel.set('connectorCollection', collection);
        this.viewModel.trigger('calendarListChange');
        return this;
    }
}
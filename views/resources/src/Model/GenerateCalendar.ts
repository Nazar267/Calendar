import {Model} from "../Core/Model/Model";
import {CollectionItemInterface} from "../Core/Model/Collection/Interface/CollectionItemInterface";
import {App} from "../app";
import {FORMGenerator} from "../Core/Form/FORMGenerator";
import {Calendar} from "./Calendar";
import Dialog = kendo.ui.Dialog;

export class GenerateCalendar extends Calendar implements CollectionItemInterface {
    protected generateCalendarDialog: Dialog;

    public data = {
        id: '',
        color: '',
        visible: true,
        title: '',
        title_prefix: '',
        code: '',
        relations: {
            events: []
        },
        module_id: 0,
        event_title: '',
        event_subtitle: '',
        datetime_mode: '',
        date_from: '',
        date_to: '',
        timezone: 'UTC',
        time_from: '',
        time_to: '',
        enabled: true,
        owner: true,
        share_to: [],
        share_to_google: '',
        connector: 0,
        hide_event_details: false,
        access_mode: ''
    };

    public complexCondition: any;

    public publicData = [
        'id',
        'title',
        'title_prefix',
        'module_id',
        'event_title',
        'event_subtitle',
        'datetime_mode',
        'date_from',
        'date_to',
        'time_from',
        'timezone',
        'time_to',
        'visible',
        'connector',
        'color',
        'access_mode'
    ];

    setBindFunctions() {
        let _this = this;
        this.bindFunctions = {
            selectModuleHandler: function (event: any) {
                jQuery('#generate-calendar-main-container').remove();
                RedooAjax(App.SCOPE_NAME).postView('GenerateCalendarMainForm', {
                    module_id: _this.getData('module_id'),
                }, false, 'json').then(function (response) {
                    jQuery('#generate-calendar-form').append('<div id="generate-calendar-main-container"></div>');
                    jQuery('#generate-calendar-main-container').append(response.form.html);
                    jQuery('#generate-calendar-main-container').append(response.condition_component);
                    //@ts-ignore
                    _this.complexCondition = new ComplexeCondition('#generate-calendar-complex-condition .container', 'settings[condition]');


                    _this.complexCondition.setTranslation({
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


                    _this.complexCondition.setEnabledTemplateFields(true);

                    _this.complexCondition.setMainCheckModule(_this.getData('module_id'));

                    _this.complexCondition.setMainSourceModule(_this.getData('module_id'));

                    _this.complexCondition.setConditionMode('mysql');

                    _this.complexCondition.setScopeName('RedooCalendar');
                    //@ts-ignore
                    _this.complexCondition.setCondition(InitReportCondition);
                    _this.complexCondition.init();


                    let _form = FORMGenerator.start();
                    _form.setValidators(response.form.validators);
                    eval(response.form.js);
                    _form.init();
                    _form.setContainer('#generate-calendar-main-form');
                    _this.setObservable(document.getElementById('generate-calendar-form') as HTMLElement);

                    _this.generateCalendarDialog.toFront();

                    jQuery('.FormGenField ').on('resize_dialog', function (event) {
                        setTimeout(function () {
                            _this.generateCalendarDialog.toFront();
                        });
                    })
                })
            }
        }
    }

    public setGenerateCalendarDialig(dialog: Dialog): GenerateCalendar {
        this.generateCalendarDialog = dialog;
        return this;
    }


}
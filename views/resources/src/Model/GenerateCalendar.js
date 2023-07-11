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
exports.GenerateCalendar = void 0;
var app_1 = require("../app");
var Calendar_1 = require("./Calendar");
var GenerateCalendar = /** @class */ (function (_super) {
    __extends(GenerateCalendar, _super);
    function GenerateCalendar() {
        var _this_1 = _super !== null && _super.apply(this, arguments) || this;
        _this_1.data = {
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
        _this_1.publicData = [
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
        return _this_1;
    }
    GenerateCalendar.prototype.setBindFunctions = function () {
        var _this = this;
        this.bindFunctions = {
            selectModuleHandler: function (event) {
                jQuery('#generate-calendar-main-container').remove();
                RedooAjax(app_1.App.SCOPE_NAME).postView('GenerateCalendarMainForm', {
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
                    var _form = FORMGenerator.start();
                    _form.setValidators(response.form.validators);
                    eval(response.form.js);
                    _form.init();
                    _form.setContainer('#generate-calendar-main-form');
                    _this.setObservable(document.getElementById('generate-calendar-form'));
                    _this.generateCalendarDialog.toFront();
                    jQuery('.FormGenField ').on('resize_dialog', function (event) {
                        setTimeout(function () {
                            _this.generateCalendarDialog.toFront();
                        });
                    });
                });
            }
        };
    };
    GenerateCalendar.prototype.setGenerateCalendarDialig = function (dialog) {
        this.generateCalendarDialog = dialog;
        return this;
    };
    return GenerateCalendar;
}(Calendar_1.Calendar));
exports.GenerateCalendar = GenerateCalendar;

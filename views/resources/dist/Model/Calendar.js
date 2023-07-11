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
import { Model } from "../Core/Model/Model";
import { ServiceProvider } from "../Core/ServiceProvider/ServiceProvider";
var Calendar = /** @class */ (function (_super) {
    __extends(Calendar, _super);
    function Calendar() {
        var _this_1 = _super !== null && _super.apply(this, arguments) || this;
        _this_1.data = {
            id: '',
            title: '',
            color: '',
            visible: false,
            enabled: true,
            relations: {
                events: []
            },
            code: '',
            connector: 0,
            hide_event_details: true,
            access_mode: '',
            share_to: [],
            share_to_google: '',
            owner: false
        };
        _this_1.publicData = [
            'id',
            'title',
            'color',
            'visible',
            'enabled',
            'code',
            'connector',
            'hide_event_details',
            'access_mode',
            'share_to'
        ];
        _this_1.events = [];
        return _this_1;
    }
    Calendar.prototype.setBindFunctions = function () {
        var _this = this;
        this.bindFunctions = {
            changeColor: function (event) {
                setTimeout(function () {
                    _this.addData('color', jQuery(_this.element).find("[data-field='color']").val());
                    var calendarList = ServiceProvider.getInstance().get('calendarList');
                    if (calendarList.getCollection().hasItem(_this.getData('connector'))) {
                        var connector = calendarList.getCollection().getItem(_this.getData('connector'));
                        connector.calendars.updateItem(_this);
                    }
                });
            },
            changeUsersList: function (event) {
                setTimeout(function () {
                    _this.data.share_to = event.data.share_to_google.split(',');
                });
            }
        };
    };
    Calendar.prototype.getEvents = function () {
        return this.events;
    };
    Calendar.prototype.setEvents = function (events) {
        this.events = events;
        return this;
    };
    Calendar.prototype.addEvent = function (event) {
        this.events.push(event);
        return this;
    };
    Calendar.prototype.removeEvent = function (id) {
        for (var _index = 0; _index < this.events.length; _index++) {
            if (this.events[_index].data.id == id) {
                this.events.splice(_index, 1);
                break;
            }
        }
        return this;
    };
    Calendar.prototype.updateEvent = function (event) {
        for (var _index = 0; _index < this.events.length; _index++) {
            if (this.events[_index].data.id == event.getId()) {
                this.events[_index] = event;
                break;
            }
        }
        return this;
    };
    Calendar.prototype.setObservable = function (element) {
        var _this = this;
        _super.prototype.setObservable.call(this, element);
        jQuery(element).find('.colorpicker').on('change', function (event) {
            _this.addData(jQuery(event.target).attr('data-field'), jQuery(event.target).val());
        });
    };
    return Calendar;
}(Model));
export { Calendar };
//# sourceMappingURL=Calendar.js.map
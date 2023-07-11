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
exports.Event = void 0;
var Model_1 = require("../Core/Model/Model");
var Event = /** @class */ (function (_super) {
    __extends(Event, _super);
    function Event() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        _this.data = {
            id: '',
            title: '',
            description: '',
            calendar_id: '',
            relations: {},
            date_start: Date,
            date_start_timestamp: 0,
            date_end: Date,
            date_end_timestamp: 0,
            code: '',
            activitytype: '',
            connector: 0,
            all_day_event: false,
            taskstatus: '',
            eventstatus: ''
        };
        _this.publicData = [
            'id',
            'calendar_id',
            'connector',
            'date_start_timestamp',
            'date_end_timestamp',
            'title',
            'description',
            'activitytype',
            'taskstatus',
            'eventstatus'
        ];
        return _this;
    }
    return Event;
}(Model_1.Model));
exports.Event = Event;

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
import { Event } from "./Event";
export var EventTypes;
(function (EventTypes) {
    var RedooEvent = /** @class */ (function (_super) {
        __extends(RedooEvent, _super);
        function RedooEvent() {
            var _this = _super !== null && _super.apply(this, arguments) || this;
            _this.publicData = [
                'id',
                'title',
                'calendar_id',
                'date_start_timestamp',
                'date_end_timestamp',
                'code',
                'connector',
                'description',
                'activitytype',
                'eventstatus',
            ];
            return _this;
        }
        return RedooEvent;
    }(Event));
    EventTypes.RedooEvent = RedooEvent;
    var RedooTask = /** @class */ (function (_super) {
        __extends(RedooTask, _super);
        function RedooTask() {
            var _this = _super !== null && _super.apply(this, arguments) || this;
            _this.publicData = [
                'id',
                'title',
                'calendar_id',
                'date_start_timestamp',
                'date_end_timestamp',
                'code',
                'connector',
                'description',
                'taskstatus',
            ];
            return _this;
        }
        return RedooTask;
    }(Event));
    EventTypes.RedooTask = RedooTask;
    var Vtiger = /** @class */ (function (_super) {
        __extends(Vtiger, _super);
        function Vtiger() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        return Vtiger;
    }(Event));
    EventTypes.Vtiger = Vtiger;
    var Google = /** @class */ (function (_super) {
        __extends(Google, _super);
        function Google() {
            var _this = _super !== null && _super.apply(this, arguments) || this;
            _this.publicData = [
                'id',
                'title',
                'calendar_id',
                'date_start_timestamp',
                'date_end_timestamp',
                'code',
                'connector',
                'description',
            ];
            return _this;
        }
        return Google;
    }(Event));
    EventTypes.Google = Google;
})(EventTypes || (EventTypes = {}));
//# sourceMappingURL=EventTypes.js.map
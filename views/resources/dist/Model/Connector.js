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
import { CalendarCollection } from "./Collection/CalendarCollection";
var Connector = /** @class */ (function (_super) {
    __extends(Connector, _super);
    function Connector() {
        var _this = _super.call(this) || this;
        _this.data = {
            title: '',
            id: 0,
            code: '',
            connector: '',
            default: 0,
            relations: {}
        };
        _this.publicData = [
            'title',
            'connector'
        ];
        _this.calendars = new CalendarCollection();
        return _this;
    }
    Connector.prototype.addCalendar = function (calendar) {
        this.calendars.addItem(calendar);
    };
    return Connector;
}(Model));
export { Connector };
//# sourceMappingURL=Connector.js.map
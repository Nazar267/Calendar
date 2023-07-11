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
import { UiComponent } from '../Core/UiComponent/UiComponent';
import { ServiceProvider } from "../Core/ServiceProvider/ServiceProvider";
var SmallCalendar = /** @class */ (function (_super) {
    __extends(SmallCalendar, _super);
    function SmallCalendar() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    SmallCalendar.prototype.init = function () {
        this.calendar = jQuery(this.element).kendoCalendar({
            change: function (event) {
                SmallCalendar.changeHandler(event);
            }
        });
        return this;
    };
    /**
     * @typedef {MainCalendar} mainCalendar
     */
    SmallCalendar.changeHandler = function (event) {
        var mainCalendar = ServiceProvider.getInstance().get('mainCalendar');
        if (mainCalendar !== null) {
            mainCalendar.setDate(event.sender.current());
        }
    };
    return SmallCalendar;
}(UiComponent));
export { SmallCalendar };
//# sourceMappingURL=SmallCalendar.js.map
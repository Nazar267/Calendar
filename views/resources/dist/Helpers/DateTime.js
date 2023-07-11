import { ServiceProvider } from "../Core/ServiceProvider/ServiceProvider";
import * as moment from 'moment-timezone';
var DateTime = /** @class */ (function () {
    function DateTime() {
    }
    DateTime.getSystemDate = function (date) {
        var mainCalendar = ServiceProvider.getInstance().get('mainCalendar');
        return moment(date).tz(mainCalendar.getSystemTimeZone(), true).toDate();
    };
    DateTime.getTimestamp = function (date) {
        return moment(date).utc(false).valueOf() / 1000;
    };
    return DateTime;
}());
export { DateTime };
//# sourceMappingURL=DateTime.js.map
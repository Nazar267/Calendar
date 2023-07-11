"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.DateTime = void 0;
var ServiceProvider_1 = require("../Core/ServiceProvider/ServiceProvider");
var moment = require("moment-timezone");
var DateTime = /** @class */ (function () {
    function DateTime() {
    }
    DateTime.getSystemDate = function (date) {
        var mainCalendar = ServiceProvider_1.ServiceProvider.getInstance().get('mainCalendar');
        return moment(date).tz(mainCalendar.getSystemTimeZone(), true).toDate();
    };
    DateTime.getTimestamp = function (date) {
        return moment(date).utc(false).valueOf() / 1000;
    };
    return DateTime;
}());
exports.DateTime = DateTime;

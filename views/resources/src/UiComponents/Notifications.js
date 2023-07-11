"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Notifications = void 0;
var Notifications = /** @class */ (function () {
    function Notifications(elementId) {
        this.elementId = elementId;
        this.element = document.getElementById(this.elementId);
    }
    Notifications.prototype.init = function () {
        this.entity = jQuery(this.element).kendoNotification().data("kendoNotification");
    };
    Notifications.prototype.info = function (text) {
        this.entity.show(text, "info");
    };
    Notifications.prototype.error = function (text) {
        this.entity.show(text, "error");
    };
    return Notifications;
}());
exports.Notifications = Notifications;

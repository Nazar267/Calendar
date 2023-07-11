"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.UiComponent = void 0;
var ServiceProvider_1 = require("../ServiceProvider/ServiceProvider");
var UiComponent = /** @class */ (function () {
    function UiComponent(elementId) {
        this.elementId = elementId;
        this.element = document.getElementById(this.elementId);
        this.notifications = ServiceProvider_1.ServiceProvider.getInstance().get('notifications');
        this.translator = ServiceProvider_1.ServiceProvider.getInstance().get('translator');
        this.appLanguage = ServiceProvider_1.ServiceProvider.getInstance().get('appLanguage');
    }
    UiComponent.prototype.init = function () {
    };
    UiComponent.prototype.getElement = function () {
        return this.element;
    };
    return UiComponent;
}());
exports.UiComponent = UiComponent;

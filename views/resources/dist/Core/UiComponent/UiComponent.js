import { ServiceProvider } from "../ServiceProvider/ServiceProvider";
var UiComponent = /** @class */ (function () {
    function UiComponent(elementId) {
        this.elementId = elementId;
        this.element = document.getElementById(this.elementId);
        this.notifications = ServiceProvider.getInstance().get('notifications');
        this.translator = ServiceProvider.getInstance().get('translator');
        this.appLanguage = ServiceProvider.getInstance().get('appLanguage');
    }
    UiComponent.prototype.init = function () {
    };
    UiComponent.prototype.getElement = function () {
        return this.element;
    };
    return UiComponent;
}());
export { UiComponent };
//# sourceMappingURL=UiComponent.js.map
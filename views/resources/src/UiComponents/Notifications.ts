import {App} from "../app";

export class Notifications {
    protected entity: kendo.ui.Notification;
    protected element: HTMLElement;
    protected elementId: string;

    public constructor(elementId: string) {
        this.elementId = elementId;
        this.element = document.getElementById(this.elementId)!;
    }

    public init() {
        this.entity = jQuery(this.element).kendoNotification().data("kendoNotification");
    }

    public info(text: string) {
        this.entity.show(text, "info");
    }

    public error(text: string) {
        this.entity.show(text, "error");
    }

}
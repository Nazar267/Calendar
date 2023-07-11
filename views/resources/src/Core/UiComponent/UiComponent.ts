import {UiComponentInterface} from './interface/UiComponentInterface'
import {Notifications} from "../../UiComponents/Notifications";
import {ServiceProvider} from "../ServiceProvider/ServiceProvider";
import {FlexTranslateInstanceInterface} from "../FlexTranslateInterface";



export abstract class UiComponent implements UiComponentInterface {
    protected element: HTMLElement;
    protected elementId: string;
    protected notifications: Notifications;
    protected translator: FlexTranslateInstanceInterface;
    protected appLanguage: string;

    public constructor(elementId: string) {
        this.elementId = elementId;
        this.element = document.getElementById(this.elementId)!;
        this.notifications = ServiceProvider.getInstance().get('notifications') as Notifications;
        this.translator = ServiceProvider.getInstance().get('translator') as FlexTranslateInstanceInterface;
        this.appLanguage = ServiceProvider.getInstance().get('appLanguage') as string;
    }

    public init() {
    }

    public getElement() {
        return this.element;
    }
}
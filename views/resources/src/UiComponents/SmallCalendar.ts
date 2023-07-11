import {UiComponent} from '../Core/UiComponent/UiComponent'
import CalendarEvent = kendo.ui.CalendarEvent;
import {ServiceProvider} from "../Core/ServiceProvider/ServiceProvider";
import {MainCalendar} from "./MainCalendar";

export class SmallCalendar extends UiComponent {
    protected calendar: object;

    public init() {
        this.calendar = jQuery(this.element).kendoCalendar({
            change: function (event: CalendarEvent) {
                SmallCalendar.changeHandler(event);
            }
        });
        return this;
    }

    /**
     * @typedef {MainCalendar} mainCalendar
     */
    protected static changeHandler(event: CalendarEvent) {
        let mainCalendar = ServiceProvider.getInstance().get('mainCalendar') as MainCalendar;
        if (mainCalendar !== null) {
            mainCalendar.setDate(event.sender.current())
        }
    }

}
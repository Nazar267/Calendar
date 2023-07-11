import {ServiceProvider} from "../Core/ServiceProvider/ServiceProvider";
import {MainCalendar} from "../UiComponents/MainCalendar";
import * as moment from 'moment-timezone';

export class DateTime {
    static getSystemDate(date: Date): Date {
        let mainCalendar = ServiceProvider.getInstance().get('mainCalendar') as MainCalendar;

        return moment(date).tz(mainCalendar.getSystemTimeZone(), true).toDate();
    }

    static getTimestamp(date: Date): number {
        return moment(date).utc(false).valueOf() / 1000;
    }
}
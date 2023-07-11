import {Model} from "../Core/Model/Model";
import {CollectionItemInterface} from "../Core/Model/Collection/Interface/CollectionItemInterface";
import {CalendarCollection} from "./Collection/CalendarCollection";
import {CollectionInterface} from "../Core/Model/Collection/Interface/CollectionInterface";
import {Calendar} from "./Calendar";

export class Connector extends Model implements CollectionItemInterface {

    public data = {
        title: '',
        id: 0,
        code: '',
        connector: '',
        default: 0,
        relations: {}
    };

    public publicData = [
        'title',
        'connector'
    ];

    public calendars: CalendarCollection;

    constructor() {
        super();
        this.calendars = new CalendarCollection();
    }

    public addCalendar(calendar: Calendar) {
        this.calendars.addItem(calendar);
    }

}
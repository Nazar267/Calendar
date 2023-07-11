import {Model} from "../Core/Model/Model";
import {CollectionItemInterface} from "../Core/Model/Collection/Interface/CollectionItemInterface";
import {EventInterface} from "./Event/Interface";

export class Event extends Model implements CollectionItemInterface, EventInterface {

    public data = {
        id: '',
        title: '',
        description: '',
        calendar_id: '',
        relations: {},
        date_start: Date,
        date_start_timestamp: 0,
        date_end: Date,
        date_end_timestamp: 0,
        code: '',
        activitytype: '',
        connector: 0,
        all_day_event: false,
        taskstatus: '',
        eventstatus: ''
    };

    publicData = [
        'id',
        'calendar_id',
        'connector',
        'date_start_timestamp',
        'date_end_timestamp',
        'title',
        'description',
        'activitytype',
        'taskstatus',
        'eventstatus'
    ]

}
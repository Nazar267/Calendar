import {EventInterface} from "./Event/Interface";
import {Event} from "./Event";

export namespace EventTypes {

    export class RedooEvent extends Event implements EventInterface {
        data: {
            id: '',
            title: 'RedooEvent',
            description: 'RedooEvent',
            calendar_id: '',
            relations: {},
            date_start: DateConstructor,
            date_start_timestamp: 0,
            date_end: DateConstructor,
            date_end_timestamp: 0,
            code: '',
            connector: 0,
            all_day_event: false,
            activitytype: '',
            taskstatus: '',
            eventstatus: ''
        };

        public publicData = [
            'id',
            'title',
            'calendar_id',
            'date_start_timestamp',
            'date_end_timestamp',
            'code',
            'connector',
            'description',
            'activitytype',
            'eventstatus',
        ]
    }

    export class RedooTask extends Event implements EventInterface {
        data: {
            description: 'RedooTask',
            id: '',
            title: 'RedooTask',
            calendar_id: '',
            relations: {},
            date_start: DateConstructor,
            date_start_timestamp: 0,
            date_end: DateConstructor,
            date_end_timestamp: 0,
            code: '',
            connector: 0
            all_day_event: true,
            taskstatus: '',
            eventstatus: '',
            activitytype: ''
        };

        public publicData = [
            'id',
            'title',
            'calendar_id',
            'date_start_timestamp',
            'date_end_timestamp',
            'code',
            'connector',
            'description',
            'taskstatus',
        ]
    }

    export class Vtiger extends Event implements EventInterface {
        data: {
            description: 'test',
            id: '',
            title: '',
            calendar_id: '',
            relations: {},
            date_start: DateConstructor,
            date_start_timestamp: 0,
            date_end: DateConstructor,
            date_end_timestamp: 0,
            code: '',
            connector: 0,
            all_day_event: false,
            taskstatus: '',
            eventstatus: '',
            activitytype: ''
        }
    }

    export class Google extends Event implements EventInterface {
        data: {
            description: 'test',
            id: '',
            title: '',
            calendar_id: '',
            relations: {},
            date_start: DateConstructor,
            date_start_timestamp: 0,
            date_end: DateConstructor,
            date_end_timestamp: 0,
            code: '',
            connector: 0,
            all_day_event: false,
            eventstatus: '',
            taskstatus: '',
            activitytype: '',
        };

        public publicData = [
            'id',
            'title',
            'calendar_id',
            'date_start_timestamp',
            'date_end_timestamp',
            'code',
            'connector',
            'description',
        ]
    }
}
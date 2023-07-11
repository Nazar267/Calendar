import {Model} from "../Core/Model/Model";
import {Event} from "./Event";
import {CollectionItemInterface} from "../Core/Model/Collection/Interface/CollectionItemInterface";
import ObservableObject = kendo.data.ObservableObject;
import {Connector} from "./Connector";
import {ServiceProvider} from "../Core/ServiceProvider/ServiceProvider";
import {CalendarList} from "../UiComponents/CalendarList";
import {CollectionInterface} from "../Core/Model/Collection/Interface/CollectionInterface";

export class Calendar extends Model implements CollectionItemInterface {

    public prototype: any;
    public connector: Connector;


    public data = {
        id: '',
        title: '',
        color: '',
        visible: false,
        enabled: true,
        relations: {
            events: []
        },
        code: '',
        connector: 0,
        hide_event_details: true,
        access_mode: '',
        share_to: [],
        share_to_google: '',
        owner: false
    };

    publicData = [
        'id',
        'title',
        'color',
        'visible',
        'enabled',
        'code',
        'connector',
        'hide_event_details',
        'access_mode',
        'share_to'
    ];

    protected events: Array<Event> = [];

    setBindFunctions() {
        let _this = this;
        this.bindFunctions = {
            changeColor: function (event: any) {
                setTimeout(function () {
                    _this.addData('color', jQuery(_this.element).find("[data-field='color']").val());
                    let calendarList = ServiceProvider.getInstance().get('calendarList') as CalendarList;
                    if (calendarList.getCollection().hasItem(_this.getData('connector'))) {
                        let connector = calendarList.getCollection().getItem(_this.getData('connector')) as Connector;
                        connector.calendars.updateItem(_this);
                    }
                });
            },
            changeUsersList: function (event: any) {
                setTimeout(function () {
                    _this.data.share_to = event.data.share_to_google.split(',')
                });
            }
        }
    }

    public getEvents(): Array<Event> {
        return this.events;
    }

    public setEvents(events: Array<Event>): Calendar {
        this.events = events;
        return this;
    }

    public addEvent(event: Event): Calendar {
        this.events.push(event);
        return this;
    }

    public removeEvent(id: string): Calendar {
        for (let _index = 0; _index < this.events.length; _index++) {
            if (this.events[_index].data.id == id) {
                this.events.splice(_index, 1);
                break;
            }
        }
        return this;
    }

    public updateEvent(event: Event): Calendar {
        for (let _index = 0; _index < this.events.length; _index++) {
            if (this.events[_index].data.id == event.getId()) {
                this.events[_index] = event;
                break;
            }
        }
        return this;
    }

    setObservable(element: HTMLElement) {
        let _this = this;
        super.setObservable(element);
        jQuery(element).find('.colorpicker').on('change', function (event) {

            _this.addData(jQuery(event.target).attr('data-field') as string, jQuery(event.target).val());
        })
    }

}
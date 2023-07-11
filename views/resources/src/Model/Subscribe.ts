import {Model} from "../Core/Model/Model";
import {CollectionItemInterface} from "../Core/Model/Collection/Interface/CollectionItemInterface";
import {EventInterface} from "./Event/Interface";

export class Subscribe extends Model implements CollectionItemInterface, EventInterface {

    public data = {
        id: '',
        title: '',
        relations: {},
        code: '',
        calendar_id: '',
        color: '',
    };

    publicData = [
        'calendar_id',
        'color'
    ]
}
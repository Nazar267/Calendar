import {Collection} from "../../Core/Model/Collection/Collection";
import {CollectionItemInterface} from "../../Core/Model/Collection/Interface/CollectionItemInterface";

export class ConnectorCollection extends Collection {

    public getItem(index: string|number): CollectionItemInterface | null {
        for (let _index = 0; _index < this.items.length; _index++) {
            if (this.items[_index].data.id == index) {
                return this.items[_index];
            }
        }
        return null;
    }

}
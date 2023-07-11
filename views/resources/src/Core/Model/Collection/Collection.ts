import {CollectionInterface} from "./Interface/CollectionInterface";

import {CollectionItemInterface} from "./Interface/CollectionItemInterface";
import {Model} from "../Model";

export abstract class Collection implements CollectionInterface {

    public items: Array<CollectionItemInterface>;

    constructor() {
        this.items = [];
    }

    public getItem(index: string): CollectionItemInterface | null {
        for (let _index = 0; _index < this.items.length; _index++) {
            if (this.items[_index].data.id == index) {
                return this.items[_index];
            }
        }
        return null;
    }

    public hasItem(index: string): boolean {
        return typeof this.items[0] !== 'undefined';
    }

    public addItem(item: CollectionItemInterface): CollectionInterface {
        this.items.push(item);
        return this;
    }

    public removeItem(index: string): CollectionInterface {
        for (let _index = 0; _index < this.items.length; _index++) {
            if (this.items[_index].data.id == index) {
                this.items.splice(_index, 1);
                break;
            }
        }
        return this;
    }

    public setItems(items: Array<CollectionItemInterface>): CollectionInterface {
        this.items = items;
        return this;
    }

    public updateItem(item: CollectionItemInterface): CollectionInterface {
        for (let _index = 0; _index < this.items.length; _index++) {
            if (this.items[_index].data.id == item.data.id) {
                this.items.splice(_index, 1, item);
                break;
            }
        }
        return this;
    }

    public getFirst(): CollectionItemInterface {
        return this.items[0];
    }
}
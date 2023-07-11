import {CollectionItemInterface} from "./Interface/CollectionItemInterface";

export class CollectionItem implements CollectionItemInterface {
    public data: {
        id?: number
    };

    public getId(): number {
        return this.data.id;
    }

    public setData(data: any): any {
        this.data = data;
    }
}
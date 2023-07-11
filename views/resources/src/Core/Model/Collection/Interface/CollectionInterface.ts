import {CollectionItemInterface} from "./CollectionItemInterface";

export interface CollectionInterface {
    getItem(index: string): CollectionItemInterface | null

    addItem(item: CollectionItemInterface): CollectionInterface

    hasItem(index: string): boolean

    removeItem(index: string): CollectionInterface

    setItems(items: Array<CollectionItemInterface>): CollectionInterface

    updateItem(item: CollectionItemInterface): CollectionInterface
}
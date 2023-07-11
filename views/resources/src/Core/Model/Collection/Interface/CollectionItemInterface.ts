import ObservableObject = kendo.data.ObservableObject;

export interface CollectionItemInterface {
    data: {
        id?: any
        title?: string
        code?: string
    };

    setData(data: any): any;

    getId(): string | number;

    addData(field: string, value: any): CollectionItemInterface;

    getData(field: string): any;
}
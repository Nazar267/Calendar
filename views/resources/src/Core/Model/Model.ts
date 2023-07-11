import {ModelInterface} from "./Interface/ModelInterface";
import ObservableObject = kendo.data.ObservableObject;
import {CollectionItemInterface} from "./Collection/Interface/CollectionItemInterface";

export abstract class Model implements ModelInterface {

    protected observable: ObservableObject;
    public data = {
        title: '',
        relations: {},
        code: '',
    };

    protected element: HTMLElement;

    public publicData: string[];

    public bindFunctions: {};

    public setBindFunctions() {
        this.bindFunctions = {};
    }

    public setData(data: any) {
        this.data = data;
        return this;
    }

    public getObject() {
        return this;
    }

    public setObservable(element: HTMLElement) {
        this.element = element;
        this.setBindFunctions();
        let _this = this;

        for (let index = 0; index < Object.keys(_this.data).length; index++) {
            let key = Object.keys(_this.data)[index];
            let input = jQuery(element).find("[data-field='" + key + "']");

            if (input.length > 0) {
                _this.addData(key, input.val());
            }
        }

        this.observable = kendo.observable(
            Object.assign(this.data, this.bindFunctions)
        );
        this.observable.bind("change", function (event: any) {
            // @ts-ignore
            _this.data[event.field] = event.sender.get(event.field);
        });

        kendo.bind(jQuery(element), this.observable);
    }

    public getObservable() {
        return this.observable;
    }

    public getId() {
        return this.getData('id');
    }

    getData(field: string): any {
        // @ts-ignore
        return this.data[field];
    }

    addData(field: string, value: any): CollectionItemInterface {
        // @ts-ignore
        this.data[field] = value;

        if (this.getObservable()) this.getObservable().set(field, value);
        return this;
    }

    public getAll(): any {
        let result = {};
        for (let item in this.publicData) {
            // @ts-ignore
            let field = this.publicData[item];

            // @ts-ignore
            if (this.data[field] === null) continue;

            // @ts-ignore
            if (typeof this.data[field] == 'object') {
                // @ts-ignore
                result[field] = this.data[field].toJSON ? this.data[field].toJSON().toString() : this.data[field].toString();
            } else {
                // @ts-ignore
                result[field] = this.data[field];
            }
        }
        return result;
    }
}
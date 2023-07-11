"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Model = void 0;
var Model = /** @class */ (function () {
    function Model() {
        this.data = {
            title: '',
            relations: {},
            code: '',
        };
    }
    Model.prototype.setBindFunctions = function () {
        this.bindFunctions = {};
    };
    Model.prototype.setData = function (data) {
        this.data = data;
        return this;
    };
    Model.prototype.getObject = function () {
        return this;
    };
    Model.prototype.setObservable = function (element) {
        this.element = element;
        this.setBindFunctions();
        var _this = this;
        for (var index = 0; index < Object.keys(_this.data).length; index++) {
            var key = Object.keys(_this.data)[index];
            var input = jQuery(element).find("[data-field='" + key + "']");
            if (input.length > 0) {
                _this.addData(key, input.val());
            }
        }
        this.observable = kendo.observable(Object.assign(this.data, this.bindFunctions));
        this.observable.bind("change", function (event) {
            // @ts-ignore
            _this.data[event.field] = event.sender.get(event.field);
        });
        kendo.bind(jQuery(element), this.observable);
    };
    Model.prototype.getObservable = function () {
        return this.observable;
    };
    Model.prototype.getId = function () {
        return this.getData('id');
    };
    Model.prototype.getData = function (field) {
        // @ts-ignore
        return this.data[field];
    };
    Model.prototype.addData = function (field, value) {
        // @ts-ignore
        this.data[field] = value;
        if (this.getObservable())
            this.getObservable().set(field, value);
        return this;
    };
    Model.prototype.getAll = function () {
        var result = {};
        for (var item in this.publicData) {
            // @ts-ignore
            var field = this.publicData[item];
            // @ts-ignore
            if (this.data[field] === null)
                continue;
            // @ts-ignore
            if (typeof this.data[field] == 'object') {
                // @ts-ignore
                result[field] = this.data[field].toJSON ? this.data[field].toJSON().toString() : this.data[field].toString();
            }
            else {
                // @ts-ignore
                result[field] = this.data[field];
            }
        }
        return result;
    };
    return Model;
}());
exports.Model = Model;

"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Collection = void 0;
var Collection = /** @class */ (function () {
    function Collection() {
        this.items = [];
    }
    Collection.prototype.getItem = function (index) {
        for (var _index = 0; _index < this.items.length; _index++) {
            if (this.items[_index].data.id == index) {
                return this.items[_index];
            }
        }
        return null;
    };
    Collection.prototype.hasItem = function (index) {
        return typeof this.items[0] !== 'undefined';
    };
    Collection.prototype.addItem = function (item) {
        this.items.push(item);
        return this;
    };
    Collection.prototype.removeItem = function (index) {
        for (var _index = 0; _index < this.items.length; _index++) {
            if (this.items[_index].data.id == index) {
                this.items.splice(_index, 1);
                break;
            }
        }
        return this;
    };
    Collection.prototype.setItems = function (items) {
        this.items = items;
        return this;
    };
    Collection.prototype.updateItem = function (item) {
        for (var _index = 0; _index < this.items.length; _index++) {
            if (this.items[_index].data.id == item.data.id) {
                this.items.splice(_index, 1, item);
                break;
            }
        }
        return this;
    };
    Collection.prototype.getFirst = function () {
        return this.items[0];
    };
    return Collection;
}());
exports.Collection = Collection;

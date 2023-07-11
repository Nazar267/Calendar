"use strict";
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.ConnectorCollection = void 0;
var Collection_1 = require("../../Core/Model/Collection/Collection");
var ConnectorCollection = /** @class */ (function (_super) {
    __extends(ConnectorCollection, _super);
    function ConnectorCollection() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    ConnectorCollection.prototype.getItem = function (index) {
        for (var _index = 0; _index < this.items.length; _index++) {
            if (this.items[_index].data.id == index) {
                return this.items[_index];
            }
        }
        return null;
    };
    return ConnectorCollection;
}(Collection_1.Collection));
exports.ConnectorCollection = ConnectorCollection;

"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Loading = void 0;
var progress = kendo.ui.progress;
var Loading = /** @class */ (function () {
    function Loading(elementId) {
        if (elementId === void 0) { elementId = 'html'; }
        this.elementId = elementId;
        if (elementId == 'html') {
            this.element = document.getElementsByTagName(this.elementId)[0];
        }
        else {
            this.element = document.getElementById(this.elementId);
        }
    }
    Loading.prototype.startLoading = function () {
        progress(jQuery(this.element), true);
    };
    Loading.prototype.stopLoading = function () {
        progress(jQuery(this.element), false);
    };
    return Loading;
}());
exports.Loading = Loading;

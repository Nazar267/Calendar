"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ServiceProvider = void 0;
require("./interface/Window");
/**
 * Class provide access to service container
 */
var ServiceProvider = exports.ServiceProvider = /** @class */ (function () {
    function ServiceProvider() {
        this.container = {};
    }
    ServiceProvider.getInstance = function () {
        if (window._serviceProviderInstance) {
            return window._serviceProviderInstance;
        }
        return window._serviceProviderInstance = new ServiceProvider();
    };
    /**
     * Set service object
     * @param name
     * @param service
     */
    ServiceProvider.prototype.set = function (name, service) {
        // @ts-ignore
        this.container[name] = service;
    };
    /**
     * Get service object
     * @param name
     */
    ServiceProvider.prototype.get = function (name) {
        // @ts-ignore
        if (typeof this.container[name] !== 'undefined') {
            // @ts-ignore
            return this.container[name];
        }
        return null;
    };
    /**
     * Check if service already register
     * @param name
     */
    ServiceProvider.prototype.has = function (name) {
        // @ts-ignore
        return typeof this.container[name] !== 'undefined';
    };
    /**
     * Delete service form container
     * @param name
     */
    ServiceProvider.prototype.delete = function (name) {
        // @ts-ignore
        return delete this.container[name];
    };
    ServiceProvider.CONSTRUCTOR_NOT_AVAILABLE_ERROR = 'Instantiation failed: use Singleton.getInstance() instead of new.';
    return ServiceProvider;
}());

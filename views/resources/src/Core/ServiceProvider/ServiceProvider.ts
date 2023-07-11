import './interface/Window';
import {ServiceProviderInterface} from "./interface/ServiceProviderInterface";

/**
 * Class provide access to service container
 */
export class ServiceProvider implements ServiceProviderInterface {

    static CONSTRUCTOR_NOT_AVAILABLE_ERROR: string = 'Instantiation failed: use Singleton.getInstance() instead of new.';
    protected container: object = {};

    private constructor() {

    }

    public static getInstance(): ServiceProvider {
        if (window._serviceProviderInstance) {
            return window._serviceProviderInstance;
        }
        return window._serviceProviderInstance = new ServiceProvider();
    }

    /**
     * Set service object
     * @param name
     * @param service
     */
    public set(name: string, service: object): void {
        // @ts-ignore
        this.container[name] = service;
    }

    /**
     * Get service object
     * @param name
     */
    public get(name: string): string | object | null {
        // @ts-ignore
        if (typeof this.container[name] !== 'undefined') {
            // @ts-ignore
            return this.container[name];
        }
        return null;
    }

    /**
     * Check if service already register
     * @param name
     */
    public has(name: string): boolean {
        // @ts-ignore
        return typeof this.container[name] !== 'undefined';
    }

    /**
     * Delete service form container
     * @param name
     */
    public delete(name: string): boolean {
        // @ts-ignore
        return delete this.container[name];
    }
}
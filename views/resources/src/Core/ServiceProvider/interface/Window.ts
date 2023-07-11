import {ServiceProvider} from "../ServiceProvider";

declare global {
    interface Window {
        _serviceProviderInstance: ServiceProvider;
    }
}
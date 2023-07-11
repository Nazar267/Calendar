export interface ServiceProviderInterface {
    get(name: string): string | object | null

    set(name: string, service: object): void

    has(name: string): boolean

    delete(name: string): boolean
}
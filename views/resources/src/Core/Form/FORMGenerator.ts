export interface FORMGenerator {
    start(): FORMGenerator;

    init(): any;

    isValid(): boolean;

    setValidators(validators: any): any;

    setContainer(container: any): any;
}
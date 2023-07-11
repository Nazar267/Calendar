interface FlexTranslateObject {
    init(language: string, translations: object): void;

    getTranslator(): FlexTranslateInstanceInterface;

    __(string: string): string;
}

export interface FlexTranslateInstanceInterface {
    (string: string): string;
}

export interface FlexTranslateInterface {
    (scopeName: string): FlexTranslateObject;
}

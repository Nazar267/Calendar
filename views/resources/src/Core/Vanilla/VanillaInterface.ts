interface RedooAjaxObject {
    postAction(actionname: any, parameters?: object, withinSettings?: boolean, ResponseDataType?: string): RedooAjaxObserver;

    postView(viewname: any, parameters?: object, withinSettings?: boolean, ResponseDataType?: string): RedooAjaxObserver;

}

interface RedooAjaxObserver {
    then(callback: RedooAjaxCallback): void;
}

interface RedooAjaxCallback {
    (response: any): void
}

export interface RedooAjaxInterface {
    (scopeName: string): RedooAjaxObject;
}


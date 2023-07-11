import progress = kendo.ui.progress;

export class Loading {

    protected element: HTMLElement;
    protected elementId: string;

    public constructor(elementId: string = 'html') {
        this.elementId = elementId;

        if(elementId == 'html') {
            this.element = document.getElementsByTagName(this.elementId)[0] as HTMLElement;
        } else {
            this.element = document.getElementById(this.elementId) as HTMLElement;
        }
    }

    public startLoading() {
        progress(jQuery(this.element), true);
    }

    public stopLoading() {
        progress(jQuery(this.element), false);
    }
}
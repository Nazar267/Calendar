/// <reference path="../node_modules/@types/kendo-ui/index.d.ts" />

import {MainCalendar} from "./UiComponents/MainCalendar";
import {SmallCalendar} from "./UiComponents/SmallCalendar";
import {ServiceProvider} from "./Core/ServiceProvider/ServiceProvider";
import {CalendarList} from "./UiComponents/CalendarList";
import {RedooAjaxInterface} from "./Core/Vanilla/VanillaInterface";
import {Notifications} from "./UiComponents/Notifications";
import {FORMGenerator} from "./Core/Form/FORMGenerator";
import {Loading} from "./UiComponents/Loading";
import {FlexTranslateInterface} from "./Core/FlexTranslateInterface";

import '@progress/kendo-ui/js/cultures/kendo.culture.de-DE.min';
import '@progress/kendo-ui/js/cultures/kendo.culture.en-US.min';

declare global {
    const RedooAjax: RedooAjaxInterface;
    const FlexTranslate: FlexTranslateInterface;
    const FORMGenerator: FORMGenerator;
}
/**
 * Main application class
 */
export class App {
    static LARGE_CALENDAR_ELEMENT_ID: string = 'large-calendar';
    static SMALL_CALENDAR_ELEMENT_ID: string = 'small-calendar';
    static CALENDAR_LIST_ELEMENT_ID: string = 'calendar-list';
    static CALENDAR_WRAP_ELEMENT_ID: string = 'calendar-wrap';
    static LOADING_ELEMENT_ID: string = 'html';
    static NOTIFICATIONS_BLOCK = 'calendar-notification';
    static SCOPE_NAME: string = 'RedooCalendar';

    public static init() {
        let _this = this;

        RedooAjax('RedooCalendar').postAction('Translations', {}, false, 'json').then(function (response) {
            let culture = response.language.substring(0, 2) + '-' + response.language.substr(3, 2).toUpperCase();
            kendo.culture(culture);

            let translator = FlexTranslate('RedooCalendar');
            translator.init(response.language, response.translations ? response.translations : {});
            ServiceProvider.getInstance().set('translator', translator.getTranslator());
            ServiceProvider.getInstance().set('appLanguage', response.language);

            let notifications = new Notifications(App.NOTIFICATIONS_BLOCK);
            let loading = new Loading(App.LOADING_ELEMENT_ID);
            notifications.init();
            ServiceProvider.getInstance().set('notifications', notifications);
            ServiceProvider.getInstance().set('loading', loading);

            let smallCalendar = new SmallCalendar(App.SMALL_CALENDAR_ELEMENT_ID);
            let mainCalendar = new MainCalendar(App.LARGE_CALENDAR_ELEMENT_ID);
            let calendarList = new CalendarList(App.CALENDAR_LIST_ELEMENT_ID);

            smallCalendar.init();
            mainCalendar.init();
            calendarList.init();

            ServiceProvider.getInstance().set('smallCalendar', smallCalendar);
            ServiceProvider.getInstance().set('mainCalendar', mainCalendar);
            ServiceProvider.getInstance().set('calendarList', calendarList);

            _this.initLayout();
        });


    }

    protected static initLayout() {
        let wrapElement = jQuery('#' + App.CALENDAR_WRAP_ELEMENT_ID)
        let windowHeight = jQuery(window).height() || 0;

        // 88 = Height Header + 2px Border + 2px Rendering Buffer
        wrapElement.css({'height': (windowHeight - 88) + 'px'});

        wrapElement.kendoSplitter({
            panes: [
                {
                    size: '400px',
                    min: '200px',
                },
                {
                    //min: '400px',
                },
            ]
        });
    }
}

/**
 * Entry point
 */
App.init();

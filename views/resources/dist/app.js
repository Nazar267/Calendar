/// <reference path="../node_modules/@types/kendo-ui/index.d.ts" />
import { MainCalendar } from "./UiComponents/MainCalendar";
import { SmallCalendar } from "./UiComponents/SmallCalendar";
import { ServiceProvider } from "./Core/ServiceProvider/ServiceProvider";
import { CalendarList } from "./UiComponents/CalendarList";
import { Notifications } from "./UiComponents/Notifications";
import { Loading } from "./UiComponents/Loading";
import '@progress/kendo-ui/js/cultures/kendo.culture.de-DE.min';
import '@progress/kendo-ui/js/cultures/kendo.culture.en-US.min';
/**
 * Main application class
 */
export var App = /** @class */ (function () {
    function App() {
    }
    App.init = function () {
        var _this = this;
        RedooAjax('RedooCalendar').postAction('Translations', {}, false, 'json').then(function (response) {
            var culture = response.language.substring(0, 2) + '-' + response.language.substr(3, 2).toUpperCase();
            kendo.culture(culture);
            var translator = FlexTranslate('RedooCalendar');
            translator.init(response.language, response.translations ? response.translations : {});
            ServiceProvider.getInstance().set('translator', translator.getTranslator());
            ServiceProvider.getInstance().set('appLanguage', response.language);
            var notifications = new Notifications(App.NOTIFICATIONS_BLOCK);
            var loading = new Loading(App.LOADING_ELEMENT_ID);
            notifications.init();
            ServiceProvider.getInstance().set('notifications', notifications);
            ServiceProvider.getInstance().set('loading', loading);
            var smallCalendar = new SmallCalendar(App.SMALL_CALENDAR_ELEMENT_ID);
            var mainCalendar = new MainCalendar(App.LARGE_CALENDAR_ELEMENT_ID);
            var calendarList = new CalendarList(App.CALENDAR_LIST_ELEMENT_ID);
            smallCalendar.init();
            mainCalendar.init();
            calendarList.init();
            ServiceProvider.getInstance().set('smallCalendar', smallCalendar);
            ServiceProvider.getInstance().set('mainCalendar', mainCalendar);
            ServiceProvider.getInstance().set('calendarList', calendarList);
            _this.initLayout();
        });
    };
    App.initLayout = function () {
        var wrapElement = jQuery('#' + App.CALENDAR_WRAP_ELEMENT_ID);
        var windowHeight = jQuery(window).height() || 0;
        // 88 = Height Header + 2px Border + 2px Rendering Buffer
        wrapElement.css({ 'height': (windowHeight - 88) + 'px' });
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
    };
    App.LARGE_CALENDAR_ELEMENT_ID = 'large-calendar';
    App.SMALL_CALENDAR_ELEMENT_ID = 'small-calendar';
    App.CALENDAR_LIST_ELEMENT_ID = 'calendar-list';
    App.CALENDAR_WRAP_ELEMENT_ID = 'calendar-wrap';
    App.LOADING_ELEMENT_ID = 'html';
    App.NOTIFICATIONS_BLOCK = 'calendar-notification';
    App.SCOPE_NAME = 'RedooCalendar';
    return App;
}());
/**
 * Entry point
 */
App.init();
//# sourceMappingURL=app.js.map
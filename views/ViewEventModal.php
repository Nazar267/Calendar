<?php


global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");


use RedooCalendar\Base\Connection\ConnectorPlugin\ConnectorPluginInterface;
use RedooCalendar\Base\View\BaseView;
use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\EventForm as Form;
use RedooCalendar\Base\Form\Field;
use RedooCalendar\Base\Form\Validator\Mandatory;
use RedooCalendar\Source\CalendarAccessMode;
use RedooCalendar\Source\ConnectionType;

class RedooCalendar_ViewEventModal_View extends BaseView
{
    public function process(Vtiger_Request $request)
    {

        $calendar = $this->getConnector()->getCalendar($request->get('calendar_id'));



        if($calendar->getData()['id'] == null)
        {

            $idUser = $this->getConnector()->getData()['user_id'];

            $calendar = $this->getConnector()->getCalendar('user_'.$idUser);

        }

        $event = $this->getConnector()->getEvent($calendar, $request->get('id'));

        $owner_text = "";

        //if user currently have vacation, we need to display owner and vaction end date
        if($event->getData('endvacationdate') != NULL)
        {
            $owner_text = "Owner is in vacation till ".$event->getData('endvacationdate');
        }

        $viewer = $this->getViewer($request);
        $viewer->assign('event', $event);
        $viewer->assign('owner_text', $owner_text);
        $viewer->assign('event_date', $event_date);
        $viewer->assign('calendar', $calendar);
        $viewer->assign('connector', $this->getConnector());
        $viewer->assign('currentUser', $this->getUser());
        $viewer->view('Modal/ViewEvent.tpl', 'RedooCalendar');

        return;
    }
}

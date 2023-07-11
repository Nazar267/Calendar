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

class RedooCalendar_AddCalendarModal_View extends BaseView
{
    public function process(Vtiger_Request $request)
    {
        $form = new Form();
        $form->setFormId('create-calendar-form');
        $form->setWidth(600);
        $tab = $form->addTab('General');
        $group = $tab->addGroup('General');

        $connections = [null => self::t('Select Option')];
        /** @var Connection $_connection */
        foreach (Connection::getAvailableConnections($this->getUser()) as $_connection) {
            if ($_connection->getConnector()->allowCreateCalendars()) {
                $connections[$_connection->getId()] = $_connection->getTitle();
            }
        }

        $group->addField()
            ->setLabel('Connector')
            ->setBindValue('connector')
            ->setName('connector')
            ->setOptions([
                'options' => $connections
            ])
            ->addValidator(new Mandatory())
            ->setType(Field::INPUT_PICKLIST);

        $group->addField()
            ->setLabel('Title')
            ->setBindValue('title')
            ->setName('title')
            ->addValidator(new Mandatory())
            ->setType(Field::INPUT_TEXT);

        $group->addField()
            ->setLabel('Color')
            ->setBindValue('color')
            ->setName('color')
            ->addValidator(new Mandatory())
            ->setOptions([
                "#f0d0c9", "#e2a293", "#d4735e", "#65281a",
                "#eddfda", "#dcc0b6", "#cba092", "#7b4b3a",
                "#fcecd5", "#f9d9ab", "#f6c781", "#c87d0e",
                "#e1dca5", "#d0c974", "#a29a36", "#514d1b",
                "#c6d9f0", "#8db3e2", "#548dd4", "#17365d"
            ])
            ->setType(Field::INPUT_COLOR);

        $group = $tab->addGroup('Access');


        $group->addField()
            ->setLabel('Access')
            ->setBindValue('access_mode')
            ->setName('access_mode')
            ->addValidator(new Mandatory())
            ->setOptions([
                'options' => CalendarAccessMode::getOptionsData()
            ])
            ->setType(Field::INPUT_PICKLIST);

        foreach (Connection::getAvailableConnections($this->getUser()) as $_connection) {
            $_connection->getConnector()->getShareUserList($group);
        }

        $group->addField()
            ->setLabel('Hide Event Details')
            ->setBindValue('hide_event_details')
            ->setName('hide_event_details')
            ->setRelated([
                'access_mode' => ['share', 'public']
            ])
            ->setType(Field::INPUT_CHECKBOX);


        echo json_encode(
            $form->getFrontendData()
        );
        return;
    }
}

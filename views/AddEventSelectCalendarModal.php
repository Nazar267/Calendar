<?php


global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Connection\ConnectorPlugin;
use RedooCalendar\Base\View\BaseView;
use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\EventForm as Form;
use RedooCalendar\Base\Form\Field;


class RedooCalendar_AddEventSelectCalendarModal_View extends BaseView
{
    public function process(Vtiger_Request $request)
    {
        $form = new Form();
        $form->setFormId('create-event-select-calendar');
        $form->setWidth(600);
        $tab = $form->addTab('General');
        $group = $tab->addGroup('Calendar');

        $connectorCollection = Connection::getAvailableConnections($this->getUser());

        $calendars = [null => self::t('Select Option')];
        /** @var Connection $connector */
        foreach ($connectorCollection as $connector) {
            if ($connector->getConnectionProcessor() === 'custom') continue;

            $collection = $connector->getConnector()
                ->getSubscribedCalendarCollection();

            foreach ($collection as $item) {
                $calendars[$connector->getId() . ':' . $item['id']] = $item['title'] . ' (' . $connector->getTitle() . ')';
            }
        }

        $group->addField()
            ->setLabel('Calendar')
            ->setBindValue('calendar_id')
            ->setChangeHandler('changeCalendar')
            ->setName('calendar_id')
            ->setOptions([
                'options' => $calendars
            ])
            ->setType(Field::INPUT_PICKLIST);

        echo json_encode(
            $form->getFrontendData()
        );
    }
}

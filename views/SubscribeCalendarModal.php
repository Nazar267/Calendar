<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\Connection\ConnectorPlugin;
use RedooCalendar\Base\Form;
use RedooCalendar\Base\View\BaseView;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;
use RedooCalendar\Base\Form\Validator\Mandatory;

class RedooCalendar_SubscribeCalendarModal_View extends BaseView
{
    public function process(Vtiger_Request $request)
    {
        $form = new Form();
        $form->setFormId('subscribe-calendar');
        $form->setWidth(600);
        $tab = $form->addTab('General');
        $group = $tab->addGroup('Calendar');

        $user = Users_Record_Model::getCurrentUserModel();

        $connectorCollection = Connection::getAvailableConnections($user);

        $calendars = [null => self::t('Select Option')];

        /** @var Connection $connector */

        foreach ($connectorCollection as $connector) {
            $subscribeCollection = new SubscribeCollection();
            $subscribeCollection->fetch([
                [
                    'column' => 'connection_id',
                    'value' => $connector->getConnector()->getId()
                ]
            ]);

            $collection = $connector->getConnector()
                ->getUnsubscribedCalendarCollection();

            $collection = array_filter($collection, function ($item) use ($subscribeCollection) {
                return !in_array($item['id'], $subscribeCollection->getCalendarIds());
            });

            foreach ($collection as $item) {
                $calendars[$connector->getConnector()->getId() . ':' . $item['id']] = $item['title'] . ' (' . $connector->getTitle() . ')';
            }
        }

        $group->addField()
            ->setLabel('Calendar')
            ->setBindValue('calendar_id')
            ->setName('calendar_id')
            ->addValidator(new Mandatory())
            ->setOptions([
                'options' => $calendars
            ])
            ->setType(Form\Field::INPUT_PICKLIST);


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
            ->setType(Form\Field::INPUT_COLOR);

        echo json_encode(
            $form->getFrontendData()
        );
        return;
    }
}

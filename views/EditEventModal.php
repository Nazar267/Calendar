<?php


global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Form\Field;
use RedooCalendar\Base\Form\Type;
use RedooCalendar\Base\Form\Type\Hidden;
use RedooCalendar\Base\View\BaseView;
use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\Connection\ConnectorPlugin\ConnectorPluginInterface;
use RedooCalendar\Base\EventForm as Form;


class RedooCalendar_EditEventModal_View extends BaseView
{
    public function process(Vtiger_Request $request)
    {
        $form = new Form();
        $form->setFormId('create-event');
        $form->setWidth(600);

        $calendar_id = $request->get('calendar_id');

        $connection = Connection::GetInstance($request->get('connector'));

        $calendar = $connection->getConnector()->getCalendar($request->get('calendar_id'));

        if($calendar->getData()['id'] == null)
        {

            $idUser = $this->getConnector()->getData()['user_id'];

            $calendar = $this->getConnector()->getCalendar('user_'.$idUser);

        }
        $event = $connection->getConnector()->getEvent($calendar, $request->get('id'));


        $req = $_REQUEST;

        $date_start_tmp = $event->getData("date_start");
        $date_end_tmp = $event->getData("date_end");


        if (!$connection) {
            echo json_encode([
                'success' => false
            ]);
            return;
        }
        $eventConfig = $connection->getConnector()->getEventConfig();

        foreach ($eventConfig as $eventTypeCode => $eventType) {
            $tab = $form->addTab($eventType['headline']);
            $tab->setModel($eventType['model']);
            $group = null;

            $eventType['blocks']["general"]['fields'][] = ["headline" => "Description", "name" => "description", "type" => "text"];

            foreach ($eventType['blocks'] as $block) {
                $group = $tab->addGroup($block['headline']);
                $group->setColumCount($block['column_count']);
                foreach ($block['fields'] as $field) {
                    $_field = $group->addField()
                        ->setId($field['id'])
                        ->setBindValue($field['name'])
                        ->setLabel($field['headline'])
                        ->setPath([$eventTypeCode])
                        ->setValue($event->getData($field['name']))
                        ->setName($field['name'])
                        ->setType($field['type']);

                    if (isset($field['options'])) {
                        foreach ($field['options']['options'] as &$option) {
                            $option = self::t($option);
                        }
                        $_field->setOptions($field['options']);
                    }

                    if (isset($field['validator'])) {
                        $_field->addValidator(new $field['validator']());
                    }
                }
            }
            $group->addField()
                ->setId('id')
                ->setBindValue('id')
                ->setValue($event->getData('id'))
                ->setName('id')
                ->setType(Field::INPUT_HIDDEN);

            $group->addField()
                ->setId('calendar_id')
                ->setBindValue('calendar_id')
                ->setValue($calendar->getId())
                ->setName('calendar_id')
                ->setType(Field::INPUT_HIDDEN);

            $group->addField()
                ->setId('connector')
                ->setBindValue('connector')
                ->setValue($connection->getId())
                ->setName('connector')
                ->setType(Field::INPUT_HIDDEN);
        }

        echo json_encode([
            'form' => $form->getFrontendData(),
            'connector' => $connection->getData(),
            'model' => reset($eventConfig)['model']
        ]);
        return;
    }
}

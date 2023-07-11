<?php


global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\View\BaseView;
use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\Connection\ConnectorPlugin\ConnectorPluginInterface;
use RedooCalendar\Base\EventForm as Form;


class RedooCalendar_AddEventModal_View extends BaseView
{
    public function process(Vtiger_Request $request)
    {
        $form = new Form();
        $form->setFormId('create-event');
        $form->setWidth(600);


        $connection = $this->getConnector();
        $connectionData = $connection->getData();

        if (!$connection) {
            echo json_encode([
                'success' => false
            ]);
            return;
        }
        $eventConfig = $connection->getEventConfig();
       // $tmp = $connection->getUserTimeZone();
         $tmp = $connection->getCalendar($request->get('calendar_id'));

        foreach ($eventConfig as $eventTypeCode => $eventType) {
            $tab = $form->addTab($eventType['headline']);
            $tab->setModel($eventType['model']);

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
        }

        echo json_encode([
            'form' => $form->getFrontendData(),
            'connector' => $connectionData,
            'model' => reset($eventConfig)['model']
        ]);
        return;
    }
}

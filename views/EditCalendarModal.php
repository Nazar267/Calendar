<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\EventForm as Form;
use RedooCalendar\Base\Form\Field;
use RedooCalendar\Base\Form\Validator\Mandatory;
use RedooCalendar\Base\Model\BaseModel;
use RedooCalendar\Base\View\BaseView;
use RedooCalendar\Base\VtUtils;
use RedooCalendar\Model\Calendar;
use RedooCalendar\Source\CalendarAccessMode;
use RedooCalendar\Source\DateTimeMode;

class RedooCalendar_EditCalendarModal_View extends BaseView
{
    public function process(Vtiger_Request $request)
    {

        $connection = Connection::GetInstance($request->get('connector'));

        switch ($connection->getConnectionProcessor()) {
            case 'custom':
                /** @var RedooCalendar\Model\GeneratedCalendar $calendar */
                $calendar = $connection->getConnector()->getCalendar($request->get('id'));
                
                $calendarConfig = json_decode(html_entity_decode($calendar->getConfig()), true);

                $form = new Form();
                $form->setFormId('generate-calendar-main-form');
                $form->setWidth(600);
                $tab = $form->addTab('General');

                $group = $tab->addGroup('Event Details');

                $instance = \Vtiger_Module_Model::getInstance($calendarConfig['module_id']);

                $fields = VtUtils::getFieldModelsWithBlocksForModule($instance->getName());

                $fieldList = [];
                foreach ($fields as $_group) {
                    foreach ($_group as $item) {
                        $fieldList[$item->name] = $item->label;
                    }
                }

                $group->addField()
                    ->setBindValue('id')
                    ->setName('id')
                    ->setValue($calendar->getId())
                    ->setType(Field::INPUT_HIDDEN);

                $group->addField()
                    ->setBindValue('module_id')
                    ->setName('module_id')
                    ->setValue($calendarConfig['module_id'])
                    ->setType(Field::INPUT_HIDDEN);

                $group->addField()
                    ->setBindValue('connector')
                    ->setName('connector')
                    ->setValue($connection->getId())
                    ->setType(Field::INPUT_HIDDEN);

                $group->addField()
                    ->setLabel('Title of Calendar')
                    ->setBindValue('title')
                    ->setName('title')
                    ->setValue($calendar->getTitle())
                    ->addValidator(new Mandatory())
                    ->setType(Field::INPUT_TEXT);

                $group->addField()
                    ->setLabel('Color')
                    ->setBindValue('color')
                    ->setName('color')
                    ->setValue($calendarConfig['color'])
                    ->addValidator(new Mandatory())
                    ->setOptions([
                        "#f0d0c9", "#e2a293", "#d4735e", "#65281a",
                        "#eddfda", "#dcc0b6", "#cba092", "#7b4b3a",
                        "#fcecd5", "#f9d9ab", "#f6c781", "#c87d0e",
                        "#e1dca5", "#d0c974", "#a29a36", "#514d1b",
                        "#c6d9f0", "#8db3e2", "#548dd4", "#17365d"
                    ])
                    ->setType(Field::INPUT_COLOR);


                $group = $tab->addGroup('Event Title');
                $group->setColumCount(2);

                $group->addField()
                    ->setLabel('Title of Event')
                    ->setBindValue('event_title')
                    ->setName('event_title')
                    ->setValue($calendarConfig['event_title'])
                    ->addValidator(new Mandatory())
                    ->setOptions([
                        'options' => $fieldList
                    ])
                    ->setType(Field::INPUT_PICKLIST);

                $group->addField()
                    ->setLabel('Prefix of Title')
                    ->setBindValue('title_prefix')
                    ->setName('title_prefix')
                    ->setValue($calendarConfig['title_prefix'])
                    ->setType(Field::INPUT_TEXT);

                $group->addField()
                    ->setLabel('Subtitle of Event')
                    ->setBindValue('event_subtitle')
                    ->setName('event_subtitle')
                    ->setValue($calendarConfig['event_subtitle'])
                    ->addValidator(new Mandatory())
                    ->setOptions([
                        'options' => $fieldList
                    ])
                    ->setType(Field::INPUT_PICKLIST);
                    
                $group->addField()
                ->setLabel('Access')
                ->setBindValue('access_mode')
                ->setValue($calendarConfig['access_mode'])
                ->setName('access_mode')
                ->addValidator(new Mandatory())
                ->setOptions([
                    'options' => CalendarAccessMode::getOptionsData()
                ])
                    ->setType(Field::INPUT_PICKLIST);

                $group->addField()
                    ->setLabel('Work with')
                    ->setBindValue('datetime_mode')
                    ->setName('datetime_mode')
                    ->setValue($calendarConfig['datetime_mode'])
                    ->addValidator(new Mandatory())
                    ->setOptions([
                        'options' => DateTimeMode::getOptionsData()
                    ])
                    ->enableFullwidth()
                    ->setType(Field::INPUT_PICKLIST);


                $group->addField()
                    ->setLabel('Date From')
                    ->setBindValue('date_from')
                    ->setName('date_from')
                    ->setValue($calendarConfig['date_from'])
                    ->addValidator(new Mandatory())
                    ->setOptions([
                        'options' => $fieldList
                    ])
                    ->setRelated([
                        'datetime_mode' => ['date', 'datetime'],
                    ])
                    ->setType(Field::INPUT_PICKLIST);

                $group->addField()
                    ->setLabel('Date To')
                    ->setBindValue('date_to')
                    ->setName('date_to')
                    ->setValue($calendarConfig['date_to'])
                    ->addValidator(new Mandatory())
                    ->setOptions([
                        'options' => $fieldList
                    ])
                    ->setRelated([
                        'datetime_mode' => ['date', 'datetime'],
                    ])
                    ->setType(Field::INPUT_PICKLIST);

                $group->addField()
                    ->setLabel('Time From')
                    ->setBindValue('time_from')
                    ->setValue($calendarConfig['time_from'])
                    ->setName('time_from')
                    ->addValidator(new Mandatory())
                    ->setOptions([
                        'options' => $fieldList
                    ])
                    ->setRelated([
                        'datetime_mode' => ['datetime'],
                    ])
                    ->setType(Field::INPUT_PICKLIST);

                $group->addField()
                    ->setLabel('Time To')
                    ->setBindValue('time_to')
                    ->setValue($calendarConfig['time_to'])
                    ->setName('time_to')
                    ->addValidator(new Mandatory())
                    ->setOptions([
                        'options' => $fieldList
                    ])
                    ->setRelated([
                        'datetime_mode' => ['datetime'],
                    ])
                    ->setType(Field::INPUT_PICKLIST);

                $group->addField()
                    ->setLabel('Timezone of Data')
                    ->setBindValue('timezone')
                    ->setValue($calendarConfig['timezone'])
                    ->setName('timezone')
                    ->addValidator(new Mandatory())
                    ->setOptions([
                        'options' => [
                            'currentuser' => 'current User timezone',
                            'UTC' => 'Default DB Timezone',
                        ]
                    ])
                    ->setRelated([
                        'datetime_mode' => ['datetime'],
                    ])
                    ->setType(Field::INPUT_PICKLIST);

                $viewer = $this->getViewer($request);

                $fieldConfig = json_decode(html_entity_decode($calendar->getFieldConfig()), true);
                $viewer->assign('fieldconfig', !empty($fieldConfig) ? $fieldConfig : []);


                $conditionComponent = $viewer->view('Form/GenerateCalendarConditionComponent.tpl', $request->getModule(), true);
                echo json_encode(
                    [
                        'form' => $form->getFrontendData(),
                        'condition_component' => $conditionComponent,
                        'readonly' => false
                    ]
                );
                return;

            case 'vtiger_event':

                $form = new Form();
                $form->setFormId('edit-calendar-form');
                $form->setWidth(600);
                $tab = $form->addTab('General');

                $group = $tab->addGroup('Event Details');

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
                echo json_encode([
                    'form' => $form->getFrontendData(),
                    'readonly' => false
                ]);
                return;

            case 'vtiger_task':
                $form = new Form();
                $form->setFormId('edit-calendar-form');
                $form->setWidth(600);
                $tab = $form->addTab('General');

                $group = $tab->addGroup('Event Details');

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
                echo json_encode([
                    'form' => $form->getFrontendData(),
                    'readonly' => false
                ]);
                return;

            default:
                /** @var Calendar $calendar */
                $calendar = $connection->getConnector()->getCalendar($request->get('id'));

                $form = new Form();
                $form->setFormId('edit-calendar-form');
                $form->setWidth(600);
                $tab = $form->addTab('General');
                $group = $tab->addGroup('General');

                $group->addField()
                    ->setBindValue('connector')
                    ->setName('connector')
                    ->setValue($connection->getId())
                    ->setType(Field::INPUT_HIDDEN);

                $group->addField()
                    ->setLabel('Title')
                    ->setBindValue('title')
                    ->setName('title')
                    ->setValue($calendar->getTitle())
                    ->addValidator(new Mandatory())
                    ->setType(Field::INPUT_TEXT);

                $group->addField()
                    ->setLabel('Color')
                    ->setBindValue('color')
                    ->setName('color')
                    ->setValue($calendar->getColor())
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
                    ->setValue($calendar->getAccessMode())
                    ->addValidator(new Mandatory())
                    ->setOptions([
                        'options' => CalendarAccessMode::getOptionsData()
                    ])
                    ->setType(Field::INPUT_PICKLIST);

                foreach (Connection::getAvailableConnections($this->getUser()) as $_connection) {
                    $_connection->getConnector()->getShareUserList($group, $calendar);
                }

                $group->addField()
                    ->setLabel('Hide Event Details')
                    ->setBindValue('hide_event_details')
                    ->setValue($calendar->getHideEventDetails())
                    ->setName('hide_event_details')
                    ->setRelated([
                        'access_mode' => ['share', 'public']
                    ])
                    ->setType(Field::INPUT_CHECKBOX);

                echo json_encode([
                    'form' => $form->getFrontendData(),
                    'readonly' => false
                ]);
                return;
        }
    }
}

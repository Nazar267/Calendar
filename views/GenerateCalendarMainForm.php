<?php


global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");


use RedooCalendar\Base\Connection\ConnectorPlugin\ConnectorPluginInterface;
use RedooCalendar\Base\View\BaseView;
use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\EventForm as Form;
use RedooCalendar\Base\Form\Field;
use RedooCalendar\Base\Form\Validator\Mandatory;
use RedooCalendar\Base\VTEntity;
use RedooCalendar\Base\VtUtils;
use RedooCalendar\Source\CalendarAccessMode;
use RedooCalendar\Source\ConnectionType;
use RedooCalendar\Source\DateTimeMode;

class RedooCalendar_GenerateCalendarMainForm_View extends BaseView
{
    public function process(Vtiger_Request $request)
    {
        $form = new Form();
        $form->setFormId('generate-calendar-main-form');
        $form->setWidth(600);
        $tab = $form->addTab('General');

        $group = $tab->addGroup('Event Details');

        $instance = \Vtiger_Module_Model::getInstance($request->get('module_id'));
        $fields = VtUtils::getFieldModelsWithBlocksForModule($instance->getName());

        $fieldList = [];
        foreach ($fields as $_group) {
            foreach ($_group as $item) {
                $fieldList[$item->name] = $item->label;
            }
        }


        $group->addField()
            ->setLabel('Title of Calendar')
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


        $group->addField()
            ->setLabel('Title of Event')
            ->setBindValue('event_title')
            ->setName('event_title')
            ->addValidator(new Mandatory())
            ->setOptions([
                'options' => $fieldList
            ])
            ->setType(Field::INPUT_PICKLIST);

        $group->addField()
            ->setLabel('Subtitle of Event')
            ->setBindValue('event_subtitle')
            ->setName('event_subtitle')
            ->addValidator(new Mandatory())
            ->setOptions([
                'options' => $fieldList
            ])
            ->setType(Field::INPUT_PICKLIST);

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

        $group->addField()
            ->setLabel('Work with')
            ->setBindValue('datetime_mode')
            ->setName('datetime_mode')
            ->addValidator(new Mandatory())
            ->setOptions([
                'options' => DateTimeMode::getOptionsData()
            ])
            ->setType(Field::INPUT_PICKLIST);

        $group->addField()
            ->setLabel('Date From')
            ->setBindValue('date_from')
            ->setName('date_from')
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
            ->setName('time_to')
            ->addValidator(new Mandatory())
            ->setOptions([
                'options' => $fieldList
            ])
            ->setRelated([
                'datetime_mode' => ['datetime'],
            ])
            ->setType(Field::INPUT_PICKLIST);
        $qualifiedModuleName = $request->getModule(false);
        $viewer = $this->getViewer($request);


        $viewer->assign('fieldconfig', !empty($_POST['fieldconfig']) ? $_POST['fieldconfig'] : []);

        $conditionComponent = $viewer->view('Form/GenerateCalendarConditionComponent.tpl', $request->getModule(), true);
        echo json_encode(
            [
                'form' => $form->getFrontendData(),
                'condition_component' => $conditionComponent
            ]
        );
        return;
    }
}

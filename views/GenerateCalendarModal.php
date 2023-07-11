<?php


global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");


use RedooCalendar\Base\Connection\ConnectorPlugin\ConnectorPluginInterface;
use RedooCalendar\Base\View\BaseView;
use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\EventForm as Form;
use RedooCalendar\Base\Form\Field;
use RedooCalendar\Base\Form\Validator\Mandatory;
use RedooCalendar\Base\VtUtils;
use RedooCalendar\Source\CalendarAccessMode;
use RedooCalendar\Source\ConnectionType;

class RedooCalendar_GenerateCalendarModal_View extends BaseView
{
    public function process(Vtiger_Request $request)
    {
        $form = new Form();
        $form->setFormId('generate-calendar-form');
        $form->setWidth(600);
        $tab = $form->addTab('General');
        $group = $tab->addGroup('General');

        $moduleList = VtUtils::getEntityModules(true);

        foreach ($moduleList as &$module) {
            $module = $module[1];
        }
        $moduleList[-1] = '';

        $group->addField()
            ->setLabel('Module')
            ->setBindValue('module_id')
            ->setName('module_id')
            ->setChangeHandler('selectModuleHandler')
            ->setOptions([
                'options' => $moduleList
            ])
            ->addValidator(new Mandatory())
            ->setType(Field::INPUT_PICKLIST)
            ->setValue(-1);


        echo json_encode(
            $form->getFrontendData()
        );
        return;
    }
}

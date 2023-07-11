<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\Form;
use RedooCalendar\Model\Subscribe\Collection as SubscribeCollection;
use RedooCalendar\Base\Form\Validator\Mandatory;
use RedooCalendar\Source\ConnectionType;

class RedooCalendar_CreateConnectionModal_View extends Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        $form = new Form();
        $form->setFormId('create-connection');
        $form->setWidth(600);
        $tab = $form->addTab('General');
        $group = $tab->addGroup('Calendar');

        $group->addField()
            ->setLabel('Connector')
            ->setBindValue('connector')
            ->setName('connector')
            ->addValidator(new Mandatory())
            ->setOptions([
                'options' => ConnectionType::getOptionsData()
            ])
            ->setType(Form\Field::INPUT_PICKLIST);

        $group->addField()
            ->setLabel('Title')
            ->setBindValue('title')
            ->setName('title')
            ->addValidator(new Mandatory())
            ->setType(Form\Field::INPUT_TEXT);

        echo json_encode(
            $form->getFrontendData()
        );
        return;
    }
}

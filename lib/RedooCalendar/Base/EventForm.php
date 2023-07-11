<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 16:52
 */

namespace RedooCalendar\Base;

use RedooCalendar\Base\Form\Field;
use RedooCalendar\Base\Form\Tab;

class EventForm extends Form
{

    public function getHTML()
    {
        $viewer = \Vtiger_Viewer::getInstance();

        $viewer->assign('TABS', $this->getTabs());
        $viewer->assign('FORMMODULE', 'RedooCalendar');
        $viewer->assign('formId', $this->formId);

        return $viewer->view('Form/EventFormContainer.tpl', 'RedooCalendar', true);
    }

}
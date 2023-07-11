<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

class RedooCalendar_List_View extends Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        $modules = \FlexSuite\VtUtils::getEntityModules(true);

        if(in_array(array('HausmeisterTasks','Flexx Service Management'), $modules))
        {
            $this->syncHausmeisterTasks();
        }

        $user = Users_Record_Model::getCurrentUserModel();

        $create_refresh_token = \FlexSuite\Database::fetchRows("SELECT create_date FROM vtiger_outlook_oauth WHERE userid = ?;", $user->{"id"});
        $datetime1 = date_create(date("Y-m-d"));
        $datetime2 = date_create($create_refresh_token[0]['create_date']);
        $interval = date_diff($datetime1, $datetime2);

        $date_diff = $interval->format('%d');

        $viewer = $this->getViewer($request);
        if($date_diff >= 0 && $create_refresh_token != null)
        {
            $viewer->assign('EXPIRED', true);
        }

        $viewer->view('Index.tpl', $request->getModule(false));
    }

    /**
     * @param \Vtiger_Request $request
     *
     * @return Vtiger_JsScript_Model[]
     */
    function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);

        $jsFileNames = array(
            "~modules/RedooCalendar/views/resources/js/FlexUtils.min.js",
            "~modules/RedooCalendar/views/resources/js/formhandler.js",
            "https://kendo.cdn.telerik.com/2019.1.115/js/kendo.all.min.js",
            "https://cdnjs.cloudflare.com/ajax/libs/validate.js/0.13.1/validate.min.js",
            "https://cdnjs.cloudflare.com/ajax/libs/jquery-contextmenu/2.7.1/jquery.contextMenu.min.js",
            "https://cdnjs.cloudflare.com/ajax/libs/jquery-contextmenu/2.7.1/jquery.ui.position.js",
            "~modules/RedooCalendar/views/resources/js/complexecondition2.js",
            "~modules/RedooCalendar/views/resources/public/build/app.js",
            "~modules/RedooCalendar/views/resources/public/build/germanlfix.js",
            "~modules/RedooCalendar/views/resources/public/build/markasholiday.js",
            "~modules/RedooCalendar/views/resources/public/build/checkcompletedtasks.js",
            "~modules/RedooCalendar/views/resources/public/build/stopmonthtextmovement.js",
            "~modules/RedooCalendar/views/resources/public/build/stopendlessloading.js",
            "~modules/RedooCalendar/views/resources/js/updateCalendar.js",
        );

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    /**
     * @param \Vtiger_Request $request
     *
     * @return \Vtiger_CssScript_Model[]
     */
    function getHeaderCss(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderCss($request);

        $cssFileNames = array(
            '~modules/RedooCalendar/views/resources/css/complexcondition.css',
            'https://kendo.cdn.telerik.com/2019.1.115/styles/kendo.common.min.css',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery-contextmenu/2.7.1/jquery.contextMenu.min.css',
            '~modules/RedooCalendar/views/resources/public/build/style.css',
            '~modules/RedooCalendar/views/resources/public/build/addition.css'
        );

        $cssScriptInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerStyleInstances = array_merge($headerScriptInstances, $cssScriptInstances);

        return $headerStyleInstances;
    }

    public function syncHausmeisterTasks()
    {
        $max_hausmeistertasks_id = \FlexSuite\Database::fetchRows("SELECT max(hausmeistertasksid) FROM vtiger_hausmeistertasks;");
        $hausmeistertasks = \Vtiger_Record_Model::getInstancesFromIds(range(0, $max_hausmeistertasks_id[0]["max(hausmeistertasksid)"]), 'HausmeisterTasks');

        $max_event_id = \FlexSuite\Database::fetchRows("SELECT max(activityid) FROM vtiger_activity;");
        $events = \Vtiger_Record_Model::getInstancesFromIds(range(0, $max_event_id[0]["max(activityid)"]), 'Events');

        foreach($events as $event)
        {
            $fields_event = $event->{"entity"}->{"column_fields"};

            $hausmeistertasksid = \FlexSuite\Database::fetchRows("SELECT hausmeistertasksid FROM vtiger_hausmeistertasks WHERE calendar_id = ?;", $fields_event["id"])[0]["hausmeistertasksid"];
            $deleted_entity = \FlexSuite\Database::fetchRows("SELECT deleted FROM vtiger_crmentity WHERE crmid = ?;", $hausmeistertasksid)[0]["deleted"];

            if($deleted_entity && $hausmeistertasksid)
            {
                $delete = \Vtiger_Record_Model::getInstanceById($fields_event["id"]);
                $delete->delete();
            }

        }

        foreach ($hausmeistertasks as $hausmeistertask)
        {
            $fields_hausmeistertask = $hausmeistertask->{"entity"}->{"column_fields"};

            $calendar_id = \FlexSuite\Database::fetchRows("SELECT calendar_id FROM vtiger_hausmeistertasks WHERE hausmeistertasksid = ?;", $fields_hausmeistertask["id"])[0]["calendar_id"];
            $deleted_entity = \FlexSuite\Database::fetchRows("SELECT deleted FROM vtiger_crmentity WHERE crmid = ?;", $calendar_id)[0]["deleted"];

            if($calendar_id && $deleted_entity)
            {
                $delete = \Vtiger_Record_Model::getInstanceById($fields_hausmeistertask["id"]);
                $delete->delete();
            }
            elseif($calendar_id && !$deleted_entity)
            {
                $this->updateEventByHausmeisterTask($fields_hausmeistertask, $calendar_id);
            }
            elseif(!$calendar_id)
            {
                $this->createEventByHausmeisterTask($fields_hausmeistertask);
            }
        }
    }

    public function createEventByHausmeisterTask($fields)
    {
        $add = \Vtiger_Record_Model::getCleanInstance('Calendar');

        $add->set('subject', $fields["hausmeistertasks_title"]);
        $add->set('description', $fields["description"]);
        $add->set('date_start', $fields["start_date"]);
        $add->set('time_start', $fields["start_working_time"]);
        $add->set('due_date', $fields["end_date"]);
        $add->set('time_end', $fields["end_working_time"]);
        $add->set('visibility', 'Private');

        $add->save();
        $calendar_id = $add->get('id');
        \FlexSuite\Database::fetchRows("UPDATE vtiger_hausmeistertasks SET calendar_id = ? WHERE hausmeistertasksid = ?;", $calendar_id, $fields["id"]);

        return true;
    }

    public function updateEventByHausmeisterTask($entities_array, $id)
    {
        $activity_redoo = \FlexSuite\Database::fetchRows("SELECT * FROM vtiger_activity INNER JOIN vtiger_crmentity ON vtiger_activity.activityid = vtiger_crmentity.crmid WHERE vtiger_activity.activityid = ?;", $id);

        if ($activity_redoo[0]["subject"] != $entities_array["hausmeistertasks_title"]) {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_activity SET subject = ? WHERE activityid = ?;", $entities_array["hausmeistertasks_title"], $id);
        }
        if ($activity_redoo[0]["description"] != $entities_array["description"]) {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_crmentity SET description = ? WHERE crmid = ?;", $entities_array["description"], $id);
        }
        if ($activity_redoo[0]["date_start"] != $entities_array["start_date"]) {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_activity SET date_start = ? WHERE activityid = ?;", $entities_array["start_date"], $id);
        }
        if ($activity_redoo[0]["time_start"] != $entities_array["start_working_time"]) {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_activity SET time_start = ? WHERE activityid = ?;", $entities_array["start_working_time"], $id);
        }
        if ($activity_redoo[0]["due_date"] != $entities_array["end_date"]) {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_activity SET due_date = ? WHERE activityid = ?;", $entities_array["end_date"], $id);
        }
        if ($activity_redoo[0]["time_end"] != $entities_array["end_working_time"]) {
            \FlexSuite\Database::fetchRows("UPDATE vtiger_activity SET time_end = ? WHERE activityid = ?;", $entities_array["end_working_time"], $id);
        }

        return true;
    }

}

<?php

class RedooCalendar_DeleteGeneratedCalendars_Action extends Vtiger_Action_Controller
{
    public function process(Vtiger_Request $request)
    {
        $adb = \PearDatabase::getInstance();
        $user_id = Users_Record_Model::getCurrentUserModel()->getId();

        $adb->pquery('DELETE FROM `vtiger_redoocalendar_generated_calendar` WHERE `user_id`=?', [$user_id]);
        header('Location: index.php?module=RedooCalendar&view=List');
    }
}
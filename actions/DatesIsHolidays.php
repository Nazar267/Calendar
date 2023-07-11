<?php

global $root_directory;
require_once($root_directory . "/modules/RedooCalendar/autoload_wf.php");

use RedooCalendar\Base\Database;
use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\ActionController\BaseActionController;
use RedooCalendar\Model\Subscribe;

class RedooCalendar_DatesIsHolidays_Action extends BaseActionController
{
    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $dates = $request->get("dates");
        
        $holiday_dates = $this->getHolidaydates($dates);

        $matched_holiday_dates = $this->getMatchedHolidaydates($dates, $holiday_dates);

        echo json_encode($matched_holiday_dates);
    }

    /*
        THis function gets year of events & country of user, makes request, and returns all holidays related to year and country provided
        Page of API: to https://date.nager.at/Api
    */
    protected function getHolidaydates($dates)
    {
        //initializing url
        $url = "https://date.nager.at/api/v3/publicholidays/";
        $year = "";
        $country = "";

        $currentUser = \Users_Record_Model::getCurrentUserModel();

        $country = strtoupper(explode('_', $currentUser->getData()['language'])[1]);
        $year = date('Y', strtotime($dates[0]));

        $url .= $year."/".$country;

        //making request by formed url 
        $curl = curl_init($url);

        curl_setopt($curl,CURLOPT_HEADER,0);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1); //we don`t want to see output, but store in variable

        $result = json_decode(curl_exec($curl));
        curl_close($curl);

        return $result;
    }

    /*
        This function compares current&holiday dates, and returns intersection of this dates
    */
    protected function getMatchedHolidaydates($all_dates, $holidays)
    {
        $result = [];

        //comparing each date with all holidays
        foreach($all_dates as $current_date)
        {
            //comparing with each holiday
            foreach($holidays as $current_holiday)
            {
                //comparing only month&date because of some holidays is in the borders of years(like new year)
                if(date('m-d', strtotime($current_holiday->date)) == date('m-d', strtotime($current_date)))
                {
                    //to avoid duplicates of dates
                    if(!in_array($current_date, $result))
                    {
                        $result[] = $current_date;
                    }

                    break;
                }
            }

        }

        return $result;
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateReadAccess();
    }

}

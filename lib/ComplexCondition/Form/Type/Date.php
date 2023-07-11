<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */
namespace ComplexCondition\Form\Type;

use ComplexCondition\Form\Type;

class Date extends Text
{
    public function render($fieldName) {
        $html = '<input type="text" '.($this->readonly ? 'readonly="readonly"' : '').' class="EventEditorInputField" name="'.$fieldName.'" data-field="'.$this->Field->getName().'" autocomplete="off" value="" /><i class="fa fa-times datePickerClearBtn" aria-hidden="true"></i>';

        return $html;
    }

    public function getGetterFunction() {
        return "return INPUT.find('.EventEditorInputField').val() == '' ? '' : INPUT.find('.EventEditorInputField').data('daterangepicker').startDate.format(\"YYYY-MM-DD\");";
    }

    public function getSetterFunction() {
        return "
        if(VALUE == '' || VALUE == '0000-00-00') return;
            INPUT.find('.EventEditorInputField').data('daterangepicker').setStartDate(new Date(VALUE));
            INPUT.find('.EventEditorInputField').data('daterangepicker').setEndDate(new Date(VALUE)); 
            INPUT.find('.EventEditorInputField').val(INPUT.find('.EventEditorInputField').data('daterangepicker').startDate.format(app.getDateFormat().toUpperCase()));
            INPUT.find('.EventEditorInputField').trigger('blur');";
    }

    public function getInitFunction() {
        return 'return; if(INPUT.find(".EventEditorInputField").prop("readonly") == true) { return; }
        INPUT.find(".datePickerClearBtn").on("click", function() {
            INPUT.find(".EventEditorInputField").val("").trigger("blur");
        });
        console.log(INPUT.find(".EventEditorInputField")); INPUT.find(".EventEditorInputField").daterangepicker({
           autoUpdateInput: false, 
           singleDatePicker: true,
           showDropdowns: true,      
        ranges: {
           \'Today\': [moment(), moment()],
           \'Yesterday\': [moment().subtract(1, \'days\'), moment().subtract(1, \'days\')],
           \'vor 7 Tagen\': [moment().subtract(6, \'days\'), moment().subtract(6, \'days\')],
           \'vor 30 Tagen\': [moment().subtract(29, \'days\'), moment().subtract(29, \'days\')],
        },
       
            "locale": {
                "format": "DD.MM.YYYY",
                "separator": " - ",
                "applyLabel": "setzen",
                "cancelLabel": "löschen",
                "fromLabel": "Von",
                "toLabel": "Bis",
                "customRangeLabel": "eigenes Datum",
                "weekLabel": "W",
                "daysOfWeek": [
                    "So","Mo","Di","Mi","Do","Fr","Sa"
                ],
                "monthNames": [
                   "Januar","Februar","März","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember"
                ],
                "firstDay": 1
            },           
        }, function(start, end, label) {
            INPUT.find(\'.EventEditorInputField\').val(start.format(app.getDateFormat().toUpperCase()));
            INPUT.trigger("blur");
          });'; //.on("changeDate", function(e,a,b,c) { console.log($(this).datepicker("getDate")); })
    }

}

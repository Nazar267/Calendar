<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */
namespace ComplexCondition\Form\Type;

use ComplexCondition\Form\Type;

class Reference extends Type
{

    public function render($fieldName) {
        $options = $this->Field->getOptions();
        $html = '<div class="childcomponent used referenceComponent">
              <span rel="RecordLabel" class="RecordLabel" data-placeholder="Keinen Eintrag gewählt">Keinen Eintrag gewählt</span>
              <input name="'.$fieldName.'" data-field="'.$this->Field->getName().'" rel="RecordId" type="hidden" value="" class="sourceField used">
              <button type="button" class="btn btn-default ChooseRecordBtn" data-module="'.$options['related'][0].'">Eintrag wählen</button>
              <button type="button" class="btn btn-default ClearRecordBtn">Clear</button>
        </div>';
        //$html = '<input type="text" '.($this->readonly ? 'readonly="readonly"' : '').' class="EventEditorInputField" name="'.$fieldName.'" data-field="'.$this->Field->getName().'" autocomplete="off" value="" />';

        return $html;
    }

    public function getGetterFunction() {
        return "return INPUT.find('[rel=\"RecordId\"]').val();";
    }

    public function getSetterFunction() {
        return "
        INPUT.find('[rel=\"RecordId\"]').val(VALUE).trigger('blur');
        FlexAjax('ComplexCondition').postAction('RecordLabel', { ids:[VALUE] }, 'json').then(function(response) { INPUT.find('[rel=\"RecordLabel\"]').html(response.result[VALUE]); });
        ";
    }

    public function getInitFunction() {
        return '
        jQuery(\'.ClearRecordBtn\', INPUT).on(\'click\', function(e) {
            var target = jQuery(e.currentTarget);

            target.parent().find(\'[rel="RecordLabel"]\').html(target.parent().find(\'[rel="RecordLabel"]\').data(\'placeholder\'));
            target.parent().find(\'[rel="RecordId"]\').val(\'\');
        });
        jQuery(\'.ChooseRecordBtn\', INPUT).on(\'click\', function(e) {
            var target = jQuery(e.currentTarget);
            var moduleName = target.data(\'module\');
            if(moduleName.substr(0, 1) == \'#\') {
                moduleName = jQuery(moduleName).val();
            }

            var params = {
                \'module\' : moduleName
            };

            var popupInstance = Vtiger_Popup_Js.getInstance();
            popupInstance.show(params,function(data){
                var responseData = JSON.parse(data);

                for(var id in responseData){
                    target.parent().find(\'[rel="RecordLabel"]\').html(responseData[id].name);
                    target.parent().find(\'[rel="RecordId"]\').val(id);
                }
            });
        });        
        ';
    }

}

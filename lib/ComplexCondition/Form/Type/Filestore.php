<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */
namespace ComplexCondition\Form\Type;

use ComplexCondition\Form\Type;

class Filestore extends Type
{

    public function render($fieldName) {
        $html = '<div class="AttachmentUploaderContainer"><button type="button" class="btn btn-default AddAttachmentBtn">'.vtranslate('Add Attachment', 'ComplexCondition').'</button>';
        $html .= '<div class="AttachmentContainer"><div class="AttachmentList"></div><div class="UploadTMPContainer"></div></div></div>';

//        $html = '<input type="text" '.($this->readonly ? 'readonly="readonly"' : '').' class="EventEditorInputField" name="'.$fieldName.'" data-field="'.$this->Field->getName().'" autocomplete="off" value="" />';

        return $html;
    }

    public function getGetterFunction() {
        return "var attachments = INPUT.find('.AttachmentContainer').data('attachments');
        var returns = [];

        if(attachments !== undefined && attachments !== null) {
        $.each(attachments, function(index, data) {
            returns.push(data.getFrontendConfig());
        });
        }
        
        console.log(returns);
        return returns;
        ";
    }

    public function getSetterFunction() {
        return "INPUT.find('.EventEditorInputField').val(VALUE).trigger('blur');";
    }

    public function getInitFunction() {
        return '';
    }

}

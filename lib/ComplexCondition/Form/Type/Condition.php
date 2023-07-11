<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 18:40
 */
namespace ComplexCondition\Form\Type;

use ComplexCondition\Form\Type;

class Condition extends Type
{
    public function render($fieldName) {
        $html = '<textarea name="'.$fieldName.'" '.($this->readonly ? 'readonly="readonly"' : '').' data-field="'.$this->Field->getName().'" class="EventEditorInputField used" style="width:100%;"></textarea>';
        $html .= '<style type="text/css">div.FormGenTab div.FormGenField > .cke { width:100%; } div.FormGenField > .cke { width:100%; } div.FormGenTab.FullSize div.FormGenField > .cke { border:none; } div.FormGenField > .cke { width:100%; } </style>';
        return $html;
    }

    public function getGetterFunction() {
        return "
        for(var instanceName in CKEDITOR.instances){
            CKEDITOR.instances[instanceName].updateElement();
        }
        return INPUT.find('.EventEditorInputField').val();";
    }

    public function getSetterFunction() {
        return "INPUT.find('.EventEditorInputField').val(VALUE).trigger('blur');";
    }

    public function getInitFunction() {
        return "
        CKEDITOR.addCss( 'body { margin:8px; } p { margin:0;}' );

        INPUT.find('.EventEditorInputField').ckeditor({
            skin: 'moono-lisa',
            toolbar: [
                { name: 'document', items: [ 'Source' ] },
                { name: 'clipboard', items: [ 'Undo', 'Redo' ] },

                { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'RemoveFormat' ] },
                { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
                { name: 'links', items: [ 'Link', 'Unlink' ] },
                { name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule' ] },
                { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
                { name: 'about', items: [ 'About' ] }
            ]
        });

        ";
    }
}

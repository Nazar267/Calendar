/**
 * FormHandler Setter component
 *
 * Version 0.1
 *
 * Changelog
 *  0.1 - First implementation as component
 *
 * Usage
 */
(function($) {
 'use strict';  var CSS = '.FormGenTab{background-color:#fff;padding:10px}.FormGenTab.FullSize{padding:0}.FormGenEditorTabs{padding:0;list-style-type:none;overflow:hidden;border-bottom:1px solid #ccc;box-shadow:inset 1px 1px 3px #777;background-color:#eee;margin-bottom:0}.FormGenEditorTabs li{list-style-type:none;line-height:25px;margin:0;float:left;font-weight:700;padding:5px 15px 5px 15px;border:1px solid transparent;border-bottom:none;cursor:pointer;font-size:14px}.FormGenEditorTabs li.ActiveFormTab{background-color:#ccc;border:1px solid #ccc;border-bottom:none!important;box-shadow:1px 1px 1px #777}.ActiveFormGenTab{display:block!important}.FormGenFields{display:flex;flex-wrap:wrap}.FormGenFields .FormGenField{display:flex;box-sizing:border-box}.FormGenFields .FormGenField .group{padding-right:5px;width:100%}.FormGenFields .FormGenFullwidthField{width:100%!important;flex-grow:0;flex-shrink:0}.FormGenFields.Columns_1 .FormGenField{width:100%;flex-grow:0;flex-shrink:0}.FormGenFields.Columns_2 .FormGenField{padding-right:10px}.FormGenFields.Columns_2 .FormGenField:nth-child(2n+2){padding-right:0}.FormGenFields.Columns_2 .FormGenField{width:50%;flex-grow:0;flex-shrink:0}.FormGenFields.Columns_3 .FormGenField{width:33%;flex-grow:0;flex-shrink:0}.FormGenFields.Columns_3 .FormGenField{padding-right:10px}.FormGenFields.Columns_3 .FormGenField:nth-child(3n+3){padding-right:0}.FormGenFields.Columns_4 .FormGenField{width:25%;flex-grow:0;flex-shrink:0}.FormGenFields.Columns_4 .FormGenField{padding-right:10px}.FormGenFields.Columns_4 .FormGenField:nth-child(4n+4){padding-right:0}.materialstyle.group{position:relative;margin-bottom:5px;margin-top:20px}.materialstyle.prefix-icon .fa{position:absolute;top:8px;font-size:14px}.materialstyle.prefix-icon label{left:19px}.materialstyle.prefix-icon input{padding-left:20px}.materialstyle.no-padding{padding-left:0;padding-right:0!important;margin:0}.materialstyle.no-padding .cke_reset{border:none!important}.materialstyle input:read-only{color:#aaa}.materialstyle input{font-size:16px;padding:10px 10px 10px 0;display:block;width:100%;border:none;height:30px;border-bottom:1px solid #757575;box-sizing:border-box}.materialstyle select{font-size:18px;padding:5px 10px 5px 0;display:block;width:100%;border:none;height:38px;border-bottom:1px solid #757575}.materialstyle textarea{font-size:14px;padding:3px 5px;display:block;width:100%;height:150px;border:1px solid #757575}.materialstyle .select2-container{width:100%}.materialstyle .select2-container .select2-choice,.materialstyle .select2-container .select2-choices,.materialstyle .select2-container .select2-choices .select2-search-field input{-webkit-box-shadow:none;box-shadow:none}.materialstyle input:focus,.materialstyle select:focus,.materialstyle textarea:focus{outline:0}.materialstyle.type-checkbox{display:flex;flex-direction:row}.materialstyle.type-checkbox .bar{display:none}.materialstyle.type-checkbox label{flex-grow:1;position:relative;color:#6b6b6b}.materialstyle .errorMsg{color:#790000}.materialstyle label{color:#bbb;font-size:14px;font-weight:400;position:absolute;pointer-events:none;left:5px;top:5px;transition:.2s ease all;-moz-transition:.2s ease all;-webkit-transition:.2s ease all}.materialstyle .childcomponent.used~label,.materialstyle input.used~label,.materialstyle input:focus~label,.materialstyle select.used~label,.materialstyle select:focus~label,.materialstyle textarea.used~label,.materialstyle textarea:focus~label{top:-18px;left:0;font-size:12px;color:#5264ae}.materialstyle .bar{position:relative;display:block;width:100%}.materialstyle .bar:after,.materialstyle .bar:before{content:"";height:2px;width:0;bottom:1px;position:absolute;background:#5264ae;transition:.2s ease all;-moz-transition:.2s ease all;-webkit-transition:.2s ease all}.materialstyle .bar:before{left:50%}.materialstyle .bar:after{right:50%}.materialstyle input:focus~.bar:after,.materialstyle input:focus~.bar:before{width:50%}.materialstyle.has-helptext .datePickerClearBtn{right:35px}.materialstyle .datePickerClearBtn{position:absolute;top:10px;right:10px;color:#777}.FormGenField.validate-error .EventEditorInputField{border-bottom-color:#790000;color:#790000}.CC_helpText{position:absolute;right:10px;top:5px;font-size:18px;color:#bbbaba}.tippy-popper[x-placement^=top] .tippy-tooltip.flexsuite-theme .tippy-arrow{border-top:8px solid #505355;border-right:8px solid transparent;border-left:8px solid transparent}.tippy-popper[x-placement^=bottom] .tippy-tooltip.flexsuite-theme .tippy-arrow{border-bottom:8px solid #505355;border-right:8px solid transparent;border-left:8px solid transparent}.tippy-popper[x-placement^=left] .tippy-tooltip.flexsuite-theme .tippy-arrow{border-left:8px solid #505355;border-top:8px solid transparent;border-bottom:8px solid transparent}.tippy-popper[x-placement^=right] .tippy-tooltip.flexsuite-theme .tippy-arrow{border-right:8px solid #505355;border-top:8px solid transparent;border-bottom:8px solid transparent}.tippy-tooltip.flexsuite-theme{background-color:#505355;padding:.25rem .4375rem;font-size:.8125rem;font-weight:600}.tippy-tooltip.flexsuite-theme .tippy-content{font-size:13px;padding:6px}.tippy-tooltip.flexsuite-theme .tippy-backdrop{background-color:#505355}.tippy-tooltip.flexsuite-theme .tippy-roundarrow{fill:#505355}.tippy-tooltip.flexsuite-theme[data-animatefill]{background-color:transparent}.tippy-tooltip.flexsuite-theme[data-size=small]{font-size:.75rem;padding:.1875rem .375rem}.tippy-tooltip.flexsuite-theme[data-size=large]{font-size:1rem;padding:.375rem .75rem}.referenceComponent{display:flex;border:1px solid #ccc;width:100%;padding:2px;line-height:30px}.referenceComponent .RecordLabel{flex-grow:1}';
    window.FORMGeneratorInstance = function() {
        this.storage = {
            'setter': {},
            'oninit': {},
            'config': {},
            'getter': {}
        };
        this.container = null;
        this.validators = {};

        this.setContainer = function(container) {
            this.container = $(container);
        };

        this.registerGetter = function(name, callback) {
            this.storage['getter'][name] = callback;
        };

        this.registerInit = function(name, callback) {
            if($('.FORMHANDLERSTYLES').length === 0) {
                $('body').append('<style type="text/css" class="FORMHANDLERSTYLES">' + CSS + '</style>');
            }

            this.storage['oninit'][name] = callback;
        };

        this.setValidators = function(validators) {
            this.validators = validators;
        };

        this.isValid = function() {
            var data = this.getValues();

            var validateResult = validate(
                data,
                this.validators,
                {
                    fullMessages: false
                }
            );

            this.container.find('.errorMsg').hide();
            this.container.find('.validate-error').removeClass('validate-error');

            if(typeof validateResult === 'undefined') {
                return true;
            } else {
                jQuery.each(validateResult, $.proxy(function(fieldName, errors) {
                    this.getInput(fieldName).addClass('validate-error');
                    this.getInput(fieldName).find('.errorMsg').html('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>&nbsp;' + errors.join('<br/><i class="fa fa-exclamation-triangle" aria-hidden="true"></i>&nbsp;')).show();
                }, this));

                return false;
            }
        };

        this.registerSetter = function(name, callback) {
            this.storage['setter'][name] = callback;
        };

        this.init = function(values) {

            $.each(this.storage['oninit'], $.proxy(function(index, ele) {
                var INPUT = $('.FormGenField[data-fieldname="' + index + '"]', this.container);

                ele(INPUT);
            }, this));

            $('.FormGenTabBtn ', this.container).on('click', $.proxy(function($event) {
                var targetEle = $($event.currentTarget);
                $(targetEle).closest('.FormGenContainer').find('li.ActiveFormTab').removeClass('ActiveFormTab');
                targetEle.addClass('ActiveFormTab');

                var target = targetEle.data('target');

                $(targetEle).closest('.FormGenContainer').find('.FormGenTab.ActiveFormGenTab').removeClass('ActiveFormGenTab');
                $(targetEle).closest('.FormGenContainer').find('.FormGenTab#' + target).addClass('ActiveFormGenTab');

                if ($('.FormGenTabBtn', this.container).length > 0) {
                    var tabs = $('.FormGenTabBtn', this.container);

                    if ($('.ActiveFormTab', tabs).length == 0) {
                        tabs.find('.FormGenTabBtn ').first().trigger('click');
                    }
                }
            }, this));

            $('.materialstyle input, .materialstyle select, .materialstyle textarea', this.container).off('blur').on('blur', $.proxy(function($event) {
                // check if the input has any value (if we've typed into it)
                var target = $($event.currentTarget);

                if ($(target).val()) {
                    $(target).addClass('used');
                } else {
                    $(target).removeClass('used');
                }

            }, this));

            if(typeof values !== 'undefined') {
                $.each(values, $.proxy(function (field, value) {
                    this.setValue(field, value);
                }, this));
            }

            if(typeof tippy !== 'undefined') {
                tippy('.CC_helpText', { zIndex: 99999, theme: 'flexsuite', maxWidth: 500 });
            }
        };

        this.setValue = function(name, value) {
            if(typeof this.storage['setter'][name] !== 'undefined') {
                var INPUT = $('.FormGenField[data-fieldname="' + name + '"]', this.container);

                this.storage['setter'][name](INPUT, value);
            }
        };

        this.getInput = function(fieldName) {
            return $('.FormGenField[data-fieldname="' + fieldName + '"]', this.container);
        };

        this.getValues = function() {
            var data = {};
            $.each(this.storage.getter, $.proxy(function(name, ele) {
                var INPUT = $('.FormGenField[data-fieldname="' + name + '"]', this.container);

                data[name] = ele(INPUT)
            }, this));

            return data;
        };
    };

    window.FORMGenerator = {
        instance: null,
        start: function() {
            FORMGenerator.instance = new FORMGeneratorInstance();

            return FORMGenerator.instance;
        },

        registerGetter: function(name, callback) {
            FORMGenerator.instance.registerGetter(name, callback);
        },

        registerInit: function(name, callback) {
            FORMGenerator.instance.registerInit(name, callback);
        },

        registerSetter: function(name, callback) {
            FORMGenerator.instance.registerSetter(name, callback);
        },

        init: function(values) {

            FORMGenerator.instance.init(values);
        },

        setValue:function(name, value) {
            FORMGenerator.instance.setValue(name, value);
        }
    };

    window.EditorHandler = {
        storage: {
            'setter': {},
            'oninit': {},
            'config': {}
        },
        _getInput:function(name) {
            return $('.EventEditorInputField[data-field="' + name + '"]');
        },
        setter:function(name, callback) {
            window.EditorHandler['storage']['setter'][name] = callback;
        },
        config:function(name, config) {
            if(typeof config === 'undefined' && typeof window.EditorHandler['storage']['config'][name] != 'undefined') {
                return window.EditorHandler['storage']['config'][name];
            }

            window.EditorHandler['storage']['config'][name] = config;
        },
        oninit:function(name, callback) {
            window.EditorHandler['storage']['oninit'][name] = callback;
        },
        clear:function() {
            EditorHandler.storage = {
                'setter': {},
                'oninit': {},
                'config': {}
            };
        },
        init:function() {
            $.each(EditorHandler.storage.oninit, function(index, ele) {
                ele(index);
            });
        },
        setValue:function(name, value) {
            if(typeof window.EditorHandler['storage']['setter'][name] != 'undefined') {
                window.EditorHandler['storage']['setter'][name](name, value);
            }
        },
        setText:function(name, value) {
            var input = EditorHandler._getInput(name);

            input.val(value).trigger('blur');
        },
        setSelect2:function(name, value) {
            var input = EditorHandler._getInput(name);

            input.select2('val', value).trigger('blur');
        },
        setDate:function(name, value) {
            var input = EditorHandler._getInput(name);

            input.data('mydatepicker').setDate(new Date(value));
            input.val(input.data('mydatepicker').getFormatedDate()).trigger('blur');
        },
        initRecordlist:function(name) {
            var input = EditorHandler._getInput(name);
            var config = EditorHandler.config(name);

            input.select2({
                placeholder: "Enter text to search Records",
                width:'100%',
                minimumInputLength: 1,
                multiple:true,
                separator: ";#;",
                initSelection: function (element, callback) {
                    var parts = jQuery(element).val().split(',');
                    var data = [];

                    jQuery.each(parts, function(index, id) {
                        data.push({
                            id: id,
                            text: productCache[id]['label']
                        });
                    });

                    callback(data);
                },
                query: function (query) {
                    var data = {
                        query: query.term,
                        page: query.page,
                        pageLimit: 25,
                        fieldtype:config.fieldtype
                    };

                    jQuery.post("index.php?module=RedooMessaging&action=RecordList", data, function (results) {
                        query.callback(results);
                    }, 'json');

                }
            });
        },
        enableCKEditor:function(name) {
            var input = EditorHandler._getInput(name);

            CKEDITOR.addCss( 'body { margin:8px; } p { margin:0;}' );

            input.ckeditor({
                // basePath: 'modules/RedooMessaging/views/resources/ckeditor-4.7.3/',
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
        },
        enableTimepicker:function(name) {
            var input = EditorHandler._getInput(name);

            var Timepicker = input.timepicker({
                className:'RedooCalendarTimepicker',
                timeFormat: CurrentUserHourFormat == '12' ? 'g:i A' : 'G:i',
                step:15,
                relationField: typeof window.EditorHandler['storage']['config'][name]['relation'] != '' ? window.EditorHandler['storage']['config'][name]['relation'] : '',
                showDuration: false,
                lang: {
                    'am': 'am',
                    'pm': 'pm',
                    'AM': 'AM',
                    'PM': 'PM',
                    'decimal': '.',
                    'mins': 'mins',
                    'hr': 'hr',
                    'hrs': 'hrs'
                },
                durationTime:function() {
                    if(this.relationField != '') {
                        var value = EditorHandler._getInput(this.relationField).val();

                        if(value == '') return false;

                        return value;
                    }

                    return false;
                }
            });

            if(typeof window.EditorHandler['storage']['config'][name]['relation'] != '') {
                Timepicker.on('changeTime', function(e) {
                    var field = $(this).data('field');
                    var relationField = window.EditorHandler['storage']['config'][field]['relation'];

                    var input = EditorHandler._getInput(relationField);

                    if(input.val() == '') {
                        input.val($(this).val()).trigger('blur');
                    }
                    input.timepicker('option', 'showDuration', true);
                    // console.log(this);
                });
            }
        },
        enableDatepicker:function(name) {
            return;
            var input = EditorHandler._getInput(name);

            myCalendar = new dhtmlXCalendarObject([input[0]]);
            myCalendar.hideTime();
            myCalendar.setDateFormat(CurrentUserDatePickerFormat);

            input.data('mydatepicker', myCalendar);
        }
    };

})(jQuery);
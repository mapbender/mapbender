/**
 * Form helper plugin for jQuery
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 02.02.2015 by WhereGroup GmbH & Co. KG
 *
 */
$.fn.formData = function(values) {
    var form = $(this);
    var inputs = $(':input', form).get();
    var hasNewValues = typeof values == 'object';

    if (hasNewValues) {
        $.each(inputs, function() {
            var input = $(this);
            var value = values[this.name];

            if (values.hasOwnProperty(this.name)) {
                switch (this.type) {
                    case 'checkbox':
                        input.prop('checked', value !== null && value);
                        break;
                    case 'radio':
                        if (value === null) {
                            input.prop('checked', false);
                        } else if (input.val() == value) {
                            input.prop("checked", true);
                        }
                        break;
                    default:
                        input.val(value);
                }
            }
        });
        return form;
    } else {
        values = {};
        $.each(inputs, function() {
            var input = $(this);
            var value;
            var declaration = input.data('declaration');

            if(this.name == ""){
                return;
            }

            switch (this.type) {
                case 'checkbox':
                case 'radio':
                    if(values.hasOwnProperty(this.name) && values[this.name] != null){
                       return;
                    }
                    value = input.is(':checked') ? input.val() : null;
                    break;
                default:
                    value = input.val();
            }

            if(declaration){
                console.log(declaration);
                if(declaration.hasOwnProperty('mandatory') && declaration.mandatory ){
                    input.data('warn')();
                }
                values[this.name] = value;
            }else{
                values[this.name] = value;
            }

        });
        return values;
    }
};

$.fn.disableForm = function() {
    var form = this;
    var inputs = $(" :input", form);
    form.attr('readonly', true);
    form.css('cursor', 'wait');
    $.each(inputs, function(idx, el) {
        var $el = $(el);
        if($el.is(':checkbox') || $el.is(':radio') || $el.is('select')) {
            $el.attr('disabled', 'disabled');
        } else {
            $el.attr('readonly', 'true');
        }
    })
};

$.fn.enableForm = function() {
    var form = this;
    var inputs = $(" :input", form);
    form.css('cursor', '');
    $.each(inputs, function(idx, el) {
        var $el = $(el);
        if($el.is(':checkbox') || $el.is(':radio') || $el.is('select')) {
            $el.removeAttr('disabled', 'disabled');
        } else {
            $el.removeAttr('readonly', 'true');
        }
    })
};


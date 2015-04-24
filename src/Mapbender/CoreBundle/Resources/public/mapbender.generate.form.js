/**
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 08.04.2015 by WhereGroup GmbH & Co. KG
 */
/**
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 05.04.15 by WhereGroup GmbH & Co. KG
 */
(function($) {

    /**
     * Check if object has a key
     *
     * @param obj
     * @param key
     * @returns {boolean}
     */
    function has(obj, key) {
        return typeof obj[key] !== 'undefined';
    }

    /**
     * Add jquery events to element by declration
     *
     * @param element
     * @param declaration
     */
    function addEvents(element, declaration) {
        $.each(declaration, function(k, value) {
            if(typeof value == 'function') {
                element.on(k, value);
            }
        });
    }

    $.widget('mapbender.generateElements', {
        options:      {},
        declarations: {
            popup: function(item, declarations, widget) {
                var popup = $("<div/>");
                if(has(item, 'children')) {
                    $.each(item.children, function(k, item) {
                        popup.append(widget.genElement(item));
                    });
                }
                window.setTimeout(function() {
                    popup.popupDialog(item)
                }, 1);

                return popup;
            },
            form: function(item, declarations, widget) {
                var form = $('<form/>');
                if(has(item, 'children')) {
                    $.each(item.children, function(k, item) {
                        form.append(widget.genElement(item));
                    })
                }
                return form;
            },
            fluidContainer: function(item, declarations, widget) {
                var container = $('<div class="container-fluid"/>');
                var hbox = $('<div class="row"/>');
                if(has(item, 'children')) {
                    $.each(item.children, function(k, item) {
                        hbox.append(widget.genElement(item));
                    })
                }
                container.append(hbox);
                return container;
            },
            inline: function(item, declarations, widget) {
                var container = $('<div class="form-inline"/>');
                if(has(item, 'children')) {
                    $.each(item.children, function(k, item) {
                        container.append(widget.genElement(item));
                    })
                }
                return container;
            },
            html:      function(item, declarations) {
                var container = $('<div class="html-element-container"/>');
                if (typeof item === 'string'){
                    container.html(item);
                }else if(has(item,'html')){
                    container.html(item.html);
                }else{
                    container.html(JSON.stringify(item));
                }
                return container;
            },
            button:    function(item, declarations) {
                var title = has(item, 'title') ? item.title : 'Submit';
                var button = $('<button class="btn button">' + title + '</button>');
                return button;
            },
            submit:    function(item, declarations) {
                var button = declarations.button(item, declarations);
                button.attr('type', 'submit');
                return button;
            },
            input:     function(item, declarations) {
                var type = has(declarations, 'type') ? declarations.type : 'text';
                var inputField = $('<input class="form-control" type="'+type+'"/>');
                var container = $('<div class="form-group"/>');
                var icon = '<span class="glyphicon glyphicon-ok form-control-feedback" aria-hidden="true"></span>';

                // IE8 bug: type can't be changed...
                /// inputField.attr('type', type);
                inputField.data('declaration',item);

                if(has(item, 'name')) {
                    inputField.attr('name', item.name);
                }

                if(has(item, 'placeholder')) {
                    inputField.attr('placeholder', item.placeholder);
                }

                if(has(item, 'value')) {
                    inputField.val(item.value);
                }

                if(has(item, 'title')) {
                    container.append(declarations.label(item, declarations));
                }

                if(has(item, 'mandatory') && item.mandatory) {
                    inputField.data('warn',function(value){
                        var hasValue = $.trim(value) != '';
                        var isRegExp = item.mandatory !== true;
                        var text = item.hasOwnProperty('mandatoryText')? item.mandatoryText: "Bitte übeprüfen!";

                        if(isRegExp){
                            hasValue = eval(item.mandatory).exec(value) != null;
                        }

                        if(hasValue){
                            container.removeClass('has-error');
                        }else{
                            $.notify( inputField, text, { position:"top right", autoHideDelay: 2000});
                            container.addClass('has-error');
                        }
                        return hasValue;
                    });
                }

                container.append(inputField);
                //container.append(icon);

                return container;
            },
            label:     function(item, declarations) {
                var label = $('<label/>');
                if(_.has(item, 'text')) {
                    label.html(item.text);
                }
                if(_.has(item, 'title')) {
                    label.html(item.title);
                }
                if(_.has(item, 'name')) {
                    label.attr('for', item.name);
                }
                return label;
            },
            checkbox:  function(item, declarations) {
                var container = $('<div class="checkbox"/>');
                var label = $('<label/>');
                var input = $('<input type="checkbox"/>');

                input.data('declaration',item);

                label.append(input);

                if(has(item, 'name')) {
                    input.attr('name', item.name);
                }

                if(has(item, 'value')) {
                    input.val(item.value);
                }

                if(has(item, 'title')) {
                    label.append(item.title);
                }

                if(has(item, 'checked')) {
                    input.attr('checked', "checked");
                }

                if(has(item, 'mandatory') && item.mandatory) {
                    input.data('warn',function(){
                        var isChecked = input.is(':checked');
                        if(isChecked){
                            container.removeClass('has-error');
                        }else{
                            container.addClass('has-error');
                        }
                        return isChecked;
                    });
                }

                container.append(label);
                return container;
            },
            radio:     function(item, declarations) {
                var container = $('<div class="radio"/>');
                var label = $('<label/>');
                var input = $('<input type="radio"/>');

                input.data('declaration',item);

                label.append(input);

                if(has(item, 'name')) {
                    input.attr('name', item.name);
                }

                if(has(item, 'title')) {
                    label.append(item.title);
                }

                if(has(item, 'value')) {
                    input.val(item.value);
                }

                if(has(item, 'checked')) {
                    input.attr('checked', "checked");
                }

                if(has(item, 'mandatory') && item.mandatory) {
                    input.data('warn',function(value){
                        var isChecked = input.is(':checked');
                        if(isChecked){
                            container.removeClass('has-error');
                        }else{
                            container.addClass('has-error');
                        }
                        return isChecked;
                    });
                }

                container.append(label);
                return container;
            },
            formGroup: function(item, declarations, widget) {
                var container = $('<div class="form-group"/>');
                if(has(item, 'children')) {
                    $.each(item.children, function(k, item) {
                        container.append(widget.genElement(item));
                    });
                }
                return container;
            },
            textArea:  function(item, declarations) {
                var container = $('<div class="form-group"/>');
                var input = $('<textarea class="form-control" rows="3"/>');
                input.data('declaration',item);

                if(has(item, 'value')) {
                    input.val(item.value);
                }

                $.each(['name', 'rows', 'placeholder'], function(i, key) {
                    if(has(item, key)) {
                        input.attr(key, item[key]);
                    }
                });

                if(has(item, 'title')) {
                    container.append(declarations.label(item, declarations));
                }

                container.append(input);

                return container;
            },
            select:    function(item, declarations) {
                var container = $('<div class="form-group"/>');
                var select = $('<select class="form-control"/>');

                select.data('declaration',item);

                $.each(['name'], function(i, key) {
                    if(has(item, key)) {
                        select.attr(key, item[key]);
                    }
                });

                if(has(item, 'multiple') && item.multiple) {
                    select.attr('multiple', 'multiple');
                }

                if(has(item, 'options')) {
                    $.each(item.options, function(value, title) {
                        var option = $("<option/>");
                        option.attr('value', value);
                        option.html(title);
                        option.data(this);
                        select.append(option);
                    });
                }

                if(has(item, 'value')) {
                    window.setTimeout(function(){
                        select.val(item.value);
                    },1)
                }

                if(has(item, 'mandatory') && item.mandatory) {
                    select.data('warn',function(){
                        var hasValue = $.trim(select.val()) != '';
                        if(hasValue){
                            container.removeClass('has-error');
                        }else{
                            container.addClass('has-error');
                        }
                        return hasValue;
                    });
                }
                
                if(has(item, 'title')) {
                    container.append(declarations.label(item, declarations));
                }

                container.append(select);

                return container;
            },
            file:      function(item, declarations) {
                var container = $('<div class="form-group"/>');
                var label = $('<label/>');
                var input = $('<input type="file"/>');

                input.data('declaration',item);

                if(has(item, 'name') && item.multiple) {
                    label.attr('for', item.name);
                    input.attr('name', item.name);
                }

                if(has(item, 'title')) {
                    label.html(item.title);
                    container.append(label);
                }

                container.append(input);
                return container;
            },
            tabs: function(item, declarations, widget) {
                var container = $('<div/>');
                var tabs = [];
                if(has(item, 'children') ) {
                    $.each(item.children, function(k, subItem) {
                        var htmlElement = widget.genElement(subItem);
                        var tab = {
                            html: htmlElement
                        };

                        if(has(subItem, 'title')) {
                            tab.title = subItem.title;
                        }
                        tabs.push(tab);
                    });
                }
                container.tabNavigator({children: tabs});
                return container;
            },
            fieldSet: function(item, declarations, widget) {
                var fieldSet = $("<fieldset/>");
                if(has(item, 'children')) {
                    $.each(item.children, function(k, item) {
                        fieldSet.append(widget.genElement(item));
                    })
                }

                if(has(item, 'breakLine') && item.breakLine) {
                    fieldSet.append(declarations.breakLine(item, declarations, widget));
                }

                return fieldSet;
            },
            date: function(item, declarations, widget) {
                var inputHolder = declarations.input(item, declarations, widget);
                var input = inputHolder.find('> input');
                input.dateSelector(item);
                return inputHolder;
            },

            /**
             * Break line
             *
             * @param item
             * @param declarations
             * @param widget
             * @return {*|HTMLElement}
             */
            breakLine: function(item, declarations, widget) {
                return $("<hr/>");
            }
        },

        _create:      function() {
            this._setOptions(this.options);
        },

        /**
         * Generate element by declaration
         *
         * @param item declaration
         * @return jquery html object
         */
        genElement: function(item) {
            var widget = this;
            var type = has(widget.declarations, item.type) ? item.type : 'html';
            var declaration = widget.declarations[type];
            var element = declaration(item, widget.declarations, widget);

            if(has(item, 'cssClass')) {
                element.addClass(item.cssClass);
            }

            if(typeof item == "object") {
                addEvents(element, item);
            }

            if(has(item, 'css')) {

                element.css(item.css);
            }

            element.data('item', item);
            return element;
        },

        /**
         * Generate elements
         *
         * @param element jQuery object
         * @param children declarations
         */
        genElements: function(element, children) {
            var widget = this;
            $.each(children, function(k, item) {
                var subElement = widget.genElement(item);
                element.append(subElement);
            })
        },

        _setOption:  function(key, value) {
            var widget = this;
            var element = $(widget.element);

            if(key === "children") {
                widget.genElements(element, value);
            }

            this._super(key, value);
        },
        _setOptions: function(options) {
            this._super(options);
            this.refresh();
        },
        refresh:     function() {
            this._trigger('refresh');
        }
    });

})(jQuery);

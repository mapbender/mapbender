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
            fluidContainer: function(item, declarations, widget) {
                var container = $('<div class="container-fluid"/>');
                var hbox = $('<div class="row"/>');
                if(has(item, 'items')) {
                    $.each(item.items, function(k, item) {
                        hbox.append(widget.genElement(item));
                    })
                }
                container.append(hbox);
                return container;
            },
            inline: function(item, declarations, widget) {
                var container = $('<div class="form-inline"/>');
                if(has(item, 'items')) {
                    $.each(item.items, function(k, item) {
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
                    container.html(html);
                }else{
                    container.html(JSON.stringify(item));
                }
                return container;
            },
            button:    function(item, declarations) {
                var title = has(item, 'title') ? item.title : 'Submit';
                var button = $('<button class="btn button">' + title + '</button>');
                addEvents(button, item);
                return button;
            },
            submit:    function(item, declarations) {
                var button = declarations.button(item, declarations);
                button.attr('type', 'submit');
                return button;
            },
            input:     function(item, declarations) {
                var inputField = $('<input class="form-control"/>');
                var container = $('<div class="form-group"/>');
                var type = has(declarations, 'type') ? declarations.type : 'text';

                inputField.attr('type', type);

                if(has(item, 'name')) {
                    inputField.attr('name', item.name);
                }

                if(has(item, 'placeholder')) {
                    inputField.attr('placeholder', item.placeholder);
                }

                if(has(item, 'value')) {
                    inputField.val(item.value);
                }

                if(has(item, 'text')) {
                    container.append(declarations.label(item, declarations));
                }

                container.append(inputField);

                return container;
            },
            label:     function(item, declarations) {
                var label = $('<label/>');
                if(_.has(item, 'text')) {
                    label.html(item.text);
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

                label.append(input);

                if(has(item, 'name')) {
                    input.attr('name', item.name);
                }

                if(has(item, 'value')) {
                    input.val(item.value);
                }

                if(has(item, 'text')) {
                    label.append(item.text);
                }

                if(has(item, 'checked')) {
                    input.attr('checked', "checked");
                }

                container.append(label);
                return container;
            },
            radio:     function(item, declarations) {
                var container = $('<div class="radio"/>');
                var label = $('<label/>');
                var input = $('<input type="radio"/>');

                label.append(input);

                if(has(item, 'name')) {
                    input.attr('name', item.name);
                }

                if(has(item, 'text')) {
                    label.append(item.text);
                }

                if(has(item, 'value')) {
                    input.val(item.value);
                }

                if(has(item, 'checked')) {
                    input.attr('checked', "checked");
                }

                container.append(label);
                return container;
            },
            formGroup: function(item, declarations) {
                return $('<div class="form-group"/>');
            },
            textArea:  function(item, declarations) {
                var input = $('<textarea class="form-control" rows="3"/>');

                if(has(item, 'value')) {
                    input.val(item.value);
                }

                $.each(['name', 'rows', 'placeholder'], function(i, key) {
                    if(has(item, key)) {
                        input.attr(key, item[key]);
                    }
                });

                return input;
            },
            select:    function(item, declarations) {
                var container = $('<div class="form-group"/>');
                var select = $('<select class="form-control"/>');

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
                container.append(select);

                return container;
            },
            file:      function(item, declarations) {
                var container = $('<div class="form-group"/>');
                var label = $('<label/>');
                var input = $('<input type="file"/>');

                if(has(item, 'name') && item.multiple) {
                    label.attr('for', item.name);
                    input.attr('name', item.name);
                }

                if(has(item, 'text')) {
                    label.html(item.text);
                    container.append(label);
                }

                container.append(input);
                return container;
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
         * @param items declarations
         */
        genElements: function(element, items) {
            var widget = this;
            $.each(items, function(k, item) {
                var subElement = widget.genElement(item);
                element.append(subElement);
            })
        },

        _setOption:  function(key, value) {
            var element = $(this.element);
            var widget = this;

            if(key === "items") {
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

    /**
     * Tests
     */
    var popup = $("<div/>").popupDialog({
        title: 'Form generator test',
        width: "423px"
    });

    popup.generateElements({
        items: [{
            type:  'inline',
            items: [{
                type: 'label',
                text: 'Name',
                css:  {"margin-right": "10px"}
            }, {
                type:        'input',
                name:        'inline[anrede]',
                placeholder: 'Anrede',
                css:         {"margin-right": "10px",width:"60px"}
            }, {
                type:        'input',
                name:        'inline[firstname]',
                placeholder: 'Vorname'
            }, {
                type:        'input',
                name:        'inline[secondname]',
                placeholder: 'Nachname'
            }]
        }, {
            type: 'label',
            text: 'Anrede'
        }, {
            type:    'select',
            name:    'gender',
            options: {
                male:   'Herr',
                female: 'Frau'
            }
        }, {
            type:     'select',
            name:     'titul',
            multiple: true,
            options:  ['Prof.', 'Dr.', 'med.', 'jur.', 'vet.', 'habil.']
        }, {
            type:        'input',
            name:        'name',
            placeholder: 'Name'
        }, {
            type:        'input',
            name:        'Nachname',
            placeholder: 'Nachname'

        }, {
            type:        'textArea',
            name:        'description',
            placeholder: 'Beschreibung'
        }, {
            type:        'input',
            name:        'email',
            text:        'E-Mail',
            placeholder: 'E-Mail'
        }, {
            type: 'file',
            name: 'file',
            text: 'Foto'
        }, {
            type:    'checkbox',
            name:    'check1',
            value:   true,
            text:    'Checked!',
            checked: true
        }, {
            type:  'checkbox',
            name:  'check2',
            value: true,
            text:  'Check me '
        }, {
            type:  'radio',
            name:  'radio1',
            value: 'test1',
            text:  'Radio #1'
        }, {
            type:  'radio',
            name:  'radio1',
            value: 'test2',
            text:  'Radio #2'
        }, "<div style='background-color: #ff0000; height: 2px'/>", {
            type:     'submit',
            cssClass: 'right',
            click:    function() {
                console.log('CLICK');
            }
        }]
    });

    //console.log("TEST");
})(jQuery);

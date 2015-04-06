/**
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 05.04.15 by WhereGroup GmbH & Co. KG
 */
(function($){

    /**
     * Check if object has a key
     *
     * @param obj
     * @param key
     * @returns {boolean}
     */
    function has (obj, key){
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
            html:   {
                init: function(item) {
                    var container = $('<div class="html-element"/>');
                    return container.html(typeof item === 'string' ? item : JSON.stringify(item));
                }
            },
            button: {
                init: function(item, declarations) {
                    var title = has(item, 'title') ? item.title : 'Submit';
                    var button = $('<button class="btn button">' + title + '</button>');
                    addEvents(button, item);
                    return button;
                }
            },
            submit: {
                init: function(item, declarations) {
                    var button = declarations.button.init(item, declarations);
                    button.attr('type', 'submit');
                    return button;
                }
            },
            input:  {
                init: function(item, declarations) {
                    var inputField = $('<input class="form-control"/>');
                    var container = $('<div class="form-group"/>');

                    if(has(item, 'name')) {
                        inputField.attr('name', item.name);
                    }

                    if(has(item, 'placeholder')) {
                        inputField.attr('placeholder', item.placeholder);
                    }

                    if(has(item, 'value')) {
                        inputField.val(item.value);
                    }

                    if(has(inputField, 'text')) {
                        container.append(declarations.label.init(item, declarations));
                    }

                    container.append(inputField);

                    return container;
                }
            },
            label:{
                init: function(item){
                    var label = $('<label/>');
                    if(_.has(item,'text')){
                        label.html(item.text);
                    }
                    if(_.has(item,'name')){
                        label.attr('for',item.name);
                    }
                    return label;
                }
            },
            'form-group':{
                init: function(){
                    return;
                }
            }

        },
        _create: function() {
            this._setOptions(this.options);
        },

        _setOption: function( key, value ) {

            var element = $(this.element);
            var widget = this;
            if(key === "items") {
                $.each(value, function(k,item) {
                    var type = has(widget.declarations, item.type) ? item.type : 'html';
                    var declaration = widget.declarations[type];
                    var subElement = declaration.init(item, widget.declarations);

                    if(has(item,'cssClass')){
                        subElement.addClass(item.cssClass);
                    }

                    element.append(subElement);
                    element.data('item',item);
                })
            }

            this._super( key, value );
        },
        _setOptions: function( options ) {
            this._super( options );
            this.refresh();
        },
        refresh: function() {
            this._trigger('refresh');
        }
    });

    var popup = $("<div/>").popupDialog();

    popup.generateElements({
        items: [{
            type: 'label',
            text: 'Label TEST'
        }, {
            type:        'input',
            name:        'name',
            text:        'Name',
            placeholder: 'Enter your name'
        }, {
            type:        'input',
            name:        'Nachname',
            text:        'Nachname',
            placeholder: 'Nachname'
        }, {
            type:        'input',
            name:        'email',
            text:        'E-Mail',
            placeholder: 'eslider@gmail.com'
        },
        "<div style='background-color: #ff0000; height: 2px'/>", {
            type:     'submit',
            cssClass: 'right',
            click: function(){
                console.log('CLICK');
            }
        }]
    });

    //console.log("TEST");
})(jQuery);

/**
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 05.04.15 by WhereGroup GmbH & Co. KG
 */
(function($){

    function has (obj, key){
        return typeof obj[key] !== 'undefined';
    }

    $.widget('mapbender.generateElements', {
        options: {},
        declarations:{
            'input':{
                init: function(item,declarations) {
                    var inputField = $('<input class="form-control"/>');
                    var container = $('<div class="form-group"/>');

                    if(has(item, 'name')) {
                        inputField.attr('name',item.name);
                    }

                    if(has(item, 'placeholder')) {
                        inputField.attr('placeholder',item.placeholder);
                    }

                    if(has(item,'value')){
                        inputField.val(item.value);
                    }

                    console.log(declarations);
                    if(has(inputField,'text')){
                        container.append(declarations.label.init(item,declarations));
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
                $.each(value, function() {
                    var item = this;
                    if(!has(widget.declarations, item.type)) {
                        return;
                    }
                    var declaration = widget.declarations[item.type];
                    element.append(declaration.init(item, widget.declarations));
                    element.data('item', item);
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
        items: [ {
            type: 'label', //value:       'test',
            text: 'HE-HE'
        },{
            type: 'label',
            text: 'Name'
        }, {type: 'submit'}, {
            type:        'input', //value:       'test',
            name:        'name',
            text:        'Name',
            placeholder: 'Enter your name'
        }, {type: 'submit'}, {
            type:        'input', //value:       'test',
            name:        'Nachname',
            text:        'Nachname',
            placeholder: 'Nachname'
        }, {type: 'submit'}, {type: 'submit'}, {
            type:        'input', //value:       'test',
            name:        'email',
            text:        'E-Mail',
            placeholder: 'eslider@gmail.com'
        }, {type: 'submit'}]
    });

    //console.log("TEST");
})(jQuery);

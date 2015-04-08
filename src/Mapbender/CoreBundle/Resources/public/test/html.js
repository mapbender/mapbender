/**
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 05.04.15 by WhereGroup GmbH & Co. KG
 */
(function($) {

    return ;

    /**
     * Tests
     */
    var popup = $("<div/>").popupDialog({
        title: 'Form generator test',
        width: "423px"
    });

    var items2 = [{
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
    }, "<div style='background-color: #c0c0c0; height: 2px'/>", {
        type:     'submit',
        cssClass: 'right',
        click:    function() {
            var button = $(this);
            var form = button.closest('.modal-body');
            console.log(form.formData());
        }
    }];
    var tabFormItems =  [{
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
    }, "<div style='background-color: #c0c0c0; height: 2px'/>", {
        type:     'submit',
        cssClass: 'right',
        click:    function() {
            var button = $(this);
            var form = button.closest('.modal-body');
            console.log(form.formData());
            return false;
        }
    }];

    popup.generateElements({
        items: [{
            type:  'tabs',
            items: [{
                type:  'form',
                title: 'Form 1',
                items: tabFormItems
            }, {
                type: 'form',
                title: 'Form 2',
                items:[
                    {
                        text: 'Test Label:',
                        type:  'input',
                        title: 'Text tab',
                        placeholder: 'Test input'
                    }
                ]
            }, {
                type:  'html',
                title: 'Text tab',
                html:  'Beispiel Text'
            }]
        }]
    });

})(jQuery);

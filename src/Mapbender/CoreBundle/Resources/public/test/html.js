/**
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 05.04.15 by WhereGroup GmbH & Co. KG
 */
(function($) {

    // widget <- HTMLElement widget
    // options <- element configuration
    // element <- jQuery widget DOM element

    $("#layertree").generateElements({
        children: [{
            type:  'input',
            title: "test"
        }]
    })

    var forms = findItem('form');

    $.each(forms, function(i, form) {
        form.children.push({
            type:  'button',
            title: 'Absenden',
            click: function(event) {
                var form = $(event.currentTarget).closest('form');
                var data = form.formData();
                console.log(data);
                return false;
            }
        })
    });

    // disable rendering
    //delete options.children;

    //return ;
    var children2 = [{
        type:     'inline',
        children: [{
            type:  'label',
            title: 'Name',
            css:   {"margin-right": "10px"}
        }, {
            info:        "Info text",
            type:        'input',
            name:        'inline[anrede]',
            placeholder: 'Anrede',
            css:         {
                "margin-right": "10px",
                width:          "60px"
            }
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
        type:  'label',
        title: 'Anrede'
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
        title:       'E-Mail',
        placeholder: 'E-Mail'
    }, {
        type:  'file',
        name:  'file',
        title: 'Foto'
    }, {
        type:    'checkbox',
        name:    'check1',
        value:   true,
        title:   'Checked!',
        checked: true
    }, {
        type:  'checkbox',
        name:  'check2',
        value: true,
        title: 'Check me '
    }, {
        type:  'radio',
        name:  'radio1',
        value: 'test1',
        title: 'Radio #1'
    }, {
        type:  'radio',
        name:  'radio1',
        value: 'test2',
        title: 'Radio #2'
    }, "<div style='background-color: #c0c0c0; height: 2px'/>", {
        type:     'submit',
        cssClass: 'right',
        click:    function() {
            var button = $(this);
            var form = button.closest('.modal-body');
            console.log(form.formData());
        }
    }];
    var tabFormItems = [{
        type:     'inline',
        children: [{
            type:  'label',
            title: 'Name',
            css:   {"margin-right": "10px"}
        }, {
            type:        'input',
            name:        'inline[anrede]',
            placeholder: 'Anrede',
            css:         {
                "margin-right": "10px",
                width:          "60px"
            }
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
        type:  'label',
        title: 'Anrede'
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
        title:       'E-Mail',
        placeholder: 'E-Mail'
    }, {
        type:  'file',
        name:  'file',
        title: 'Foto'
    }, {
        type:    'checkbox',
        name:    'check1',
        value:   true,
        title:   'Checked!',
        checked: true
    }, {
        type:  'checkbox',
        name:  'check2',
        value: true,
        title: 'Check me '
    }, {
        type:  'radio',
        name:  'radio1',
        value: 'test1',
        title: 'Radio #1'
    }, {
        type:  'radio',
        name:  'radio1',
        value: 'test2',
        title: 'Radio #2'
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

    /**
     * Tests
     */
    var popup = $("<div/>");

    popup.generateElements({
        children: [{
            type:     "tabs",
            children: [{
                type:     "form",
                title:    "Formular #1",
                children: [{
                    type:  "html",
                    html:  "<div>TEST</div>",
                    click: function() {
                        console.log(this);
                    }
                }, {
                    type:        "input",
                    name:        "Test",
                    title:       "Label for something",
                    mandatory:   true,
                    placeholder: "Enter something"
                }, {
                    type:      "select",
                    options:   ["Herr", "Frau"],
                    name:      "gender",
                    value:     '',
                    mandatory: true
                }, {type: "input"}, {
                    type:  "radio",
                    name:  "acception",
                    title: "Ja",
                    value: "1"
                }, {
                    type:  "radio",
                    name:  "acception",
                    title: "Nein",
                    value: "2"
                }, {
                    type:      "checkbox",
                    name:      "asdasd",
                    mandatory: true,
                    title:     "yay!",
                    value:     "2"
                }, {
                    type:     "inline",
                    children: [{
                        type:        "input",
                        placeholder: "Name",
                        css:         {"margin-right": "20px"}
                    }, {
                        type:        "input",
                        placeholder: "Vorname"
                    }]
                }, {
                    type:     "tabs",
                    children: [{
                        type:  "HTML",
                        title: "test tab 1",
                        html:  "test"
                    }, {
                        type:  "HTML",
                        title: "test tab 2",
                        html:  "test 2"
                    }]
                }, {
                    type:     "button",
                    title:    "OK",
                    cssClass: "right",
                    click:    function() {
                        var data = popup.formData();
                        console.log(data);
                        return false;
                    }
                }, {
                    type:     "button",
                    title:    "Fill",
                    cssClass: "right",
                    click:    function() {
                        popup.formData({
                            Test:      "Beispiel",
                            acception: 2,
                            gender:    1
                        });

                        return false;
                    }
                }]
            }, {
                type:     "form",
                title:    "Form 2",
                children: children2
            }]
        }]
    });

    popup.popupDialog({
        title:  'Form generator test',
        width:  "423px",
        height: 450,
        modal:  false
    });

    //popup.generateElements({
    //    children: [{
    //        type:  'tabs',
    //        children: [{
    //            type:  'form',
    //            title: 'Form 1',
    //            children: tabFormItems
    //        }, {
    //            type: 'form',
    //            title: 'Form 2',
    //            children:[
    //                {
    //                    title: 'Test Label:',
    //                    type:  'input',
    //                    title: 'Text tab',
    //                    placeholder: 'Test input'
    //                }
    //            ]
    //        }, {
    //            type:  'html',
    //            title: 'Text tab',
    //            html:  'Beispiel Text'
    //        }]
    //    }]
    //});

})(jQuery);

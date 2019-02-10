$(function() {
    var popupCls = Mapbender.Popup;
    $("table.elementsTable tbody").sortable({
        connectWith: "table.elementsTable tbody",
        items: "tr:not(.dummy)",
        distance: 20,
        stop: function(event, ui) {
            $(ui.item).parent().find("tr.element").each(function(idx, elm) {
                if ($(elm).attr("data-href") === $(ui.item).attr("data-href")) {
                    $.ajax({
                        url: $(ui.item).attr("data-href"),
                        type: "POST",
                        data: {
                            number: idx,
                            region: $(ui.item).closest('table').attr("data-region")
                        },
                        success: function(data, textStatus, jqXHR) {
                            if (data.error && data.error !== '') {
                                document.location.reload();
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            document.location.reload();
                        }
                    });
                }
            });
        }
    });

    $('tr.element, tr.sourceinst').find('input[type="checkbox"]').click(function() {
        if ($(this).attr('data-href') === undefined) {
            return;
        }

        $.ajax({
            url: $(this).attr("data-href"),
            type: "POST",
            data: {
                enabled: !$(this).is(":checked")
            },
            success: function(data, textStatus, jqXHR) {
                if (data.error && data.error !== '') {
                    document.location.reload();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                document.location.reload();
            }
        });
    });

    $("table.layersetTable tbody").sortable({
        connectWith: "table.layersetTable tbody",
        items: "tr:not(.header)",
        distance: 20,
        stop: function(event, ui) {
            $(ui.item).parent().find("tr").each(function(idx, elm) {
                if ($(elm).attr("data-id") === $(ui.item).attr("data-id")) {
                    $.ajax({
                        url: $(ui.item).attr("data-href"),
                        type: "POST",
                        data: {
                            number: idx,
                            new_layersetId: $(elm).closest('table').attr("data-id")
                        }
                    });
                }
            });
        }
    });

    $("ul.layercollection ul").each(function() {
        $(this).sortable({
            cursor: "move",
            connectWith: "ul.layercollection",
            items: "li:not(.header,.root,.dummy)",
            distance: 20,
            stop: function(event, ui) {
                $(ui.item).parent().find("li").each(function(idx, elm) {
                    if ($(elm).attr("data-id") === $(ui.item).attr("data-id")) {

                        $.ajax({
                            url: $(ui.item).attr("data-href"),
                            type: "POST",
                            data: {
                                number: idx - $("ul.layercollection li.header").length, // idx - header
                                id: $(ui.item).attr("data-id")
                            },
                            success: function(data, textStatus, jqXHR) {
                                if (data.error && data.error !== '') {
                                    document.location.reload(); // why?
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                document.location.reload(); // why?
                            }
                        });
                    }
                });
            }
        })
    });

    $('ul.layercollection div.group button.groupon').bind("click", function(e) {
        var className = $(this).parent().attr('class');
        $('ul.layercollection li span.' + className + ' input[type="checkbox"]').each(function(index) {
            if ($(this).attr("disabled") !== "disabled") {
                $(this).attr("checked", true);
            }
        });
        return false;
    });
    $('ul.layercollection div.group button.groupoff').bind("click", function(e) {
        var className = $(this).parent().attr('class');
        $('ul.layercollection li span.' + className + ' input[type="checkbox"]').each(function(index) {
            if ($(this).attr("disabled") !== "disabled") {
                $(this).attr("checked", false);
            }
        });
        return false;
    });


    var popup;

    function startEditElement(formUrl, strings, extraButtons) {
        $.ajax(formUrl).then(function(response) {
            popup = new popupCls({
                title: Mapbender.trans(strings.title || 'mb.manager.components.popup.edit_element.title'),
                subTitle: strings.subTitle || '',
                modal: true,
                closeOnOutsideClick: true,
                destroyOnClose: true,
                cssClass: "elementPopup",
                content: response,
                buttons: (extraButtons || []).slice().concat([
                    {
                        label: Mapbender.trans(strings.save || 'mb.manager.components.popup.edit_element.btn.ok'),
                        cssClass: 'button',
                        callback: function() {
                            elementFormSubmit();
                        }
                    },
                    {
                        label: Mapbender.trans(strings.cancel || 'mb.manager.components.popup.edit_element.btn.cancel'),
                        cssClass: 'button buttonCancel critical',
                        callback: function() {
                            this.close();
                        }
                    }
                ])
            });
            popup.$element.on('change', function(event) {
                $('#elementForm', popup.$element).data('dirty', true);
            });
            popup.$element.on('close', function(event, token) {
                if (true === $('#elementForm', popup.$element).data('dirty')) {
                    if (!confirm('Ignore Changes?')) {
                        token.cancel = true;
                    }
                }
            });
        });
    }

    function startElementChooser(regionName, listUrl) {
        var title ='mb.manager.components.popup.add_element.title';
        $.ajax({
            url: listUrl
        }).then(function(response) {
            popup = new popupCls({
                title: Mapbender.trans(title),
                subTitle: ' - ' + regionName,
                modal: true,
                closeOnOutsideClick: true,
                destroyOnClose: true,
                content: response,
                cssClass: "elementPopup",
                buttons: [
                    {
                        label: Mapbender.trans("mb.manager.components.popup.add_element.btn.cancel"),
                        cssClass: 'button buttonCancel critical',
                        callback: function() {
                            this.close();
                        }
                    }
                ]
            });
            popup.$element.on('click', '.chooseElement', function() {
                var elTypeSubtitle = $('.subTitle', this).first().text();
                var editStrings = {
                    title: title,
                    subTitle: ' - ' + regionName + ' - ' + elTypeSubtitle,
                    save: 'mb.manager.components.popup.add_element.btn.ok',
                    cancel: 'mb.manager.components.popup.add_element.btn.cancel'
                };

                startEditElement($(this).attr('href'), editStrings, [
                    {
                        label: Mapbender.trans("mb.manager.components.popup.add_element.btn.back"),
                        cssClass: 'button buttonBack',
                        callback: function() {
                            startElementChooser(regionName, listUrl);
                        }
                    }
                ]);
                return false;
            });
        });
    }

    $(".addElement").bind("click", function(event) {
        var regionName = $('.subTitle', $(this).closest('.region')).first().text();
        startElementChooser(regionName, $(this).attr('href'));
        return false;
    });

    $(".editElement").bind("click", function() {
        startEditElement($(this).attr('data-url'), {});
        return false;
    });

    function elementFormSubmit() {
        var $form = $("#elementForm"),
            data = $form.serialize(),
            url = $form.attr('action'),
            self = this;

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            error: function (e, statusCode, message) {
                Mapbender.error(Mapbender.trans("mb.application.save.failure.general") + ' ' + message);
            },
            success: function(data) {
                if (data.length > 0) {
                    $form.parent().html( data );
                } else {
                    $form.data('dirty', false);
                    self.close();
                    window.location.reload();
                }
            }
        });
    }

    // Element security
    $(".secureElement").bind("click", function() {
        var self = $(this),
                toremove = null;

        if (popup) {
            popup = popup.destroy();
        }
        popup = new popupCls({
            title: "Secure element",
            closeOnOutsideClick: true,
            height: 600,
            content: [
                $.ajax({
                    url: self.attr("data-url"),
                    complete: function() {
                        $('#addElmPermission').on('click', function(e) {
                            $.ajax({
                                url: $(e.target).attr("href"),
                                type: "GET",
                                success: function(data, textStatus, jqXHR) {
                                    $(".contentItem:first,.buttonOk", popup.$element).addClass('hidden');
                                    $(".buttonAdd,.buttonBack", popup.$element).removeClass('hidden');
                                    popup.addContent(data);
                                    var groupUserItem, text, me, groupUserType;

                                    $("#listFilterGroupsAndUsers", popup.$element).find(".filterItem").each(function(i, e) {

                                        groupUserItem = $(e);
                                        groupUserType = (groupUserItem.find(".tdContentWrapper")
                                                .hasClass("iconGroup") ? "iconGroup"
                                                : "iconUser");
                                        $("#permissionsBody", popup.$element).find(".labelInput").each(function(i, e) {
                                            me = $(e);
                                            text = me.text().trim();
                                            if ((groupUserItem.text().trim().toUpperCase().indexOf(text.toUpperCase()) >= 0) &&
                                                    (me.parent().hasClass(groupUserType))) {
                                                groupUserItem.remove();
                                            }
                                        });
                                    });
                                }
                            });
                            return false;
                        });
                        $("#permissionsBody", popup.$element).on("click", '.iconRemove', function(e) {
                            var self = $(e.target);
                            var parent = self.parent().parent();
                            var userGroup = ((parent.find(".iconUser").length == 1) ? "user " : "group ") + parent.find(".labelInput").text();
                            popup.addContent(Mapbender.trans('fom.core.components.popup.delete_user_group.content', {'userGroup': userGroup}));
                            toremove = parent;
                            $(".contentItem:first,.buttonOk", popup.$element).addClass('hidden');
                            $(".buttonRemove,.buttonBack", popup.$element).removeClass('hidden');
                        });
                    }
                })
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans('mb.manager.components.popup.element_acl.btn.cancel'),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        toremove = null;
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans('mb.manager.components.popup.element_acl.btn.ok'),
                    cssClass: 'button buttonOk right',
                    callback: function() {
                        toremove = null;
                        $("#elementSecurity", popup.$element).submit();
                        window.setTimeout(function() {
                            window.location.reload();
                        }, 50);
                    }
                },
                'add': {
                    label: Mapbender.trans('mb.manager.components.popup.element_acl.btn.add'), //Mapbender.trans("mb.wmc.element.wmceditor.popup.btn.back"),
                    cssClass: 'button right buttonAdd hidden',
                    callback: function() {
                        toremove = null;
                        $(".contentItem:first", popup.$element).removeClass('hidden');
                        if ($(".contentItem", popup.$element).length > 1) {
                            var proto = $(".contentItem:first #permissionsHead", popup.$element).attr("data-prototype");
                            if (proto.length > 0) {
                                var body = $(".contentItem:first #permissionsBody", popup.$element);
                                var count = body.find("tr").length;
                                var text, val, parent, newEl;
                                $("#listFilterGroupsAndUsers", popup.$element).find(".iconCheckboxActive").each(function(i, e) {
                                    parent = $(e).parent();
                                    text = parent.find(".labelInput").text().trim();
                                    val = parent.find(".hide").text().trim();
                                    userType = parent.hasClass("iconGroup") ? "iconGroup" : "iconUser";
                                    newEl = body.prepend(proto.replace(/__name__/g, count))
                                            .find("tr:first");

                                    newEl.addClass("new").find(".labelInput").text(text);
                                    newEl.find(".input").attr("value", val);
                                    newEl.find(".view.checkWrapper").trigger("click");
                                    newEl.find(".userType")
                                            .removeClass("iconGroup")
                                            .removeClass("iconUser")
                                            .addClass(userType);
                                    ++count;
                                });
                            }
                            $('.contentItem:first .permissionsTable', popup.$element).removeClass('hidePermissions');
                            $('.contentItem:first #permissionsDescription', popup.$element).addClass('hidden');

                            $(".contentItem:not(.contentItem:first)", popup.$element).remove();
                        }
                        $(".buttonAdd, .buttonBack", popup.$element).addClass('hidden');
                        $(".buttonOk", popup.$element).removeClass('hidden');
                    }
                },
                'remove': {
                    label: Mapbender.trans('mb.manager.components.popup.element_acl.btn.remove'), //Mapbender.trans("mb.wmc.element.wmceditor.popup.btn.back"),
                    cssClass: 'button right buttonRemove hidden',
                    callback: function() {
                        $(".contentItem:first", popup.$element).removeClass('hidden');
                        if (toremove !== null) {
                            toremove.remove();
                        }
                        toremove = null;
                        if ($(".contentItem", popup.$element).length > 1) {
                            $(".contentItem:not(.contentItem:first)", popup.$element).remove();
                        }
                        $(".buttonAdd,.buttonBack,.buttonRemove", popup.$element).addClass('hidden');
                        $(".buttonOk", popup.$element).removeClass('hidden');
                    }
                },
                'back': {
                    label: Mapbender.trans('mb.manager.components.popup.element_acl.btn.back'), //Mapbender.trans("mb.wmc.element.wmceditor.popup.btn.back"),
                    cssClass: 'button left buttonBack hidden',
                    callback: function() {
                        toremove = null;
                        $(".contentItem:first", popup.$element).removeClass('hidden');
                        if ($(".contentItem", popup.$element).length > 1) {
                            $(".contentItem:not(.contentItem:first)", popup.$element).remove();
                        }
                        $(".buttonAdd,.buttonBack,.buttonRemove", popup.$element).addClass('hidden');
                        $(".buttonOk", popup.$element).removeClass('hidden');
                    }
                }
            }
        });
        return false;
    });

    // Delete element
    $('.removeElement').bind("click", function() {
        var $el = $(this);
        popup = Mapbender.Manager.confirmDelete($el, $el.attr('data-url'), {
            title: 'mb.manager.components.popup.delete_element.title',
            confirm: 'mb.manager.components.popup.delete_element.btn.ok',
            cancel: 'mb.manager.components.popup.delete_element.btn.cancel',
            subTitle: 'mb.manager.components.popup.delete_element.subtitle'
        });
        return false;
    });

    // Layers --------------------------------------------------------------------------------------
    function addOrEditLayerset(edit) {
        var self = $(this);

        if (popup) {
            popup = popup.destroy();
        }
        popup = new popupCls({
            title: ((self.hasClass("editLayerset")) ? Mapbender.trans("mb.manager.components.popup.add_edit_layerset.title_edit")
                    : Mapbender.trans("mb.manager.components.popup.add_edit_layerset.title_add")),
            closeOnOutsideClick: true,
            content: [
                $.ajax({url: self.attr("href")})
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.add_edit_layerset.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.add_edit_layerset.btn.ok"),
                    cssClass: 'button right',
                    callback: function() {
                        $("#layersetForm").submit();
                    }
                }
            }
        });
        return false;
    }

    // Add layerset action
    $(".addLayerset").bind("click", addOrEditLayerset);
    // Edit layerset action
    $(".editLayerset").bind("click", addOrEditLayerset);
    // Delete layerset Action
    $(".removeLayerset").bind("click", function() {
        var strings = {
            title: 'mb.manager.components.popup.delete_layerset.title',
            confirm: 'mb.manager.components.popup.delete_layerset.btn.ok',
            cancel: 'mb.manager.components.popup.delete_layerset.btn.cancel'
        };
        var $el = $(this);
        var actionUrl = $el.attr('href');
        $.ajax({url: actionUrl}).then(function(content) {
            popup = Mapbender.Manager.confirmDelete($el, actionUrl, strings, content);
        });
        return false;
    });
    // Add Instance Action
    $(".addInstance").bind("click", function(event) {
        var self = $(this);
        if (popup) {
            popup = popup.destroy();
        }
        popup = new popupCls({
            title: Mapbender.trans("mb.manager.components.popup.add_instance.title"),
            subTitle: " - " + self.parent().siblings(".subTitle").text(),
            closeOnOutsideClick: true,
            cssClass: 'new-instance-select',
            content: [
                $.ajax({url: self.attr("href")})
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.add_instance.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                }
            }
        });
        return false;
    });
    // Delete instance
    $('.removeInstance').bind("click", function() {
        var $el = $(this);
        popup = Mapbender.Manager.confirmDelete($el, $el.attr('data-url'), {
            title: 'mb.manager.components.popup.delete_instance.title',
            confirm: 'mb.manager.components.popup.delete_instance.btn.ok',
            cancel: 'mb.manager.components.popup.delete_instance.btn.cancel'
        });
        return false;
    });

    var applicationForm = $('form[name=application]');
    var screenShot = applicationForm.find('.screenshot_img');
    var screenShotCell = applicationForm.find('div.cell_edit');
    var screenShotImg = screenShotCell.find('img');
    var uploadButton = applicationForm.find('.upload_button');
    var fileInput = applicationForm.find('#application_screenshotFile');
    var validationMsgBox = applicationForm.find('span.validationMsgBox');
    var maxFileSize = applicationForm.find('#application_maxFileSize').val();
    var minWidth = applicationForm.find('#application_screenshotWidth').val();
    var minHeight = applicationForm.find('#application_screenshotHeight').val();
    var uploadScreenShot = applicationForm.find('#application_uploadScreenShot');

    
    fileInput.on('mouseover', function() {
        uploadButton.addClass('hover');
    }).on('mouseout', function() {
        uploadButton.removeClass('hover');
    }).on('change', function(e) {
        setUploadFilename(e);  
//        uploadScreenshotFile(this.files);

        var file = this.files;
        var reader = new FileReader();
        var img = new Image();
        var src = "";
        
        img.onload = function() {
            if (img.width >= minWidth && img.height >= minHeight) {
               
                screenShotImg.attr('src', src);
                screenShotImg.removeClass('hidden');
                screenShot.find('div.messageBox').remove();
                validationMsgBox.remove();
                screenShotImg.before('<div class="delete button critical hidden">X</div>');
                deleteScreenShotButtonInit();
                screenShot.removeClass('default iconAppDefault');
                applicationForm.find('input[name="application[removeScreenShot]"]').val(0);
                uploadScreenShot.val(0);
            } else {
                uploadScreenShot.val(1);
                $('<span class=\"validationMsgBox smallText\">'+ Mapbender.trans('mb.core.entity.app.screenshotfile.resolution.error',
                {'screenshotWidth':minWidth, 'screenshotHeight':minHeight ,'uploadWidth': img.width, 'uploadHeighth': img.height }) +'</span>').insertAfter(fileInput);
                validationMsgBox = applicationForm.find('span.validationMsgBox');
            }
        };
    
        
         if (file && file[0]) {
            if (file[0].type.match('image/')){
                if (file[0].size <= 2097152) {
                   
                    reader.onload = function (e) {
                        img.src = src = e.target.result;
                    };

                    reader.readAsDataURL(file[0]);
                    
                }else{
                    var uploadFileSize = file[0].size;
                    $('<span class=\"validationMsgBox smallText\">'+ Mapbender.trans('mb.core.entity.app.screenshotfile.error',{'maxFileSize':maxFileSize, 'uploadFileSize': uploadFileSize }) +'</span>').insertAfter(fileInput);
                    validationMsgBox = applicationForm.find('span.validationMsgBox');
                }
            }else{
                 $('<span class=\"validationMsgBox smallText\">'+ Mapbender.trans('mb.core.entity.app.screenshotfile.format_error') +'</span>').insertAfter(fileInput);
                  validationMsgBox = applicationForm.find('span.validationMsgBox');
            }
        }
        
    });
   
    var setUploadFilename = function(e){
        var fileName = $(e.currentTarget).val().replace(/^.+(\\)/, '');
        var displayFilename = fileName || Mapbender.trans('mb.manager.admin.application.upload.label');
        if (displayFilename.length > 35) {
            $('.upload_label').text(displayFilename.substring(0, 35) + '…');
        } else {
            $('.upload_label').text(displayFilename);
        }
    };
    
    var deleteScreenShotButtonInit = function() {
           
        var deleteButton = screenShot.find('.delete');
        screenShot.mouseenter('mouseover', function() {
            deleteButton.removeClass('hidden');
        }).mouseleave('mouseout', function() {
            deleteButton.addClass('hidden');
        });

        deleteButton.on('click', function() {
            screenShotImg.addClass('hidden');
            deleteButton.remove();
            screenShotImg.attr('src',"");
            screenShot.addClass('default').addClass('iconAppDefault');
            applicationForm.find('.upload_label').html(Mapbender.trans("mb.manager.upload.label_delete"));
            applicationForm.find('input[name="application[removeScreenShot]"]').val(1);
        });
        return deleteButton;
    };
    
    deleteScreenShotButtonInit();
    


    fileInput.on('click',function(){
        validationMsgBox.remove();
    });

//    applicationForm.find('.containerBaseData div.right').on('click', function(event) {
//        if ($(event.target).hasClass('delete')) {
//            return;
//        }
//        fileInput.trigger('click');
//    });

    $(document).ready(function() {
        $('#listFilterLayersets .checkWrapper input.checkbox, #containerLayout .checkWrapper input.checkbox').each(function() {
            var self = this;
            initCheckbox.call(this);
            $(self).on("change", function(e) {
                $.ajax({
                    url: $(self).attr('data-url'),
                    type: 'POST',
                    data: {
                        'id': $(self).attr('data-id'),
                        'enabled': $(self).is(":checked")
                    },
                    success: function(data) {
                        if (data.success) {
                            if (data.success.enabled.after !== $(self).is(":checked"))
                                alert("Cannot be changed!");
                        } else if (data.error) {
                            alert(data.error);
                        }
                    }
                });
            });
        });
    });

    // Custom CSS editor
    (function($) {
        var textarea = $('#application_custom_css');
        if(!textarea.length) return;
        codeMirror = CodeMirror.fromTextArea(textarea[0], {
            mode: 'css',
            keyMap: 'sublime',
            styleActiveLine: true,
            lineNumbers: true,
            indentUnit: 2,
            tabSize: 4,
            indentWithTabs: false,
            lineWrapping: true,
            matchBrackets: true,
            theme: 'neo'
        });

        codeMirror.on('change', function() {
            codeMirror.save();
        });

        $('#tabCustomCss').on('click', function() {
            codeMirror.refresh();
            codeMirror.focus();
        });
    })(jQuery);
    (function($) {
        var tabkey = 'manager_active_tab';
        if (typeof(Storage) !== "undefined" && window.sessionStorage && window.sessionStorage[tabkey]) {
            var id = window.sessionStorage[tabkey];
            $(".tabContainer .tab#" + id + ", .tabContainerAlt .tab#" + id).click();
        }
        $(".tabContainer, .tabContainerAlt").on('click', '.tab', function() {
            if (typeof(Storage) !== "undefined" && window.sessionStorage) {
                window.sessionStorage.setItem(tabkey, $(this).attr('id'));
            }
        });
    })(jQuery);
});


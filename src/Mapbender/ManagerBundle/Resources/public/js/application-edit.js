$(function() {
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
                                document.location.href = document.location.href;
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            document.location.href = document.location.href;
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
                    document.location.href = document.location.href;
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                document.location.href = document.location.href;
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
                                    document.location.href = document.location.href;
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                document.location.href = document.location.href;
                            },
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

    // Layout - Elements ---------------------------------------------------------------------------
    var submitHandler = function(e) {
        $.ajax({
            url: $(this).attr('action'),
            data: $(this).serialize(),
            type: 'POST',
            statusCode: {
                200: function(response) {
                    $("#popupSubContent").html(response);
                    var subTitle = $("#popupSubContent").find("#form_title").val();
                    $("#popupSubTitle").text(" - " + subTitle);
                    $("#popup").find(".buttonYes, .buttonBack").show();
                },
                201: function() {
                    popup.close();
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 10);

                },
                205: function() {
                    popup.close();
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 10);
                }
            }

        });
        e.preventDefault();
        return false;
    };

    function loadElementFormular() {
        var url = $(this).attr("href");
        if (url) {
            $.ajax({
                url: url,
                type: "GET",
                complete: function(data) {
                    if (data != undefined) {
                        var pop = $(".popup");
                        var popupContent = $(".popupContent");
                        var contentWrapper = pop.find(".contentWrapper");

                        if (contentWrapper.get(0) == undefined) {
                            popupContent.wrap('<div class="contentWrapper"></div>');
                            contentWrapper = pop.find(".contentWrapper");
                        }
                        popupContent.hide();
                        var subContent = contentWrapper.find(".popupSubContent");

                        if (subContent.get(0) == undefined) {
                            contentWrapper.append('<div class="popupSubContent"></div>');
                            subContent = contentWrapper.find('.popupSubContent');
                        }
                        subContent.html(data.responseText);

                        var subTitle = subContent.find("#form_title").val();
                        $(".popupSubTitle").text(" - " + subTitle);
                        $(".popup").find(".buttonYes, .buttonBack").show();
                        subContent.on('submit', 'form', submitHandler);
                    }
                }
            });
        }

        return false;
    }

    $(".addElement").bind("click", function(event) {
        var self = $(this);
        if (popup) {
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mb.manager.components.popup.add_element.title"),
            subtitle: " - " + Mapbender.trans(self.parent().siblings(".subTitle").text()),
            closeOnOutsideClick: true,
            cssClass: "elementPopup",
            height: 550,
            width: 550,
            content: [
                $.ajax({
                    url: self.attr("href"),
                    complete: function() {
                        var curPopup = $(".popup");

                        curPopup.find(".buttonYes, .buttonBack").hide();
                        curPopup.find(".chooseElement").on("click", loadElementFormular);
                    }
                })
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.add_element.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        $("#elementForm").data('dirty', false);
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.add_element.btn.ok"),
                    cssClass: 'button buttonYes right',
                    callback: function() {
                        $("#elementForm")
                                .data('dirty', false)
                                .submit();
                        return false;
                    }
                },
                'back': {
                    label: Mapbender.trans("mb.manager.components.popup.add_element.btn.back"),
                    cssClass: 'button left buttonBack',
                    callback: function() {
                        $("#elementForm").data('dirty', false);
                        $(".popupSubContent").remove();
                        $(".popupSubTitle").text("");
                        $(".popup").find(".buttonYes, .buttonBack").hide();
                        $(".popupContent").show();
                    }
                }
            }
        });

        var onChange = function(event) {
            $('#elementForm', popup.$element).data('dirty', true);
            popup.$element.off('change', onChange);
        };
        popup.$element.on('change', onChange);
        popup.$element.on('close', function(event, token) {
            if (true === $('#elementForm', popup.$element).data('dirty')) {
                if (!confirm('Ignore Changes?')) {
                    token.cancel = true;
                }
            }
        });
        return false;
    });

    // Edit element
    $(".editElement").bind("click", function() {
        var self = $(this);

        if (popup) {
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mb.manager.components.popup.edit_element.title"),
            closeOnOutsideClick: true,
            height: 550,
            width: 550,
            content: [
                $.ajax({
                    url: self.attr("data-url"),
                    complete: function() {
                        $(".popupContent").removeClass("popupContent")
                                .addClass("popupSubContent")
                                .find('form').submit(submitHandler);
                    }
                })
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.edit_element.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        $("#elementForm").data('dirty', false);
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.edit_element.btn.ok"),
                    cssClass: 'button right',
                    callback: function() {
                        $("#elementForm")
                                .data('dirty', false)
                                .submit();
                    }
                }
            }
        });

        var onChange = function(event) {
            $('#elementForm', popup.$element).data('dirty', true);
            popup.$element.off('change', onChange);
        };
        popup.$element.on('change', onChange);
        popup.$element.on('close', function(event, token) {
            if (true === $('#elementForm', popup.$element).data('dirty')) {
                if (!confirm('Ignore Changes?')) {
                    token.cancel = true;
                }
            }
        });
        return false;
    });

    // Element security
    $(".secureElement").bind("click", function() {
        var self = $(this),
                toremove = null;

        if (popup) {
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
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
        var self = $(this);
        var content = $('<div/>').text(self.attr('title')).html();

        if (popup) {
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mb.manager.components.popup.delete_element.title"),
            subTitle: " - " + Mapbender.trans("mb.manager.components.popup.delete_element.subtitle"),
            closeOnOutsideClick: true,
            content: [content + "?"],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_element.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_element.btn.ok"),
                    cssClass: 'button right',
                    callback: function() {
                        $.ajax({
                            url: self.attr('data-url'),
                            data: {'id': self.attr('data-id')},
                            type: 'POST',
                            success: function(data) {
                                window.location.reload();
                            }
                        });
                    }
                }
            }
        });
        return false;
    });

    // Layers --------------------------------------------------------------------------------------
    function addOrEditLayerset(edit) {
        var self = $(this);

        if (popup) {
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
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
        var self = $(this);
        if (popup) {
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mb.manager.components.popup.delete_layerset.title"),
            subTitle: " - " + $(this).siblings("legend").text(),
            closeOnOutsideClick: true,
            content: [
                $.ajax({url: self.attr("href")})
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_layerset.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_layerset.btn.ok"),
                    cssClass: 'button right',
                    callback: function() {
                        $("#deleteLaysersetForm").submit();
                    }
                }
            }
        });
        return false;
    });
    // Add Instance Action
    $(".addInstance").bind("click", function(event) {
        var self = $(this);
        if (popup) {
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mb.manager.components.popup.add_instance.title"),
            subTitle: " - " + self.parent().siblings(".subTitle").text(),
            closeOnOutsideClick: true,
            height: 400,
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
        var self = $(this);
        var content = self.attr('title');


        if (popup) {
            popup = popup.destroy();
        }

        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mb.manager.components.popup.delete_instance.title"),
            subtitle: " - layerset",
            closeOnOutsideClick: true,
            content: [content + "?"],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_instance.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_instance.btn.ok"),
                    cssClass: 'button right',
                    callback: function() {
                        $.ajax({
                            url: self.attr('data-url'),
                            data: {
                                'slug': self.attr('data-slug'),
                                'id': self.attr('data-id')
                            },
                            type: 'POST',
                            success: function(data) {
                                window.location.reload();
                            }
                        });
                    }
                }
            }
        });
        return false;
    });

    var applicationForm = $('form[name=application]');
    var screenShot = applicationForm.find('.screenshot_img');
    var deleteScreenShotButton = screenShot.find('.delete');
    var uploadButton = applicationForm.find('.upload_button');
    var fileInput = applicationForm.find('#application_screenshotFile');
    var validationMsgBox = applicationForm.find('span.validationMsgBox');
    var maxFileSize = applicationForm.find('#application_maxFileSize').val();
    var fileNameTmp = "";


    fileInput.on('mouseover', function() {
        uploadButton.addClass('hover');
    }).on('mouseout', function() {
        uploadButton.removeClass('hover');
    }).on('change', function(e) {
        $('input[name="application[removeScreenShot]"]').val(0);

        var file =  this.files;
        fileName = $(e.currentTarget).val().replace(/^.+(\\)/, '');

        if(fileName === ""){
            if (fileNameTmp === ""){
                fileName = Mapbender.trans('mb.manager.admin.application.upload.label');
            }else{
               fileName = fileNameTmp;
            }
        }else{
            fileNameTmp = fileName;
        }

        if (fileName.length > 20) {
                                console.log("dasdasdasdasdas");
            fileName = fileName.substring(0, 20) + "...";
        }

        if (file && file[0]) {
            var reader = new FileReader();

            if (file[0].type.match('image/')){
                if (file[0].size <= 2097152){
                    screenShot.find('div.messageBox').remove();
                    validationMsgBox.remove();
                    if (screenShot.find('div.cell').length === 0 ){
                        screenShot.removeClass('default iconAppDefault');
                        screenShot.append('<div class= \"cell\"><img src= \"\" alt="Load" class="screenshot" /></div>');
                    }
                    reader.onload = function (e) {
                        screenShot.find('img').attr('src', e.target.result);
                    }
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

        $('.upload_label').html(fileName);
    });



    screenShot.mouseenter('mouseover', function() {
        deleteScreenShotButton.removeClass('hidden');
    }).mouseleave('mouseout', function() {
        deleteScreenShotButton.addClass('hidden');
    });

    deleteScreenShotButton.on('click', function() {
        screenShot.find('img.screenshot').remove();
        deleteScreenShotButton.remove();
        screenShot.addClass('default').addClass('iconAppDefault');
        applicationForm.find('.upload_label').html(Mapbender.trans("mb.manager.upload.label_delete"));
        applicationForm.find('input[name="application[removeScreenShot]"]').val(1);
    });


    fileInput.on('click',function(){
        validationMsgBox.remove();
    });

    applicationForm.find('.containerBaseData div.right').on('click', function(event) {
        if ($(event.target).hasClass('delete')) {
            return;
        }
        fileInput.trigger('click');
    });

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
            matchBrackets: true,
            lineNumbers: true,
            theme: 'neo'
        });

        codeMirror.on('change', function() {
            codeMirror.save();
        });

        $('#tabCustomCss').on('click', function() {
            codeMirror.refresh();
            codeMirror.focus();
        });
    })(jQuery)

//
//    var submitImportExport = function(e) {
//        console.log($(this), e);
//        $.ajax({
//            url: $(e.target).attr('action'),
//            data: $(e.target).serialize(),
//            type: 'POST',
//            statusCode: {
//                200: function(response) {
//                    alert();
//                    $("#popupSubContent").html(response);
//                    var subTitle = $("#popupSubContent").find("#form_title").val();
//                    $("#popupSubTitle").text(" - " + subTitle);
//                    $("#popup").find(".buttonYes, .buttonBack").show();
//                },
//                201: function() {
//                    popup.close();
//                    window.setTimeout(function() {
//                        window.location.reload();
//                    }, 10);
//
//                },
//                205: function() {
//                    popup.close();
//                    window.setTimeout(function() {
//                        window.location.reload();
//                    }, 10);
//                }
//            }
//
//        });
//        e.preventDefault();
//        return false;
//    };
//
//    $("#application-export").bind("click", function() {
//        var self = $(this);
//        if (popup) {
//            popup = popup.destroy();
//        }
//        popup = new Mapbender.Popup2({
//            title: Mapbender.trans("mb.manager.application.popup.export.title"),
//            closeOnOutsideClick: true,
//            height: 350,
//            width: 550,
//            content: [
//                $.ajax({
//                    url: self.attr("href"),
//                    complete: function() {
//                        var pupCont = $(".popupContent");
//                        pupCont.removeClass("popupContent").addClass("popupSubContent");
//                        $(".contentItem .right", pupCont).addClass('hidden');
//                        pupCont.submit(submitImportExport);
//                    }
//                })
//            ],
//            buttons: {
//                'cancel': {
//                    label: Mapbender.trans("mb.manager.application.popup.export.btn.cancel"),
//                    cssClass: 'button buttonCancel critical right',
//                    callback: function() {
////                        $("#elementForm").data('dirty', false);
//                        this.close();
//                    }
//                },
//                'ok': {
//                    label: Mapbender.trans("mb.manager.application.popup.export.btn.ok"),
//                    cssClass: 'button right',
//                    callback: function() {
//                        $('form[name="application-export"]', popup.$element).submit();
////                        window.setTimeout(function() {
////                            window.location.reload();
////                        }, 50);
//                    }
//                }
//            }
//        });
//        return false;
//    });
});

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
                        success: function(data) {
                            if (data.error && data.error !== '') {
                                document.location.reload();
                            }
                        },
                        error: function() {
                            document.location.reload();
                        }
                    });
                }
            });
        }
    });
    $(".regionProperties").each(function() {
        function updateGroupIcons() {
            function updateWrapper() {
                var $cb = $('input[type="radio"]', this);
                $(this)
                    .toggleClass('checked', $cb.prop('checked'))
                    .toggleClass('disabled', $cb.prop('disabled'))
                ;
            }
            $('.radioWrapper', this).each(updateWrapper);
        }
        function onClick() {
            var $clickedRadio = $('input[type="radio"]', this);
            $clickedRadio.prop('checked', true);
            updateGroupIcons.call($(this).parent());
        }
        updateGroupIcons.call(this);
        $(this).on('click', '.radioWrapper', onClick);
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

    function startEditElement(formUrl, strings, extraButtons) {
        $.ajax(formUrl).then(function(response) {
            var popup = new popupCls({
                title: Mapbender.trans(strings.title || 'mb.manager.components.popup.edit_element.title'),
                subTitle: strings.subTitle || '',
                modal: true,
                closeOnOutsideClick: false,
                destroyOnClose: true,
                cssClass: "elementPopup",
                content: response,
                buttons: (extraButtons || []).slice().concat([
                    {
                        label: Mapbender.trans(strings.save || 'mb.manager.components.popup.edit_element.btn.ok'),
                        cssClass: 'button',
                        callback: function() {
                            elementFormSubmit(this.$element, formUrl);
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
            popup.$element.on('change', function() {
                $('form', popup.$element).data('dirty', true);
            });
            popup.$element.on('close', function(event, token) {
                if ($('form', popup.$element).data('dirty')) {
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
            var popup = new popupCls({
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

    $(".addElement").bind("click", function() {
        var regionName = $('.subTitle', $(this).closest('.region')).first().text();
        startElementChooser(regionName, $(this).attr('href'));
        return false;
    });

    $(".editElement").bind("click", function() {
        startEditElement($(this).attr('data-url'), {});
        return false;
    });

    function elementFormSubmit(scope, submitUrl) {
        var $form = $('form', scope),
            data = $form.serialize(),
            url = submitUrl || $form.attr('action'),
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
                    $form.parent().html(data);
                } else {
                    $form.data('dirty', false);
                    self.close();
                    window.location.reload();
                }
            }
        });
    }

    // Delete element
    $('.removeElement').bind("click", function() {
        var $el = $(this);
        Mapbender.Manager.confirmDelete($el, $el.attr('data-url'), {
            title: 'mb.manager.components.popup.delete_element.title',
            confirm: 'mb.manager.components.popup.delete_element.btn.ok',
            cancel: 'mb.manager.components.popup.delete_element.btn.cancel',
            subTitle: 'mb.manager.components.popup.delete_element.subtitle'
        });
        return false;
    });

    // Layers --------------------------------------------------------------------------------------
    function addOrEditLayerset() {
        var self = $(this);
        var isEdit = self.hasClass("editLayerset");
        var popupTitle = isEdit ? "mb.manager.components.popup.add_edit_layerset.title_edit"
                                : "mb.manager.components.popup.add_edit_layerset.title_add";
        $.ajax({url: self.attr("href")}).then(function(html) {
            new popupCls({
                title: Mapbender.trans(popupTitle),
                closeOnOutsideClick: true,
                destroyOnClose: true,
                content: [html],
                buttons: [
                    {
                        label: Mapbender.trans("mb.manager.components.popup.add_edit_layerset.btn.ok"),
                        cssClass: 'button',
                        callback: function() {
                            $('form', this.$element).submit();
                        }
                    },
                    {
                        label: Mapbender.trans("mb.manager.components.popup.add_edit_layerset.btn.cancel"),
                        cssClass: 'button buttonCancel critical',
                        callback: function() {
                            this.close();
                        }
                    }
                ]
            });
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
            Mapbender.Manager.confirmDelete($el, actionUrl, strings, content);
        });
        return false;
    });
    // Add Instance Action
    $(".addInstance").bind("click", function() {
        var self = $(this);
        var layersetTitle = self.closest('.filterItem', '.listFilterContainer').find('.subTitle').first().text();
        new popupCls({
            title: Mapbender.trans("mb.manager.components.popup.add_instance.title"),
            subTitle: " - " + layersetTitle,
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
        Mapbender.Manager.confirmDelete($el, $el.attr('data-url'), {
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
    var fileInput = applicationForm.find('#application_screenshotFile');
    var fileGroup = fileInput.closest('.upload');
    function setFileError(message) {
        var $box = $('.validationMsgBox', fileGroup);
        if (!$box.length) {
            $box = $('<span/>').addClass('validationMsgBox');
            fileInput.after($box);
        }
        $box.text(message || '');
        $box.toggle(!!message);
    }
    var maxFileSize = applicationForm.find('#application_maxFileSize').val();
    var minWidth = applicationForm.find('#application_screenshotWidth').val();
    var minHeight = applicationForm.find('#application_screenshotHeight').val();

    fileInput.on('change', function(e) {
        setUploadFilename(e);

        var file = this.files;
        var reader = new FileReader();
        var img = new Image();
        var src = "";
        var validationMessage;

        img.onload = function() {
            if (img.width >= minWidth && img.height >= minHeight) {
                setFileError(null);
                screenShotImg.attr('src', src);
                screenShotImg.before('<div class="delete button critical hidden">X</div>');
                deleteScreenShotButtonInit();
                screenShot.removeClass('default');
                applicationForm.find('input[name="application[removeScreenShot]"]').val(0);
            } else {
                validationMessage = Mapbender.trans('mb.core.entity.app.screenshotfile.resolution.error',
                    {'screenshotWidth':minWidth, 'screenshotHeight':minHeight ,'uploadWidth': img.width, 'uploadHeighth': img.height });
                setFileError(validationMessage);
            }
        };

         if (file && file[0]) {
            if (file[0].type.match('image/')){
                var uploadFileSize = file[0].size;
                if (uploadFileSize <= 2097152) {
                    reader.onload = function (e) {
                        img.src = src = e.target.result;
                    };

                    reader.readAsDataURL(file[0]);
                } else {
                    validationMessage = Mapbender.trans('mb.core.entity.app.screenshotfile.error', {'maxFileSize':maxFileSize, 'uploadFileSize': uploadFileSize });
                    setFileError(validationMessage);
                }
             } else {
                 validationMessage = Mapbender.trans('mb.core.entity.app.screenshotfile.format_error');
                 setFileError(validationMessage);
            }
        }
    });

    var setUploadFilename = function(e){
        var fileName = $(e.currentTarget).val().replace(/^.+(\\)/, '');
        var displayFilename = fileName || Mapbender.trans('mb.manager.admin.application.upload.label');
        $('.upload_label').text(displayFilename);
    };

    var deleteScreenShotButtonInit = function() {

        var deleteButton = screenShot.find('.delete');
        screenShot.hover(function() {
            deleteButton.toggleClass('hidden', $(this).hasClass('default'));
        }, function() {
            deleteButton.addClass('hidden');
        });

        deleteButton.on('click', function() {
            screenShot.addClass('default');
            screenShotImg.attr('src',"");
            applicationForm.find('.upload_label').html(Mapbender.trans("mb.manager.upload.label_delete"));
            applicationForm.find('input[name="application[removeScreenShot]"]').val(1);
            deleteButton.addClass('hidden');
        });
        return deleteButton;
    };

    deleteScreenShotButtonInit();

    $(document).ready(function() {
        $('.application-component-table tbody .iconColumn input.checkbox[data-url]').each(function() {
            var self = this;
            initCheckbox.call(this);
            $(self).on("change", function() {
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
        var codeMirror = CodeMirror.fromTextArea(textarea[0], {
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


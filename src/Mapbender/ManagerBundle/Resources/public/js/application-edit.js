$(function() {
    var popupCls = Mapbender.Popup;
    function _handleLoginRedirect(html) {
        if (/^<(!DOCTYPE|html)/i.test(html)) {
            // Redirected to login
            // Reload whole page; this will (again) redirect to login
            window.location.reload();
            return true;
        } else {
            return false;
        }
    }
    function _formJax(options) {
        return $.ajax(options).then(function(response) {
            if (_handleLoginRedirect(response)) {
                return [];
            }

            var $markup = $(response);
            // support top-level form tag(s)
            var forms = $markup.filter('form').get();
            if (!forms.length) {
                // also support form(s) wrapped in something else
                forms = $('form', $markup).get();
            }
            for (var i = 0; i< forms.length; ++i) {
                var form = forms[i];
                if (!form.getAttribute('action')) {
                    // amend missing "action" attribute to match url of form source
                    form.setAttribute('action', options.url);
                }
            }
            return $markup.get();
        });
    }
    function confirmDiscard(e) {
        var $form = $('form', $(this).closest('.popup'));
        if ($form.data('dirty') && !$form.data('discard')) {
            // @todo: translate
            if (!confirm(Mapbender.trans('mb.manager.confirm_form_discard'))) {
                e.stopPropagation();
                return false;
            }
            $form.data('discard', true);
        }
        return true;
    }
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
    $('.regionProperties[data-url]').on('change', ':input', function() {
        var $rprop = $(this).closest('.regionProperties');
        var $inputs = $(':input', $rprop).filter(function() {
            var $input = $(this);
            return $input.attr('type') !== 'radio' || $input.is(':checked');
        });
        // Include CSRF token, so server can run validation
        $inputs = $inputs.add($('input[name="application[_token]"]', $rprop.closest('form')));
        var formData = $inputs.serializeArray();
        $.ajax($rprop.attr('data-url'), {
            method: 'POST',
            data: formData
        });
    });

    $("table.layersetTable tbody").sortable({
        connectWith: "table.layersetTable tbody",
        items: "tr.sourceinst[data-href]",
        distance: 20,
        stop: function(event, ui) {
            var $item = $(ui.item);
            var $siblings = $('tr:not(.dummy)', $item.parent());
            $.ajax({
                url: $item.attr("data-href"),
                type: "POST",
                data: {
                    number: $siblings.index($item),
                    new_layersetId: $item.closest('table.layersetTable[data-id]').attr("data-id")
                }
            });
        }
    });

    function startEditElement(formUrl, strings, extraButtons) {
        _formJax({url: formUrl}).then(function(nodes) {
            if (!nodes || !nodes.length) {
                return;
            }
            var popup = new popupCls({
                title: Mapbender.trans(strings.title || 'mb.manager.components.popup.edit_element.title'),
                subTitle: strings.subTitle || '',
                modal: true,
                cssClass: "elementPopup",
                content: [nodes],
                buttons: (extraButtons || []).slice().concat([
                    {
                        label: Mapbender.trans(strings.save || 'mb.actions.save'),
                        cssClass: 'button',
                        callback: function() {
                            elementFormSubmit(this.$element, formUrl).then(function(success) {
                                if (success) {
                                    window.location.reload();
                                }
                            })
                        }
                    },
                    {
                        label: Mapbender.trans(strings.cancel || 'mb.actions.cancel'),
                        cssClass: 'button buttonCancel critical popupClose'
                    }
                ])
            });

            popup.$element.on('change', function() {
                var $form = $('form', popup.$element);
                $form.data('dirty', true);
                $form.data('discard', false);
            });
            popup.$element.on('close', function(event, token) {
                if (!confirmDiscard.call(this, event)) {
                    token.cancel = true;
                }
            });
        });
    }

    function startElementChooser(regionName, listUrl) {
        var title ='mb.manager.components.popup.add_element.title';
        $.ajax({
            url: listUrl
        }).then(function(response) {
            if (_handleLoginRedirect(response)) {
                return;
            }
            var popup = new popupCls({
                title: Mapbender.trans(title),
                subTitle: ' - ' + regionName,
                modal: true,
                content: response,
                cssClass: "elementPopup",
                buttons: [
                    {
                        label: Mapbender.trans('mb.actions.cancel'),
                        cssClass: 'button buttonCancel critical popupClose'
                    }
                ]
            });
            popup.$element.on('click', '.chooseElement', function(e) {
                e.preventDefault();
                var elementTitle = $(this).attr('data-element-title');
                var editStrings = {
                    title: title,
                    subTitle: ' - ' + regionName + ' - ' + elementTitle,
                    save: 'mb.actions.add',
                    cancel: 'mb.actions.cancel'
                };

                startEditElement($(this).attr('href'), editStrings, [
                    {
                        label: Mapbender.trans('mb.actions.back'),
                        cssClass: 'button buttonBack',
                        callback: function(e) {
                            if (confirmDiscard.call(e.target, e)) {
                                startElementChooser(regionName, listUrl);
                            }
                        }
                    }
                ]);
                return false;
            });
        });
    }

    $(".addElement").bind("click", function(e) {
        e.preventDefault();
        var regionName = $('.subTitle', $(this).closest('.region')).first().text();
        startElementChooser(regionName, $(this).attr('href'));
        return false;
    });

    $(".editElement").bind("click", function(e) {
        e.preventDefault();
        startEditElement($(this).attr('data-url'), {});
        return false;
    });

    function elementFormSubmit(scope, submitUrl) {
        var $form = $('form', scope),
            data = $form.serialize(),
            url = submitUrl || $form.attr('action')
        ;

        return $.ajax({
            url: url,
            method: 'POST',
            data: data
        }).then(
            function(data) {
                if (data.length > 0) {
                    var dirty = $form.data('dirty');
                    var body = $form.parent();
                    body.html(data);
                    $form = $('form', body);
                    $form.data('dirty', dirty);
                    $form.data('discard', false);
                    return false;
                } else {
                    return true;
                }
            },
            function (e, statusCode, message) {
                Mapbender.error(Mapbender.trans("mb.application.save.failure.general") + ' ' + message);
            }
        );
    }

    // Delete element
    $('.removeElement').bind("click", function() {
        var $el = $(this);
        Mapbender.Manager.confirmDelete($el, $el.attr('data-url'), {
            title: 'mb.manager.components.popup.delete_element.title',
            confirm: "mb.actions.delete",
            cancel: "mb.actions.cancel"
        });
        return false;
    });

    // Layers --------------------------------------------------------------------------------------
    $(document).on('click', '.-fn-add-layerset, .-fn-edit-layerset', function() {
        var $this = $(this);
        var isEdit = !$this.hasClass("-fn-add-layerset");
        var popupTitle = isEdit ? "mb.manager.components.popup.add_edit_layerset.title_edit"
                                : "mb.manager.components.popup.add_edit_layerset.title_add";
        var confirmText = isEdit ? 'mb.actions.save'
                                 : 'mb.actions.add';
        var url = $this.attr('data-url');
        _formJax({url: url}).then(function(nodes) {
            if (!nodes || !nodes.length) {
                return;
            }
            new popupCls({
                title: Mapbender.trans(popupTitle),
                content: [nodes],
                buttons: [
                    {
                        label: Mapbender.trans(confirmText),
                        cssClass: 'button',
                        callback: function() {
                            $('form', this.$element).submit();
                        }
                    },
                    {
                        label: Mapbender.trans('mb.actions.cancel'),
                        cssClass: 'button buttonCancel critical popupClose'
                    }
                ]
            });
        });
    });

    // Delete layerset Action
    $(document).on('click', '.-fn-delete-layerset', function() {
        var strings = {
            title: 'mb.manager.components.popup.delete_layerset.title',
            confirm: 'mb.actions.delete',
            cancel: 'mb.actions.cancel'
        };
        var $el = $(this);
        var actionUrl = $el.attr('data-url');
        $.ajax({url: actionUrl}).then(function(content) {
            Mapbender.Manager.confirmDelete($el, actionUrl, strings, content);
        });
    });
    // Add Instance Action
    $(document).on('click', '.-fn-add-instance', function(e) {
        var $target = $(this);
        var layersetTitle = $target.closest('table').attr('data-layerset-title');
        $.ajax({url: $target.attr("data-url")}).then(function(response) {
            if (_handleLoginRedirect(response)) {
                return;
            }
            new popupCls({
                title: Mapbender.trans("mb.manager.components.popup.add_instance.title"),
                subTitle: " - " + layersetTitle,
                cssClass: 'new-instance-select',
                content: response,
                buttons: {
                    'cancel': {
                        label: Mapbender.trans('mb.actions.cancel'),
                        cssClass: 'button buttonCancel critical popupClose'
                    }
                }
            });
        });
        return false;
    });
    // Delete instance
    $('.removeInstance').bind("click", function() {
        var $el = $(this);
        Mapbender.Manager.confirmDelete($el, $el.attr('data-url'), {
            title: 'mb.manager.components.popup.delete_instance.title',
            confirm: 'mb.actions.delete',
            cancel: 'mb.actions.cancel'
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
        var $box = $('.alert-danger', fileGroup);
        if (!$box.length) {
            $box = $(document.createElement('div')).addClass('alert alert-danger');
            fileGroup.append($box);
        }
        $box.text(message || '');
        $box.toggle(!!message);
    }
    var maxFileSize = fileInput.attr('data-max-size');
    var minWidth = fileInput.attr('data-min-width');
    var minHeight = fileInput.attr('data-min-height');

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
        $('.application-component-table tbody .iconColumn input[type="checkbox"][data-url]').each(function() {
            var self = this;
            initCheckbox.call(this);
            $(self).on("change", function() {
                $.ajax({
                    url: $(self).attr('data-url'),
                    type: 'POST',
                    data: {
                        'id': $(self).attr('data-id'),
                        'enabled': $(self).is(":checked")
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
    $('.regionProperties [data-toggle-target]').on('click', function() {
        $($(this).attr('data-toggle-target')).toggleClass('hidden');
    });
});

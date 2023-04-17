;!(function($) {
    "use strict";
    $.widget("mapbender.mbViewManager", {
        options: {
            publicEntries: null,
            privateEntries: null,
            allowAnonymousSave: false
        },
        mbMap: null,
        elementUrl: null,
        referenceSettings: null,
        defaultSavePublic: false,
        deleteConfirmationContent: null,
        updateContent: null,
        infoContent: null,
        mapPromise: null,
        baseUrl: null,
        useDialog_: null,
        popup_: null,
        recordPopup_: null,
        csrfToken: null,


        _create: function() {
            var self = this;
            this.useDialog_ = !this.element.closest('.sideContent, .mobilePane').length;
            this.baseUrl = window.location.href.replace(/[?#].*$/, '');
            this._toggleEnabled(false);
            this.elementUrl = [Mapbender.configuration.application.urls.element, this.element.attr('id')].join('/');
            this.mapPromise = Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.referenceSettings = mbMap.getModel().getConfiguredSettings();
                self._setup(mbMap);
                return mbMap;
            });
            this.defaultSavePublic = (this.options.publicEntries === 'rw') || (this.options.publicEntries === 'rwd');
            this.deleteConfirmationContent = $('.-js-delete-confirmation-content', this.element)
                .remove().removeClass('hidden').html()
            ;
            var $updateInfoCommon = $('.-js-update-content', this.element)
                .remove().removeClass('hidden')
            ;
            var $infoContent = $updateInfoCommon.clone();
            $('input', $infoContent).prop('readonly', true);
            $('.-fn-update', $infoContent).remove();
            this.updateContent = $updateInfoCommon.html();
            this.infoContent = $infoContent.html();
            const $form = this.element.find('form');
            $form.on('submit', function(e) {
                e.preventDefault();
                this._saveNew();
            }.bind(this));
            this.csrfToken = $form.attr('data-token');
            this._load();   // Does not need map element to finish => can start asynchronously
        },
        _setup: function(mbMap) {
            this.mbMap = mbMap;
            this._initEvents();
            this._toggleEnabled(true);
        },
        _toggleEnabled: function(enabled) {
            $('.-fn-save-new', this.element.prop('disabled', !enabled));
            $('input[name="title"]', this.element).prop('disabled', !enabled);
        },
        open: function(callback) {
            this.closeCallback = callback || null;
             if (!this.popup_) {
                 this.popup_ = new Mapbender.Popup({
                     modal: false,
                     detachOnClose: false,
                     destroyOnClose: false,
                     draggable: true,
                     resizable: true,
                     title: this.element.attr('data-title'),
                     content: this.element.get(0),
                     cssClass: 'mbViewManager-dialog',
                     buttons: [
                         {
                             label: Mapbender.trans('mb.actions.close'),
                             cssClass: 'popupClose btn btn-sm btn-success'
                         }
                     ]
                 });
                 var self = this;
                 this.popup_.$element.on('close', function() {
                     self.close();
                 });
             }
             this.popup_.$element.show();
             this.popup_.focus();
        },
        close: function() {
            this.popup_.$element.hide();
            if (this.recordPopup_ && this.recordPopup_.$element) {
                this.recordPopup_.close();
            }
            this.recordPopup_ = null;
            if (this.closeCallback) {
                (this.closeCallback)();
                this.closeCallback = null;
            }
        },
        _initEvents: function() {
            var self = this;
            this.element.on('click', '.-fn-save-new', function() {
                self._saveNew().then(function() {
                    self._updatePlaceholder();
                });
            });
            this.element.on('click', '.-fn-apply', function(evt) {
                evt.preventDefault();
                var settings = self._extractLinkSettings(this);
                self._apply(settings);
                var $marker = $('.recall-marker', $(this).closest('tr'));
                $('.recall-marker', self.element).not($marker).css({opacity: ''});
                $marker.css({opacity: '1'});
                self.mbMap.element.one('mbmapviewchanged mb.sourcenodeselectionchanged mbmapsourcechanged', function() {
                    $marker.animate({opacity: '0'});
                });
            });
            this.element.on('click', 'tr .-js-forward-to-apply', function() {
                $('.-fn-apply', $(this).closest('tr')).trigger('click');
            });
            this.element.on('click', '.-fn-delete', function() {
                var $row = $(this).closest('tr');
                var rowId = $row.attr('data-id');
                self._confirm($row, self.deleteConfirmationContent).then(function() {
                    self._delete(rowId, self.csrfToken).then(function() {
                        $row.remove();
                        self._updatePlaceholder();
                    });
                });
            });
            this.element.on('click', '.-fn-open-update, .-fn-open-info', function() {
                var $clickTarget = $(this);
                var $row = $clickTarget.closest('tr');
                var recordId = !$clickTarget.is('.-fn-open-info') && $row.attr('data-id') || null;
                self._openUpdateOrInfo($row, recordId);
                return false;   // Avoid re-focusing dialog
            });
        },
        _load: function() {
            var $loadingPlaceholder = $('.-fn-loading-placeholder', this.element)
            var self = this;
            var listingPromise = $.ajax([this.elementUrl, 'listing'].join('/'));
            $.when(listingPromise, this.mapPromise)
                .then(function(response) {
                    var $content = $(response[0]);
                    $('a.-fn-apply', $content).each(function() {
                        self._updateLinkUrl(this);
                    });
                    $loadingPlaceholder.replaceWith($content);
                    self._updatePlaceholder();
                }, function() {
                    $loadingPlaceholder.hide()
                })
            ;
        },
        _updateLinkUrl: function(link) {
            var settings = this._extractLinkSettings(link);
            var params = this.mbMap.getModel().encodeSettingsDiff(settings);
            var hash = this.mbMap.getModel().encodeViewParams(settings.viewParams);
            var url = [Mapbender.Util.addUrlParams(this.baseUrl, params).replace(/\/?\?$/, ''), hash].join('#');
            $(link).attr('href', url);
        },
        _replace: function($row, $form, id) {
            if (!id) {
                throw new Error("Cannot update record without id");
            }
            var data = Object.assign(this._getCommonSaveData(), {
                title: $('input[name="title"]', $form).val() || $row.attr('data-title')
            });
            var params = {id: id};
            var self = this;
            return $.ajax([[this.elementUrl, 'save'].join('/'), $.param(params)].join('?'), {
                method: 'POST',
                data: data
            }).then(function(response) {
                var newRow = $.parseHTML(response);
                $('a.-fn-apply', $(newRow)).each(function() {
                    self._updateLinkUrl(this);
                });
                $row.replaceWith(newRow);
                self._flash($(newRow), '#88ff88');
            });
        },
        _saveNew: function() {
            var $titleInput = $('input[name="title"]', this.element);
            var title = $titleInput.val();
            if (!title) {
                var $titleGroup = $titleInput.closest('.form-group');
                $titleGroup.addClass('has-error');
                $titleInput.one('keydown', function() {
                    $titleGroup.removeClass('has-error');
                });
                return $.Deferred().reject().promise();
            }
            var data = Object.assign(this._getCommonSaveData(), {
                title: title,
                savePublic: this._getSavePublic()
            });

            var self = this;
            var $tbody = $('table tbody', this.element);
            return $.ajax([this.elementUrl, 'save'].join('/'), {
                method: 'POST',
                data: data
            }).then(function(response) {
                var newRow = $.parseHTML(response);
                $('a.-fn-apply', $(newRow)).each(function() {
                    self._updateLinkUrl(this);
                });
                var insertAfter = !data.savePublic && $('tr[data-visibility-group="public"]', $tbody).get(-1);
                if (insertAfter) {
                    $(insertAfter).after(newRow);
                } else {
                    $tbody.prepend(newRow);
                }
                self._flash($(newRow), '#88ff88');
            });
        },
        _delete: function(id, csrfToken) {
            var params = {id: id};
            return $.ajax([[this.elementUrl, 'delete'].join('/'), $.param(params)].join('?'), {
                method: 'DELETE',
                data: {token: csrfToken}
            });
        },
        _getSavePublic: function() {
            var $savePublicCb = $('input[name="save-as-public"]', this.element);
            var savePublic;
            if (!$savePublicCb.length) {
                savePublic = this.defaultSavePublic;
            } else {
                savePublic = $savePublicCb.prop('checked');
            }
            // @see https://stackoverflow.com/questions/14716730/send-a-boolean-value-in-jquery-ajax-data/14716803
            return savePublic && '1' || '';
        },
        _getCommonSaveData: function() {
            var currentSettings = this.mbMap.getModel().getCurrentSettings();
            var diff = this.mbMap.getModel().diffSettings(this.referenceSettings, currentSettings);
            return {
                viewParams: this.mbMap.getModel().encodeViewParams(diff.viewParams || this.mbMap.getModel().getCurrentViewParams()),
                layersetsDiff: diff.layersets,
                sourcesDiff: diff.sources,
                token: this.csrfToken,
            };
        },
        _openUpdateOrInfo: function($row, recordId) {
            var $content = $(document.createElement('div'));
            var isUpdate = !!recordId;
            if (isUpdate) {
                $content.append(this.updateContent);
            } else {
                $content.append(this.infoContent);
            }
            $('input[name="title"]', $content).val($row.attr('data-title'));
            $('input[name="mtime"]', $content).val($row.attr('data-mtime'));
            var statusText = $row.attr('data-visibility-group') === 'public'
                ? Mapbender.trans('mb.core.viewManager.recordStatus.public')
                : Mapbender.trans('mb.core.viewManager.recordStatus.private')
            ;
            $('.-js-record-status', $content).text(statusText);
            this._showRecordForm($row, $content, recordId);
        },
        _showRecordForm: function($targetRow, $content, recordId) {
            if (this.useDialog_) {
                this._showRecordDialog($targetRow, $content, recordId);
            } else {
                this._showRecordPopover($targetRow, $content, recordId);
            }
        },
        _showRecordDialog: function($targetRow, $content, recordId) {
            var self = this;
            if (this.recordPopup_ && this.recordPopup_.$element) {
                this.recordPopup_.close();
            }
            var popup = new Mapbender.Popup({
                title: Mapbender.trans('mb.actions.edit'),
                subtitle: this.element.attr('data-title'),
                destroyOnClose: true,
                content: $content.get(0),
                draggable: true,
                modal: false,
                buttons: []
            });
            $content.on('click', '.-fn-update', function() {
                self._replace($targetRow, $content, recordId);
                popup.close();
            });
            $content.on('click', '.-fn-close', function() {
                popup.close();
            });
            this.recordPopup_ = popup;
        },
        _showRecordPopover: function($targetRow, $content, recordId) {
            this._closePopovers();
            $content
                .addClass('popover bottom')
                .prepend($(document.createElement('div')).addClass('arrow'))
            ;
            $('.-js-update-content-anchor', $targetRow).append($content);
            var self = this;
            $content.on('click', '.-fn-update', function() {
                self._replace($targetRow, $content, recordId);
            });
            $content.on('click', '.-fn-close', function() {
                $content.remove();
            });
        },
        _confirm: function($row, content) {
            var deferred = $.Deferred();
            var $popover = $(document.createElement('div'))
                .addClass('popover bottom')
                .append($(document.createElement('div')).addClass('arrow'))
                .append(content)
            ;
            $popover.on('click', '.-fn-confirm', function() {
                deferred.resolve();
                $popover.remove();
            });
            $popover.on('click', '.-fn-cancel', function() {
                $popover.remove();
                deferred.reject();
            });
            $popover.data('deferred', deferred);
            if (this.useDialog_) {
                var dialog = new Mapbender.Popup({
                    modal: true,
                    width: 300,
                    destroyOnClose: true,
                    title: $('p', $popover).text(),
                    buttons: $('button', $popover).get()
                });
                $('.-fn-cancel', dialog.$element).addClass('popupClose');
                dialog.$element.on('click', '.-fn-confirm', function() {
                    dialog.destroy();
                    deferred.resolve();
                });
                dialog.$element.on('close', function() {
                    deferred.reject();
                });
            } else {
                this._closePopovers();
                $('.-js-confirmation-anchor-delete', $row).append($popover);
            }

            return deferred.promise();
        },
        _closePopovers: function() {
            $('table .popover', this.element).each(function() {
                var $other = $(this);
                var otherPromise = $other.data('deferred');
                if (otherPromise) {
                    // Reject pending promises on delete confirmation popovers
                    otherPromise.reject();
                }
                $other.remove();
            });
        },
        _updatePlaceholder: function() {
            var $rows = $('table tbody tr', this.element);
            var $plch = $rows.filter('.placeholder-row');
            var $dataRows = $rows.not($plch);
            $plch.toggleClass('hidden', !!$dataRows.length);
        },
        /**
         * @param {Element} node
         * @return {}
         */
        _extractLinkSettings: function(node) {
            var raw = JSON.parse($(node).attr('data-diff'));
            return this._normalizeSettingsDiff(this._decodeLinkSettingsDiff(raw));
        },
        /**
         * @param {Object} raw
         * @return {mmMapSettingsDiff}
         */
        _decodeLinkSettingsDiff: function(raw) {
            return {
                viewParams: this.mbMap.getModel().decodeViewParams(raw.viewParams),
                sources: raw.sources || [],
                layersets: raw.layersets || []
            };
        },
        /**
         * @param {mmMapSettingsDiff} diff
         * @return {mmMapSettingsDiff}
         * @private
         */
        _normalizeSettingsDiff: function(diff) {
            // Fix stringified numbers
            diff.sources = diff.sources.map(function(entry) {
                if (typeof (entry.opacity) === 'string') {
                    entry.opacity = parseFloat(entry.opacity);
                }
                return entry;
            });
            return diff;
        },
        _apply: function(diff) {
            var settings = this.mbMap.getModel().mergeSettings(this.referenceSettings, diff);

            this.mbMap.getModel().applyViewParams(diff.viewParams);
            this.mbMap.getModel().applySettings(settings);
        },
        _flash: function($el, color) {
            $el.css({
                'background-color': color
            });
            window.setTimeout(function() {
                $el.css({
                    'transition': 'background-color 1s',
                    'background-color': ''
                });
                window.setTimeout(function() {
                    $el.css('transition', '');
                }, 1000);
            });
        },
        __dummy__: null
    });
})(jQuery);

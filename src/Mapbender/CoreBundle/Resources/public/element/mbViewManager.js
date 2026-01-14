(function($) {
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
            this._setupCsrf();
        },
        _setup: function(mbMap) {
            this.mbMap = mbMap;
            this._initEvents();
            this._toggleEnabled(true);
            // save default layertree settings, so they can be reloaded by the ResetView-Element
            this.saveDefaultSettings();
            this.mbMap.map.olMap.once('postrender', () => {
                this._load();
            });
        },
        _toggleEnabled: function(enabled) {
            $('.-fn-save-new', this.element).prop('disabled', !enabled);
            $('.-js-viewmanager-new-name', this.element).prop('disabled', !enabled);
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
                             cssClass: 'popupClose btn btn-sm btn-primary'
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
            this.element.on('mousedown', '.-fn-apply, .-js-forward-to-apply', function() {
                $(this).closest('tr').find('.-js-loadingspinner i').removeClass('opacity-0');
            });
            this.element.on('click', '.-fn-apply', function(evt) {
                evt.preventDefault();
                const $tr = $(evt.target).closest('tr');
                const viewId = $tr.attr('data-id');
                $.ajax([[self.elementUrl, 'getView'].join('/'), $.param({viewId: viewId})].join('?')).then(function(settings) {
                    settings.viewParams = self.mbMap.getModel().decodeViewParams(settings.viewParams);
                    self._apply(settings);
                    var $marker = $('.recall-marker', $tr);
                    $('.recall-marker', self.element).not($marker).css({opacity: ''});
                    $marker.css({opacity: '1'});
                    $tr.find('.-js-loadingspinner i').addClass('opacity-0');
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
                    const params = Mapbender.Util.getUrlQueryParams(window.location.href);
                    const viewid = params.hasOwnProperty('viewid') ? params.viewid : false;
                    let loadViewButton = false;
                    $('a.-fn-apply', $content).each(function() {
                        if (parseInt(viewid) === parseInt($(this).closest('tr').attr('data-id'))) {
                            loadViewButton = $(this);
                        }
                    });
                    $loadingPlaceholder.replaceWith($content);
                    self._updatePlaceholder();
                    if (loadViewButton !== false) {
                        loadViewButton.trigger('click');
                    }
                }, function() {
                    $loadingPlaceholder.hide();
                })
            ;
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
                var insertAfter = !data.savePublic && $('tr[data-visibility-group="public"]', $tbody).get(-1);
                if (insertAfter) {
                    $(insertAfter).after(newRow);
                } else {
                    $tbody.prepend(newRow);
                }
                self._flash($(newRow), '#88ff88');
                self._updatePlaceholder();
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
            let sources = [];
            this.mbMap.getModel().getSources().forEach((source) => {
                sources.push({
                    'id': source.id,
                    'children': source.children,
                    'options': source.options,
                    'type': source.type,
                    'customParams': source.customParams,
                    'isBaseSource': source.isBaseSource,
                    'isDynamicSource': source.isDynamicSource
                });
            });
            let layersets = [];
            Mapbender.layersets.forEach(layerset => {
                layersets.push({
                    'children': layerset.children,
                    'id': layerset.id,
                    'parent': layerset.parent,
                    'selected': layerset.selected,
                    'title_': layerset.title_,
                });
            });
            return {
                viewParams: this.mbMap.getModel().encodeViewParams(diff.viewParams || this.mbMap.getModel().getCurrentViewParams()),
                layersetStates: JSON.stringify(layersets),
                sourcesStates: JSON.stringify(sources),
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
                .prepend($(document.createElement('div')).addClass('popover-arrow'))
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
                .append($(document.createElement('div')).addClass('popover-arrow'))
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
        _apply: function(settings) {
            const layertreeElement = $('.mb-element-layertree');
            if (layertreeElement.length > 0) {
                layertreeElement.find('ul.layers:first').empty();
            }
            this.mbMap.getModel().sourceTree = [];
            this.mbMap.map.olMap.getAllLayers().forEach(layer => {
                this.mbMap.map.olMap.removeLayer(layer);
            });
            let wmsloaderSources = [];
            let sources = [];

            settings.sources.forEach(s => {
                let source = Mapbender.Source.factory(s);
                if (s.isDynamicSource) {
                    wmsloaderSources.push(source);
                    return; // continue
                }
                let layerset = settings.layersets.filter(l => {
                    let layersetSource = l.children.filter(child => child.id === source.id)[0];
                    return typeof(layersetSource) !== 'undefined' && source.id === layersetSource.id;
                })[0];
                source.layerset = new Mapbender.Layerset(layerset.title_, layerset.id, layerset.selected);
                this.mbMap.getModel().sourceTree.push(source);
                sources.push(source);
            });

            this.mbMap.getModel().initializeSourceLayers(sources);

            if (layertreeElement.length > 0) {
                layertreeElement.data('mapbenderMbLayertree')._createTree();
            }
            wmsloaderSources.forEach(source => {
                this.mbMap.getModel().addSourceFromConfig(source);
            });
            this.mbMap.getModel().applyViewParams(settings.viewParams);
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
        saveDefaultSettings: function () {
            window.localStorage.removeItem('viewManagerSettings');
            let settings = JSON.stringify(this._getCommonSaveData());
            window.localStorage.setItem('viewManagerSettings', settings);
        },
        _setupCsrf: function () {
            $.ajax({
                url: this.elementUrl + '/csrf',
                method: 'POST'
            }).fail(function (err) {
                Mapbender.error(Mapbender.trans(err.responseText));
            }).then(function (response) {
                this.csrfToken = response;
            }.bind(this));
        },
        __dummy__: null
    });
})(jQuery);

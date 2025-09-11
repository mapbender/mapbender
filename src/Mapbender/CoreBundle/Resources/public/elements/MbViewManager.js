(function() {

    class MbViewManager extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.useDialog_ = !this.$element.closest('.sideContent, .mobilePane').length;
            this.baseUrl = window.location.href.replace(/[?#].*$/, '');
            this._toggleEnabled(false);
            this.elementUrl = [Mapbender.configuration.application.urls.element, this.$element.attr('id')].join('/');
            this.mapPromise = Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this.referenceSettings = mbMap.getModel().getConfiguredSettings();
                this._setup(mbMap);
                return mbMap;
            });
            this.defaultSavePublic = (this.options.publicEntries === 'rw') || (this.options.publicEntries === 'rwd');
            this.deleteConfirmationContent = $('.-js-delete-confirmation-content', this.$element)
                .remove().removeClass('hidden').html();
            const $updateInfoCommon = $('.-js-update-content', this.$element)
                .remove().removeClass('hidden');
            const $infoContent = $updateInfoCommon.clone();
            $('input', $infoContent).prop('readonly', true);
            $('.-fn-update', $infoContent).remove();
            this.updateContent = $updateInfoCommon.html();
            this.infoContent = $infoContent.html();
            const $form = this.$element.find('form');
            $form.on('submit', (e) => {
                e.preventDefault();
                this._saveNew();
            });
            this.csrfToken = $form.attr('data-token');
        }

        _setup(mbMap) {
            this.mbMap = mbMap;
            this._initEvents();
            this._toggleEnabled(true);
            // save default layertree settings, so they can be reloaded by the ResetView-Element
            this.saveDefaultSettings();
            this.mbMap.map.olMap.on('rendercomplete', () => {
                this._load();
            });
            Mapbender.elementRegistry.markReady(this.$element.attr('id'));
        }

        _toggleEnabled(enabled) {
            $('.-fn-save-new', this.$element).prop('disabled', !enabled);
            $('.-js-viewmanager-new-name', this.$element).prop('disabled', !enabled);
        }

        open(callback) {
            this.closeCallback = callback || null;
            if (!this.popup_) {
                this.popup_ = new Mapbender.Popup({
                    modal: false,
                    detachOnClose: false,
                    destroyOnClose: false,
                    draggable: true,
                    resizable: true,
                    title: this.$element.attr('data-title'),
                    content: this.$element.get(0),
                    cssClass: 'mbViewManager-dialog',
                    buttons: [
                        {
                            label: Mapbender.trans('mb.actions.close'),
                            cssClass: 'popupClose btn btn-sm btn-primary'
                        }
                    ]
                });
                this.popup_.$element.on('close', () => {
                    this.close();
                });
            }
            this.popup_.$element.show();
            this.popup_.focus();
        }

        close() {
            if (this.popup_) {
                this.popup_.$element.hide();
            }
            if (this.recordPopup_ && this.recordPopup_.$element) {
                this.recordPopup_.close();
            }
            this.recordPopup_ = null;
            if (this.closeCallback) {
                (this.closeCallback)();
                this.closeCallback = null;
            }
        }

        _initEvents() {
            this.$element.on('mousedown', '.-fn-apply, .-js-forward-to-apply', function() {
                $(this).closest('tr').find('.-js-loadingspinner i').removeClass('opacity-0');
            });
            this.$element.on('click', '.-fn-apply', (evt) => {
                evt.preventDefault();
                const settings = this._extractLinkSettings(evt.currentTarget);
                this._apply(settings);
                const $marker = $('.recall-marker', $(evt.currentTarget).closest('tr'));
                $('.recall-marker', this.$element).not($marker).css({opacity: ''});
                $marker.css({opacity: '1'});
                $(evt.currentTarget).closest('tr').find('.-js-loadingspinner i').addClass('opacity-0');
            });
            this.$element.on('click', 'tr .-js-forward-to-apply', function() {
                $('.-fn-apply', $(this).closest('tr')).trigger('click');
            });
            this.$element.on('click', '.-fn-delete', (e) => {
                const $row = $(e.currentTarget).closest('tr');
                const rowId = $row.attr('data-id');
                this._confirm($row, this.deleteConfirmationContent).then(() => {
                    this._delete(rowId, this.csrfToken).then(() => {
                        $row.remove();
                        this._updatePlaceholder();
                    });
                });
            });
            this.$element.on('click', '.-fn-open-update, .-fn-open-info', (e) => {
                const $clickTarget = $(e.currentTarget);
                const $row = $clickTarget.closest('tr');
                const recordId = !$clickTarget.is('.-fn-open-info') && $row.attr('data-id') || null;
                this._openUpdateOrInfo($row, recordId);
                return false;   // Avoid re-focusing dialog
            });
        }

        _load() {
            const $loadingPlaceholder = $('.-fn-loading-placeholder', this.$element);
            const listingPromise = $.ajax([this.elementUrl, 'listing'].join('/'));
            $.when(listingPromise, this.mapPromise)
                .then((response) => {
                    const $content = $(response[0]);
                    const params = Mapbender.Util.getUrlQueryParams(window.location.href);
                    const viewid = params.hasOwnProperty('viewid') ? params.viewid : false;
                    let loadViewButton = false;
                    $('a.-fn-apply', $content).each((i, el) => {
                        this._updateLinkUrl(el);
                        if (parseInt(viewid) === parseInt($(el).closest('tr').attr('data-id'))) {
                            loadViewButton = $(el);
                        }
                    });
                    $loadingPlaceholder.replaceWith($content);
                    this._updatePlaceholder();
                    if (loadViewButton !== false) {
                        loadViewButton.trigger('click');
                    }
                }, () => {
                    $loadingPlaceholder.hide();
                });
        }

        _updateLinkUrl(link) {
            const settings = this._extractLinkSettings(link);
            const params = this.mbMap.getModel().encodeSettingsDiff(settings);
            const hash = this.mbMap.getModel().encodeViewParams(settings.viewParams);
            const url = [Mapbender.Util.addUrlParams(this.baseUrl, params).replace(/\/??\?$/, ''), hash].join('#');
            $(link).attr('href', url);
        }

        _replace($row, $form, id) {
            if (!id) {
                throw new Error('Cannot update record without id');
            }
            const data = Object.assign(this._getCommonSaveData(), {
                title: $('input[name="title"]', $form).val() || $row.attr('data-title')
            });
            const params = {id: id};
            return $.ajax([[this.elementUrl, 'save'].join('/'), $.param(params)].join('?'), {
                method: 'POST',
                data: data
            }).then((response) => {
                const newRow = $.parseHTML(response);
                $('a.-fn-apply', $(newRow)).each((i, el) => {
                    this._updateLinkUrl(el);
                });
                $row.replaceWith(newRow);
                this._flash($(newRow), '#88ff88');
            });
        }

        _saveNew() {
            const $titleInput = $('input[name="title"]', this.$element);
            const title = $titleInput.val();
            if (!title) {
                const $titleGroup = $titleInput.closest('.form-group');
                $titleGroup.addClass('has-error');
                $titleInput.one('keydown', function() {
                    $titleGroup.removeClass('has-error');
                });
                return $.Deferred().reject().promise();
            }
            const data = Object.assign(this._getCommonSaveData(), {
                title: title,
                savePublic: this._getSavePublic()
            });

            const $tbody = $('table tbody', this.$element);
            return $.ajax([this.elementUrl, 'save'].join('/'), {
                method: 'POST',
                data: data
            }).then((response) => {
                const newRow = $.parseHTML(response);
                $('a.-fn-apply', $(newRow)).each((i, el) => {
                    this._updateLinkUrl(el);
                });
                const insertAfter = !data.savePublic && $('tr[data-visibility-group="public"]', $tbody).get(-1);
                if (insertAfter) {
                    $(insertAfter).after(newRow);
                } else {
                    $tbody.prepend(newRow);
                }
                this._flash($(newRow), '#88ff88');
                this._updatePlaceholder();
            });
        }

        _delete(id, csrfToken) {
            const params = {id: id};
            return $.ajax([[this.elementUrl, 'delete'].join('/'), $.param(params)].join('?'), {
                method: 'DELETE',
                data: {token: csrfToken}
            });
        }

        _getSavePublic() {
            const $savePublicCb = $('input[name="save-as-public"]', this.$element);
            let savePublic;
            if (!$savePublicCb.length) {
                savePublic = this.defaultSavePublic;
            } else {
                savePublic = $savePublicCb.prop('checked');
            }
            return savePublic && '1' || '';
        }

        _getCommonSaveData() {
            const currentSettings = this.mbMap.getModel().getCurrentSettings();
            const diff = this.mbMap.getModel().diffSettings(this.referenceSettings, currentSettings);
            const sources = [];
            this.mbMap.getModel().getSources().forEach((source) => {
                sources.push({
                    id: source.id,
                    children: source.children,
                    options: source.options,
                    type: source.type,
                    customParams: source.customParams,
                    isBaseSource: source.isBaseSource,
                    isDynamicSource: source.isDynamicSource
                });
            });
            const layersets = [];
            Mapbender.layersets.forEach((layerset) => {
                layersets.push({
                    children: layerset.children,
                    id: layerset.id,
                    parent: layerset.parent,
                    selected: layerset.selected,
                    title_: layerset.title_,
                });
            });
            return {
                viewParams: this.mbMap.getModel().encodeViewParams(diff.viewParams || this.mbMap.getModel().getCurrentViewParams()),
                layersetStates: JSON.stringify(layersets),
                sourcesStates: JSON.stringify(sources),
                token: this.csrfToken,
            };
        }

        _openUpdateOrInfo($row, recordId) {
            const $content = $(document.createElement('div'));
            const isUpdate = !!recordId;
            if (isUpdate) {
                $content.append(this.updateContent);
            } else {
                $content.append(this.infoContent);
            }
            $('input[name="title"]', $content).val($row.attr('data-title'));
            $('input[name="mtime"]', $content).val($row.attr('data-mtime'));
            const statusText = $row.attr('data-visibility-group') === 'public'
                ? Mapbender.trans('mb.core.viewManager.recordStatus.public')
                : Mapbender.trans('mb.core.viewManager.recordStatus.private');
            $('.-js-record-status', $content).text(statusText);
            this._showRecordForm($row, $content, recordId);
        }

        _showRecordForm($targetRow, $content, recordId) {
            if (this.useDialog_) {
                this._showRecordDialog($targetRow, $content, recordId);
            } else {
                this._showRecordPopover($targetRow, $content, recordId);
            }
        }

        _showRecordDialog($targetRow, $content, recordId) {
            if (this.recordPopup_ && this.recordPopup_.$element) {
                this.recordPopup_.close();
            }
            const popup = new Mapbender.Popup({
                title: Mapbender.trans('mb.actions.edit'),
                subtitle: this.$element.attr('data-title'),
                destroyOnClose: true,
                content: $content.get(0),
                draggable: true,
                modal: false,
                buttons: []
            });
            $content.on('click', '.-fn-update', () => {
                this._replace($targetRow, $content, recordId);
                popup.close();
            });
            $content.on('click', '.-fn-close', () => {
                popup.close();
            });
            this.recordPopup_ = popup;
        }

        _showRecordPopover($targetRow, $content, recordId) {
            this._closePopovers();
            $content
                .addClass('popover bottom')
                .prepend($(document.createElement('div')).addClass('popover-arrow'));
            $('.-js-update-content-anchor', $targetRow).append($content);
            $content.on('click', '.-fn-update', () => {
                this._replace($targetRow, $content, recordId);
            });
            $content.on('click', '.-fn-close', () => {
                $content.remove();
            });
        }

        _confirm($row, content) {
            const deferred = $.Deferred();
            const $popover = $(document.createElement('div'))
                .addClass('popover bottom')
                .append($(document.createElement('div')).addClass('popover-arrow'))
                .append(content);
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
                const dialog = new Mapbender.Popup({
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
        }

        _closePopovers() {
            $('table .popover', this.$element).each(function() {
                const $other = $(this);
                const otherPromise = $other.data('deferred');
                if (otherPromise) {
                    otherPromise.reject();
                }
                $other.remove();
            });
        }

        _updatePlaceholder() {
            const $rows = $('table tbody tr', this.$element);
            const $plch = $rows.filter('.placeholder-row');
            const $dataRows = $rows.not($plch);
            $plch.toggleClass('hidden', !!$dataRows.length);
        }

        _extractLinkSettings(node) {
            const raw = JSON.parse($(node).attr('data-diff'));
            return this._normalizeSettingsDiff(this._decodeLinkSettingsDiff(raw));
        }

        _decodeLinkSettingsDiff(raw) {
            return {
                viewParams: this.mbMap.getModel().decodeViewParams(raw.viewParams),
                sources: raw.sources || [],
                layersets: raw.layersets || []
            };
        }

        _normalizeSettingsDiff(diff) {
            diff.sources = diff.sources.map(function(entry) {
                if (typeof (entry.opacity) === 'string') {
                    entry.opacity = parseFloat(entry.opacity);
                }
                return entry;
            });
            return diff;
        }

        _apply(settings) {
            const layertreeElement = $('.mb-element-layertree');
            layertreeElement.find('ul.layers:first').empty();
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

            layertreeElement.data('MbLayertree')._createTree();
            wmsloaderSources.forEach(source => {
                this.mbMap.getModel().addSourceFromConfig(source);
            });
            this.mbMap.getModel().applyViewParams(settings.viewParams);
        }

        _flash($el, color) {
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
        }

        saveDefaultSettings() {
            window.localStorage.removeItem('viewManagerSettings');
            const settings = JSON.stringify(this._getCommonSaveData());
            window.localStorage.setItem('viewManagerSettings', settings);
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbViewManager = MbViewManager;
})();

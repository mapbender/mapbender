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

        _create: function() {
            var self = this;
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
            // Attribute "readonly" doesn't work for checkboxes
            $('input[type="checkbox"]', $infoContent).prop('disabled', true);
            $('.-fn-update', $infoContent).remove();
            this.updateContent = $updateInfoCommon.html();
            this.infoContent = $infoContent.html();
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
        _initEvents: function() {
            var self = this;
            this.element.on('click', '.-fn-save-new', function() {
                self._saveNew().then(function() {
                    self._updatePlaceholder();
                });
            });
            this.element.on('click', '.-fn-apply', function(evt) {
                evt.preventDefault();
                self._apply(self._decodeDiff(this));
            });
            this.element.on('click', '.-fn-delete[data-id]', function() {
                // @todo: put id data on the row instead
                var rowId = $(this).attr('data-id');
                var $row = $(this).closest('tr');
                self._confirm($row, self.deleteConfirmationContent).then(function() {
                    self._delete(rowId).then(function() {
                        $row.remove();
                        self._updatePlaceholder();
                    });
                });
            });
            this.element.on('click', '.-fn-open-update[data-id], .-fn-open-info', function() {
                // @todo: put id data on the row instead
                var $clickTarget = $(this);
                var recordId = !$clickTarget.is('.-fn-open-info') && $clickTarget.attr('data-id') || null;
                var $row = $clickTarget.closest('tr');
                self._openUpdateOrInfo($row, recordId);
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
            var settings = this._decodeDiff(link)
            var params = this.mbMap.getModel().encodeSettingsDiff(settings);
            var hash = this.mbMap.getModel().encodeViewParams(settings.viewParams);
            var url = [Mapbender.Util.addUrlParams(this.baseUrl, params).replace(/\/?\?$/, ''), hash].join('#');
            $(link).attr('href', url);
        },
        _replace: function($row, $form, id) {
            var data = Object.assign(this._getCommonSaveData(), {
                title: $('input[name="title"]', $form).val() || $row.attr('data-title')
            });
            var $publicCb = $('input[name="public"]', $form);
            if ($publicCb.length && !$publicCb.prop('disabled')) {
                // @see https://stackoverflow.com/questions/14716730/send-a-boolean-value-in-jquery-ajax-data/14716803
                data.savePublic =  $publicCb.prop('checked') && '1' || ''
            }
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
                title: title
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
        _delete: function(id) {
            var params = {id: id};
            return $.ajax([[this.elementUrl, 'delete'].join('/'), $.param(params)].join('?'), {
                method: 'DELETE'
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
                // @see https://stackoverflow.com/questions/14716730/send-a-boolean-value-in-jquery-ajax-data/14716803
                savePublic: this._getSavePublic(),
                viewParams: this.mbMap.getModel().encodeViewParams(diff.viewParams || this.mbMap.getModel().getCurrentViewParams()),
                layersetsDiff: diff.layersets,
                sourcesDiff: diff.sources
            };
        },
        _openUpdateOrInfo: function($row, recordId) {
            this._closePopovers();
            var $popover = $(document.createElement('div'))
                .addClass('popover bottom')
                .append($(document.createElement('div')).addClass('arrow'))
            ;
            var isUpdate = !!recordId;
            if (isUpdate) {
                $popover.append(this.updateContent);
            } else {
                $popover.append(this.infoContent);
            }
            $('input[name="title"]', $popover).val($row.attr('data-title'));
            $('input[name="mtime"]', $popover).val($row.attr('data-mtime'));
            $('input[name="public"]', $popover).prop('checked', $row.attr('data-visibility-group') === 'public');
            $('.-js-update-content-anchor', $row).append($popover);

            var self = this;
            $popover.on('click', '.-fn-update', function() {
                self._replace($row, $popover, recordId);
            });
            $popover.on('click', '.-fn-close', function() {
                $popover.remove();
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
            this._closePopovers();
            $('.-js-confirmation-anchor-delete', $row).append($popover);

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
         * @return {mmMapSettingsDiff}
         * @private
         */
        _decodeDiff: function(node) {
            var raw = JSON.parse($(node).attr('data-diff'));
            // unravel viewParams from scalar string => Object
            var diff = {
                viewParams: this.mbMap.getModel().decodeViewParams(raw.viewParams),
                sources: raw.sources || [],
                layersets: raw.layersets || []
            };
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

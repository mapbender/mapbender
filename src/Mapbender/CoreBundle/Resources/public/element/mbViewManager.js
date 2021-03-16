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

        _create: function() {
            var self = this;
            this._toggleEnabled(false);
            this.elementUrl = [Mapbender.configuration.application.urls.element, this.element.attr('id')].join('/');
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self._setup(mbMap);
            });
            this.defaultSavePublic = (this.options.publicEntries === 'rw');
            this.deleteConfirmationContent = $('.-js-delete-confirmation-content', this.element)
                .remove().removeClass('hidden').html()
            ;
            this._load();   // Does not need map element to finish => can start asynchronously
        },
        _setup: function(mbMap) {
            this.mbMap = mbMap;
            this.referenceSettings = mbMap.getModel().getConfiguredSettings();
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
            this.element.on('click', '.-fn-apply', function() {
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
            this.element.on('click', '.-fn-update[data-id]', function() {
                var $clickTarget = $(this);
                var recordId = $clickTarget.attr('data-id');
                var $row = $clickTarget.closest('tr');
                self._replace($row, recordId);
            });
        },
        _load: function() {
            var $loadingPlaceholder = $('.-fn-loading-placeholder', this.element)
            var self = this;
            $.ajax([this.elementUrl, 'listing'].join('/'))
                .then(function(response) {
                    var $content = $(response);
                    $loadingPlaceholder.replaceWith($content);
                    self._updatePlaceholder();
                }, function() {
                    $loadingPlaceholder.hide()
                })
            ;
        },
        _replace: function($row, id) {
            var title = $('input[name="title"]', this.element).val() || $row.attr('data-title');
            var data = Object.assign(this._getCommonSaveData(), {
                title: title,
                savePublic: $row.attr('data-visibility-group') === 'public' && '1' || ''
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
            return this._saveCommon(title);
        },
        _saveCommon: function(title) {
            var data = Object.assign(this._getCommonSaveData(), {
                title: title
            });

            var self = this;
            return $.ajax([this.elementUrl, 'save'].join('/'), {
                method: 'POST',
                data: data
            }).then(function(response) {
                var newRow = $.parseHTML(response);
                $('table tbody', self.element).prepend(newRow);
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
            var savePublic
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
            $('.-js-confirmation-anchor-delete', $row).append($popover);

            return deferred.promise();
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

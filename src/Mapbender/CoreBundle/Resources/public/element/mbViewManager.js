;!(function($) {
    "use strict";
    $.widget("mapbender.mbViewManager", {
        mbMap: null,
        elementUrl: null,
        referenceSettings: null,

        _create: function() {
            var self = this;
            this._toggleEnabled(false);
            this.elementUrl = [Mapbender.configuration.application.urls.element, this.element.attr('id')].join('/');
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self._setup(mbMap);
            });
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
                self._saveNew();
            });
            this.element.on('click', '.-fn-apply', function() {
                self._apply(self._decodeDiff(this));
            });
            this.element.on('click', '.-fn-delete[data-id]', function() {
                var $row = $(this).closest('tr');
                self._delete($(this).attr('data-id')).then(function() {
                    $row.remove();
                });
            });
        },
        _load: function() {
            var $loadingPlaceholder = $('.-fn-loading-placeholder')
            $.ajax([this.elementUrl, 'listing'].join('/'))
                .then(function(response) {
                    var $content = $(response);
                    $loadingPlaceholder.replaceWith($content);
                }, function() {
                    $loadingPlaceholder.hide()
                })
            ;
        },
        _saveNew: function() {
            var title = $('input[name="title"]', this.element).val();
            if (!title) {
                // @todo: error feedback
                throw new Error("Cannot save with empty title");
            }

            var currentSettings = this.mbMap.getModel().getCurrentSettings();
            var diff = this.mbMap.getModel().diffSettings(this.referenceSettings, currentSettings);
            var data = {
                title: title,
                viewParams: this.mbMap.getModel().encodeViewParams(diff.viewParams || this.mbMap.getModel().getCurrentViewParams()),
                layersetsDiff: diff.layersets,
                sourcesDiff: diff.sources
            };

            var self = this;
            $.ajax([this.elementUrl, 'save'].join('/'), {
                method: 'POST',
                data: data
            }).then(function(response) {
                $('table tbody', self.element).prepend(response);
            })
        },
        _delete: function(id) {
            var params = {id: id};
            return $.ajax([[this.elementUrl, 'delete'].join('/'), $.param(params)].join('?'), {
                method: 'DELETE'
            });
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
            console.log("Trying to apply diff", diff);
            var settings = this.mbMap.getModel().mergeSettings(this.referenceSettings, diff);
            console.log("Produced complete settings", settings);

            this.mbMap.getModel().applyViewParams(diff.viewParams);
            this.mbMap.getModel().applySettings(settings);
        },
        __dummy__: null
    });
})(jQuery);

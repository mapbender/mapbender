;!(function($) {
    "use strict";
    $.widget("mapbender.mbShareUrl", {
        mbMap: null,
        baseUrl: null,

        _create: function() {
            var self = this;
            this.baseUrl = window.location.href.replace(/[?#].*$/, '');
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self._setup(mbMap);
            });
        },
        _setup: function(mbMap) {
            this.mbMap = mbMap;
            this._initEvents();
        },
        _initEvents: function() {
            var self = this;

            this.element.on('click', 'a.-fn-share-link', function(evt) {
                var useClipboard = evt.which === 1 && !evt.ctrlKey && !evt.shiftKey;
                var url = self._getUrl();
                // Update href to preempt standard browser actions "open in new tab" / "open in new window"
                $(this).attr('href', url);
                if (useClipboard) {
                    self._copyToClipboard(url);
                    Mapbender.info(Mapbender.trans('mb.core.ShareUrl.copied_to_clipboard'));
                    evt.preventDefault();
                    evt.stopPropagation();
                    return false;
                } else {
                    return true;
                }
            });
            this.element.on('mousedown', 'a.-fn-share-link', function() {
                // Update href to preempt standard browser actions "open in new tab" / "open in new window"
                $(this).attr('href', self._getUrl());
            });
        },
        _getUrl: function() {
            var m = this.mbMap.getModel();
            var settings = m.getCurrentSettings();
            var diff = m.diffSettings(m.getConfiguredSettings(), m.getCurrentSettings());
            var params = m.encodeSettingsDiff(diff);
            var url = Mapbender.Util.addUrlParams(this.baseUrl, params).replace(/\/?\?$/, '');
            url = [url, m.encodeViewParams(settings.viewParams)].join('#');
            return url;
        },
        _copyToClipboard: function(text) {
            // MUST use an input that is in the DOM and nominally visible.
            // We prevent visible rendering flashes with style="opacity: 0;" (works in Chrome + FF)
            // Remove input from DOM immediately after copy operation
            var $input = $(document.createElement('textarea'));
            $input.val(text);
            $input.css({opacity: 0});
            document.body.appendChild($input.get(0));
            $input.focus();
            $input.select();
            document.execCommand('copy');
            document.body.removeChild($input.get(0));
        },
        __dummy__: null
    });
})(jQuery);


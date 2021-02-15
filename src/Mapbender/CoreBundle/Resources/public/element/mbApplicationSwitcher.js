;!(function($) {
    "use strict";
    $.widget("mapbender.mbApplicationSwitcher", {
        options: {
            open_in_new_tab: false
        },
        mbMap: null,
        baseUrl: null,
        elementUrl: null,

        _create: function() {
            var self = this;
            this.elementUrl = [Mapbender.configuration.application.urls.element, this.element.attr('id')].join('/');
            this.baseUrl = window.location.href.replace(/(\/application\/).*$/, '$1');
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
            this.element.on('change', 'select', function() {
                self._switchApplication($(this).val());
            });
        },
        _switchApplication: function(slug) {
            var targetApplicationUrl = [this.baseUrl, slug].join('');
            var viewParams = this.mbMap.getModel().getCurrentViewParams();
            var targetHash = this.mbMap.getModel().encodeViewParams(viewParams);
            var targetUrl = [targetApplicationUrl, targetHash].join('#');
            if (this.options.open_in_new_tab) {
                window.open(targetUrl);
            } else {
                window.location.href = targetUrl;
            }
        },
        __dummy__: null
    });
})(jQuery);

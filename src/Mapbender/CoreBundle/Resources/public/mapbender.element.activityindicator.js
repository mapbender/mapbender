(function ($) {
    'use strict';

    $.widget("mapbender.mbActivityIndicator", {
        options: {
            activityClass: 'mb-activity',
            ajaxActivityClass: 'mb-activity-ajax',
            titleActivityClass: 'mb-activity-tile'
        },

        ajaxActivity: false,
        tileActivity: false,
        loadingLayers: [],
        knownLayers: [],

        _create: function () {
            var elementIds = Object.keys(Mapbender.configuration.elements);
            for (var i = 0; i < elementIds.length; ++i) {
                var id = elementIds[i];
                var elementConfig = Mapbender.configuration.elements[id];
                if (elementConfig.init === 'mapbender.mbMap') {
                    if (Mapbender.checkTarget("mbActivityIndicator", id)) {
                        Mapbender.elementRegistry.waitReady(id).then($.proxy(this._setupMap, this));
                    }
                }
            }
            this.element.on('ajaxStart', $.proxy(this._onAjaxStart, this));
            this.element.on('ajaxStop', $.proxy(this._onAjaxStop, this));
        },
        _setupMap: function(mbMap) {
            var self = this;
            mbMap.element.on('mbmapsourceloadstart', function(event, data) {
                var source = data.source;
                self.loadingLayers.push(source);
                self._onLayerLoadChange();
            });
            mbMap.element.on('mbmapsourceloadend mbmapsourceremoved', function(event, data) {
                var source = data.source;
                self.loadingLayers = self.loadingLayers.filter(function(x) {
                    return x !== source;
                });
                self._onLayerLoadChange();
            });
        },
        /**
         * Listener for global ajaxStart events
         */
        _onAjaxStart: function () {
            this.ajaxActivity = true;
            this._updateBodyClass();
        },

        /**
         * Listener for global ajaxStop events
         */
        _onAjaxStop: function () {
            this.ajaxActivity = false;
            this._updateBodyClass();
        },

        _onLayerLoadChange: function () {
            var stillLoading = this.loadingLayers.length > 0;

            if (stillLoading !== this.tileActivity) {
                this.tileActivity = stillLoading;
                this._updateBodyClass();
            }
        },

        /**
         * Update body classes to match current activity
         */
        _updateBodyClass: function () {
            var $body = $('body');
            $body.toggleClass(this.options.ajaxActivityClass, this.ajaxActivity);
            $body.toggleClass(this.options.titleActivityClass, this.tileActivity);
            $body.toggleClass(this.options.activityClass, this.tileActivity || this.ajaxActivity);
        }
    });

})(jQuery);

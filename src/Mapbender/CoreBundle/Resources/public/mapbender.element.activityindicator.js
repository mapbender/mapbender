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
        targets: [],

        _create: function () {
            var widget = this;
            var elementIds = Object.keys(Mapbender.configuration.elements);
            for (var i = 0; i < elementIds.length; ++i) {
                var id = elementIds[i];
                var elementConfig = Mapbender.configuration.elements[id];
                if (elementConfig.init === 'mapbender.mbMap') {
                    this.targets[id] = false;
                    if (Mapbender.checkTarget("mbActivityIndicator", id)) {
                        Mapbender.elementRegistry.waitReady(id).then($.proxy(widget._setupMap, widget));
                    }
                }
            }
            this.element.on('ajaxStart', $.proxy(this._onAjaxStart, this));
            this.element.on('ajaxStop', $.proxy(this._onAjaxStop, this));
        },
        _setupMap: function (mbMap) {
            var self = this;
            var olMap = mbMap.map.olMap;
            for (var i = 0; i < olMap.layers.length; ++i) {
                this._bindToLayer(olMap.layers[i]);
            }
            olMap.events.register('addlayer', null, function(event) {
                self._bindToLayer(event.layer);
            });
            this._onLayerLoadChange();
        },
        _bindToLayer: function (olLayer) {
            var self = this;
            if (this.knownLayers.indexOf(olLayer.id) !== -1) {
                return;
            } else {
                this.knownLayers.push(olLayer.id);
            }
            if (olLayer.numLoadingTiles && this.loadingLayers.indexOf(olLayer.id) === -1) {
                this.loadingLayers.push(olLayer.id);
            }
            olLayer.events.on({
                loadstart: function (event) {
                    var position = self.loadingLayers.indexOf(event.object.id);
                    if (position === -1) {
                        self.loadingLayers.push(event.object.id);
                        self._onLayerLoadChange();
                    }
                },
                loadend: function (event) {
                    var position = self.loadingLayers.indexOf(event.object.id);
                    if (position !== -1) {
                        self.loadingLayers.splice(position, 1);
                        self._onLayerLoadChange();
                    }
                }
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

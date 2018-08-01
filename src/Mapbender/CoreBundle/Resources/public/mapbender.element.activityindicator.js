(function ($) {
    'use strict';

    $.widget("mapbender.mbActivityIndicator", $.mapbender.mbBaseElement, {
        options: {
            activityClass: 'mb-activity',
            ajaxActivityClass: 'mb-activity-ajax',
            titleActivityClass: 'mb-activity-tile'
        },

        elementUrl: null,
        ajaxActivity: false,
        tileActivity: false,
        loadingLayers: [],
        targets: [],

        _create: function () {
            var widget = this;

            Object.entries(Mapbender.configuration.elements).map(function (entry) {
                var element = entry.pop(),
                    id = entry.pop();

                if (element.init === 'mapbender.mbMap') {
                    widget.targets[id] = false;

                    if (!Mapbender.checkTarget("mbActivityIndicator", id)) {
                        return;
                    }

                    Mapbender.elementRegistry.onElementReady(id, $.proxy(widget._setup, widget, id));
                }
            });

        },

        _setup: function (id) {
            var widget = this,
                allInitiated = $.inArray(false, this.targets) >= 0;

            $('#' + id).each(function () {
                var mqMap = $(this).data('mapbenderMbMap').map;

                $.each(mqMap.layers(), function (idx, mqLayer) {
                    widget._bindToLayer(mqLayer);

                    // Is it already loading tiles?
                    if (typeof mqLayer.olLayer.numLoadingTiles === 'number' && mqLayer.olLayer.numLoadingTiles > 0) {
                        widget.loadingLayers.push(mqLayer.olLayer.id);
                        widget._onLayerLoadChange();
                    }
                });

                mqMap.events.on('mqAddLayer', function (event, mqLayer) {
                    widget._bindToLayer(mqLayer);
                });
            });

            this.targets[id] = true;

            if (allInitiated) {
                this.element.on('ajaxStart', $.proxy(this._onAjaxStart, this));
                this.element.on('ajaxStop', $.proxy(this._onAjaxStop, this));
            }
        },

        _bindToLayer: function (mqLayer) {
            mqLayer.olLayer.events.on({
                scope: this,
                loadstart: function (event) {
                    this.loadingLayers.push(event.object.id);
                    this._onLayerLoadChange();
                },
                loadend: function (event) {
                    var position = this.loadingLayers.indexOf(event.object.id);
                    this.loadingLayers.splice(position, 1);
                    this._onLayerLoadChange();
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
            var $body = $('body'),
                hasAjaxClass = $body.hasClass(this.options.ajaxActivityClass),
                hasTileClass = $body.hasClass(this.options.titleActivityClass),
                hasActivityClass = $body.hasClass(this.options.activityClass);

            if (this.ajaxActivity !== hasAjaxClass) {
                $body.toggleClass(this.options.ajaxActivityClass);
            }

            if (this.tileActivity !== hasTileClass) {
                $body.toggleClass(this.options.tileActivityClass);
            }

            if ((this.tileActivity || this.ajaxActivity) !== hasActivityClass) {
                $body.toggleClass(this.options.activityClass);
            }
        }
    });

})(jQuery);


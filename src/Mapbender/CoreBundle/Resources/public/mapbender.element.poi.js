(function($) {

    $.widget('mapbender.mbPOI', {
        options: {
            useMailto: false,
            gps: undefined
        },
        map: null,
        mbMap: null,
        clickActive: false,
        popup: null,
        poiMarkerLayer: null,
        poi: null,
        gpsElement: null,
        // invoked on close; informs controlling button to de-highlight
        closeCallback: null,

        _create: function() {
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(
                function(mbMap) {
                    self._setup(mbMap);
                },
                function() {
                    Mapbender.checkTarget("mbPOI");
                }
            );
        },

        _setup: function(mbMap) {
            this.mbMap = mbMap;
            this.mbMap.element.on('mbmapclick', this._mapClickHandler.bind(this));

            if (this.options.gps) {
                this.gpsElement = $('#' + this.options.gps);
                if (!this.gpsElement.length) {
                    this.gpsElement = null;
                }
            }
        },

        /**
         * Deprecated
         */
        defaultAction: function(closeCallback) {
            if (this.popup) {
                this.deactivate();
            } else {
                this.activate(closeCallback);
            }
        },
        activate: function(closeCallback) {
            this._open(closeCallback);
        },
        deactivate: function() {
            this.close();
        },
        /**
         * Same as activate, but proper Button API name expectation
         * @param closeCallback
         */
        open: function(closeCallback) {
            this.activate(closeCallback);
        },
        /**
         * Method name aliasing to Avoid detection by control button
         * @param closeCallback
         * @private
         */
        _open: function(closeCallback) {
            if (!this.popup) {
                this.popup = new Mapbender.Popup(this._getPopupOptions());
                this.popup.$element.one('close', this.close.bind(this));
            }
            this.closeCallback = closeCallback;
            this.clickActive = true;
        },

        _mapClickHandler: function(event, data) {
            if (this.clickActive) {
                this._updatePoi(data.coordinate[0], data.coordinate[1]);
                this._setPoiMarker(data.coordinate[0], data.coordinate[1]);
                // Stop further handlers
                return false;
            }
        },
        _updatePoi: function(lon, lat) {
            var srsName = Mapbender.Model.getCurrentProjectionCode();
            var deci = (Mapbender.Model.getProjectionUnitsPerMeter(srsName) < 0.25) ? 5 : 2;
            this.poi = {
                point: lon.toFixed(deci) + ',' + lat.toFixed(deci),
                scale: this.mbMap.model.getCurrentScale(),
                srs: srsName
            };
            this.popup.subtitle(this.poi.point + ' @ 1:' + this.poi.scale);
        },
        _setPoiMarker: function(lon, lat) {
            if (!this.poiMarkerLayer) {
                this.poiMarkerLayer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            }

            this.poiMarkerLayer.clear();
            this.poiMarkerLayer.setBuiltinMarkerStyle('poiIcon');
            this.poiMarkerLayer.addMarker(lon, lat);
            this.poiMarkerLayer.show();
        },
        _getPopupOptions: function() {
            var self = this;
            var options = {
                draggable: true,
                cssClass: 'mb-poi-popup',
                destroyOnClose: true,
                modal: false,
                scrollable: false,
                width: 500,
                title: this.element.attr('data-title'),
                content: $('.input', this.element).first().html(),
                buttons: [
                    {
                        label: Mapbender.trans('mb.actions.accept'),
                        cssClass: 'button',
                        callback: function () {
                            self._sendPoi(this.$element);
                        }
                    },
                    {
                        label: Mapbender.trans('mb.actions.cancel'),
                        cssClass: 'popupClose button critical'
                    }
                ]
            };
            if (this.gpsElement) {
                options.buttons.unshift({
                    label: Mapbender.trans('mb.core.poi.popup.btn.position'),
                    cssClass: 'button',
                    callback: function() {
                        self.gpsElement.mbGpsPosition('getGPSPosition', function(lonLat) {
                            self._updatePoi(lonLat.lon, lonLat.lat);
                            self._setPoiMarker(lonLat.lon, lonLat.lat);
                        });
                    }
                });
            }
            return options;
        },

        close: function() {
            this._reset();
            if (this.gpsElement) {
                this.gpsElement.mbGpsPosition('deactivate');
            }

            if (this.poiMarkerLayer) {
                this.poiMarkerLayer.hide();
            }
            if (this.popup) {
                this.popup.close();
            }
            if (this.closeCallback && typeof this.closeCallback === 'function') {
                this.closeCallback.call();
            }
            this.closeCallback = null;
            this.popup = null;
            this.clickActive = false;
        },

        _sendPoi: function(content) {
            var label = $('textarea', content).val();

            if(!this.poi) {
                return;
            }

            var poi = $.extend({}, this.poi, {
                label: label
            });
            var params = $.param({ poi: poi });
            var poiURL = window.location.protocol + '//' + window.location.host + window.location.pathname + '?' + params;
            var body = [label, '\n\n', poiURL].join('');
            if(this.options.useMailto) {
                var mailto_link = 'mailto:?body=' + escape(body);
                var win = window.open(mailto_link, 'emailWindow');
                window.setTimeout(function() {if (win && win.open &&!win.closed) win.close();}, 100);
            } else {
                var ta = $('<div/>', {
                    html: $('.output', this.element).html()
                });
                $('textarea', ta).val(body);
                new Mapbender.Popup({
                    destroyOnClose: true,
                    cssClass: 'mb-poi-popup',
                    modal: true,
                    title: this.element.attr('title'),
                    width: 500,
                    content: ta,
                    buttons: []
                });
            }

            this._reset();
            this.popup.close();

        },

        _reset: function() {
            this.poi = null;
        }
    });

})(jQuery);

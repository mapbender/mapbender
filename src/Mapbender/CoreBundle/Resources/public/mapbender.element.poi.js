(function($) {

    $.widget('mapbender.mbPOI', {
        options: {
            target: undefined,
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
            var target = this.options.target;
            Mapbender.elementRegistry.waitReady(target).then(
                function(mbMap) {
                    self._setup(mbMap);
                },
                function() {
                    Mapbender.checkTarget("mbPOI", target);
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
                this._setPoiMarker(data.coordinate[0], data.coordinate[1]);
            }
        },

        _setPoiMarker: function(lon, lat) {
            var srsName = Mapbender.Model.getCurrentProjectionCode();
            var deci = (Mapbender.Model.getProjectionUnitsPerMeter(srsName) < 0.25) ? 5 : 2;

            if (!this.poiMarkerLayer) {
                this.poiMarkerLayer = new OpenLayers.Layer.Markers();
                this.mbMap.map.olMap.addLayer(this.poiMarkerLayer);
            }

            this.poiMarkerLayer.clearMarkers();

            var poiMarker = new OpenLayers.Marker({lon: lon, lat: lat}, new OpenLayers.Icon(
                Mapbender.configuration.application.urls.asset +
                this.mbMap.options.poiIcon.image, {
                    w: this.mbMap.options.poiIcon.width,
                    h: this.mbMap.options.poiIcon.height
                }, {
                    x: this.mbMap.options.poiIcon.xoffset,
                    y: this.mbMap.options.poiIcon.yoffset
                })
            );

            this.poiMarkerLayer.addMarker(poiMarker);

            this.poi = {
                point: lon.toFixed(deci) + ',' + lat.toFixed(deci),
                scale: this.mbMap.model.getScale(),
                srs: srsName
            };
            this.popup.subtitle(this.poi.point + ' @ 1:' + this.poi.scale);
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
                title: this.element.attr('title'),
                content: $('.input', this.element).html(),
                buttons: [
                    {
                        label: Mapbender.trans('mb.core.poi.popup.btn.ok'),
                        cssClass: 'button',
                        callback: function () {
                            self._sendPoi(this.$element);
                        }
                    },
                    {
                        label: Mapbender.trans('mb.core.poi.popup.btn.cancel'),
                        cssClass: 'button buttonCancel critical',
                        callback: function () {
                            this.close();
                        }
                    }
                ]
            };
            if (this.gpsElement) {
                options.buttons.unshift({
                    label: Mapbender.trans('mb.core.poi.popup.btn.position'),
                    cssClass: 'button',
                    callback: function() {
                        self.gpsElement.mbGpsPosition('getGPSPosition', function(lonLat) {
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
                this.poiMarkerLayer.clearMarkers();
                this.mbMap.map.olMap.removeLayer(this.poiMarkerLayer);
                this.poiMarkerLayer.destroy();
                this.poiMarkerLayer = null;
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
            var form = $('form', content);
            var body = $('#body', form).val();

            if(!this.poi) {
                return;
            }

            var poi = $.extend({}, this.poi, {
                label: body.replace(/\n|\r/g, '<br />')
            });
            var params = $.param({ poi: poi });
            var poiURL = window.location.protocol + '//' + window.location.host + window.location.pathname + '?' + params;
            body += '\n\n' + poiURL;
            if(this.options.useMailto) {
                var mailto_link = 'mailto:?body=' + escape(body);
                var win = window.open(mailto_link, 'emailWindow');
                window.setTimeout(function() {if (win && win.open &&!win.closed) win.close();}, 100);
            } else {
                var ta = $('<div/>', {
                    html: $('.output', this.element).html()
                });
                ta.addClass("poi-link");
                $('textarea', ta).val(body);
                new Mapbender.Popup({
                    destroyOnClose: true,
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

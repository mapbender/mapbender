(function () {
    class MbPoi extends MapbenderElement {

        constructor(configuration, $element) {
            super(configuration, $element);

            const self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                    self._setup(mbMap);
                }, () => {
                    Mapbender.checkTarget('mbPOI');
                }
            );
        }

        _setup(mbMap) {
            this.mbMap = mbMap;
            this.mbMap.element.on('mbmapclick', this._mapClickHandler.bind(this));

            if (this.options.gps) {
                this.gpsElement = $('#' + this.options.gps);
                if (!this.gpsElement.length) {
                    this.gpsElement = null;
                }
            }
        }

        activate(closeCallback) {
            this._open(closeCallback);
        }

        deactivate() {
            this.close();
        }

        /**
         * Same as activate, but proper Button API name expectation
         * @param closeCallback
         */
        open(closeCallback) {
            this.activate(closeCallback);
        }

        /**
         * Method name aliasing to Avoid detection by control button
         * @param closeCallback
         */
        _open(closeCallback) {
            if (!this.popup) {
                this.popup = new Mapbender.Popup(this._getPopupOptions());
                this.popup.$element.one('close', this.close.bind(this));
            }
            this.closeCallback = closeCallback;
            this.clickActive = true;
        }

        _mapClickHandler(event, data) {
            if (this.clickActive) {
                this._updatePoi(data.coordinate[0], data.coordinate[1]);
                this._setPoiMarker(data.coordinate[0], data.coordinate[1]);
                // Stop further handlers
                return false;
            }
        }

        _updatePoi(lon, lat) {
            const srsName = Mapbender.Model.getCurrentProjectionCode();
            const deci = (Mapbender.Model.getProjectionUnitsPerMeter(srsName) < 0.25) ? 5 : 2;
            this.poi = {
                point: lon.toFixed(deci) + ',' + lat.toFixed(deci),
                scale: this.mbMap.model.getCurrentScale(),
                srs: srsName
            };
            this.popup.subtitle(this.poi.point + ' @ 1:' + this.poi.scale);
        }

        _setPoiMarker(lon, lat) {
            if (!this.poiMarkerLayer) {
                this.poiMarkerLayer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            }

            this.poiMarkerLayer.clear();
            this.poiMarkerLayer.setBuiltinMarkerStyle('poiIcon');
            this.poiMarkerLayer.addMarker(lon, lat);
            this.poiMarkerLayer.show();
        }

        _getPopupOptions() {
            const self = this;
            let options = {
                draggable: true,
                cssClass: 'mb-poi-popup',
                destroyOnClose: true,
                modal: false,
                scrollable: false,
                width: 500,
                title: this.$element.attr('data-title'),
                content: $('.input', this.$element).first().html(),
                buttons: [
                    {
                        label: Mapbender.trans('mb.core.poi.accept'),
                        cssClass: 'btn btn-sm btn-primary',
                        attrDataTest: 'mb-poi-btn-add',
                        callback: () => {
                            self._sendPoi(this.$element);
                        }
                    },
                    {
                        label: Mapbender.trans('mb.actions.close'),
                        cssClass: 'btn btn-sm btn-light popupClose',
                        attrDataTest: 'mb-poi-btn-close'
                    }
                ]
            };
            if (this.gpsElement) {
                options.buttons.unshift({
                    label: Mapbender.trans('mb.core.poi.popup.btn.position'),
                    cssClass: 'button',
                    callback: () => {
                        self.gpsElement.mbGpsPosition('getGPSPosition', (lonLat) => {
                            self._updatePoi(lonLat.lon, lonLat.lat);
                            self._setPoiMarker(lonLat.lon, lonLat.lat);
                        });
                    }
                });
            }
            return options;
        }

        close() {
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
        }

        _sendPoi(content) {
            const label = $('textarea', content).val();

            if(!this.poi) {
                return;
            }

            const poi = $.extend({}, this.poi, {
                label: label
            });
            const params = $.param({ poi: poi });
            const poiURL = window.location.protocol + '//' + window.location.host + window.location.pathname + '?' + params;
            const body = [label, '\n\n', poiURL].join('');
            if(this.options.useMailto) {
                const mailto_link = 'mailto:?body=' + escape(body);
                const win = window.open(mailto_link, 'emailWindow');
                window.setTimeout(() => {
                    if (win && win.open && !win.closed) win.close();
                }, 100);
            } else {
                const ta = $('<div/>', {
                    html: $('.output', this.$element).html()
                });
                $('textarea', ta).val(body);
                new Mapbender.Popup({
                    destroyOnClose: true,
                    cssClass: 'mb-poi-popup',
                    modal: true,
                    title: this.$element.attr('data-title'),
                    width: 500,
                    content: ta,
                    buttons: []
                });
            }

            this._reset();
            this.popup.close();
        }

        _reset() {
            this.poi = null;
        }
    }
    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbPoi = MbPoi;
})();

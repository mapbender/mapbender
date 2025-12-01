(function() {
    class MbGpsPosition extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.geolocationProvider_ = navigator.geolocation || null;
            // Uncomment to use mock data
            // this.geolocationProvider_ = window.Mapbender.GeolocationMock;
            if (!this.geolocationProvider_) {
                Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
                throw new Error("No geolocation support");
            }

            // Ensure the toolbar item is tabbable
            var $toolBarItem = $(this.$element).closest('.toolBarItem');
            if (!$toolBarItem.attr('tabindex')) {
                $toolBarItem.attr('tabindex', '0');
            }

            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this.map = mbMap;
                this._setup();
            }, () => {
                Mapbender.checkTarget("mbGpsPosition");
            });
            this.options.average = Math.max(1, parseInt(this.options.average) || 1);
            this.$element
                .on('click', () => { this.toggleTracking(); })
                .on('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        this.toggleTracking();
                    }
                });
        }

        _setup() {
            this.layer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            if (this.options.autoStart === true) {
                this.activate();
            }
            Mapbender.elementRegistry.markReady(this);
        }

        _getMarkerFeatures(position, accuracy) {
            var model = this.map.model,
                upm = model.getProjectionUnitsPerMeter();
            var markerStyle = new ol.style.Style({
                image: new ol.style.Circle({
                    radius: 10,
                    stroke: new ol.style.Stroke({
                        color: 'rgba(255, 0, 0, 1)',
                        width: 3
                    })
                })
            });
            var circleStyle = new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: 'rgba(255,255,255, 1)',
                    width: 1
                }),
                fill: new ol.style.Fill({
                    color: 'rgba(255,255,255, 0.5)'
                })
            });
            var features = {
                point: new ol.Feature(new ol.geom.Point([position.lon, position.lat]))
            };
            features.point.setStyle(markerStyle);

            if (accuracy) {
                var radius = accuracy * (upm / 2.);
                features.circle = new ol.Feature(new ol.geom.Circle([position.lon, position.lat], radius));
                features.circle.setStyle(circleStyle);
            }
            return features;
        }

        _showLocation(position, accuracy) {
            var features = this._getMarkerFeatures(position, accuracy);
            this.layer.clear();
            this.layer.addNativeFeatures([features.point]);
            if (features.circle) {
                this.layer.addNativeFeatures([features.circle]);
            }
            if ((this.firstPosition && this.options.centerOnFirstPosition) || this.options.follow) {
                if (features.circle && this.options.zoomToAccuracyOnFirstPosition && this.firstPosition) {
                    this.map.getModel().zoomToFeature(features.circle);
                } else {
                    this.map.getModel().panToFeature(features.point, {
                        center: this.firstPosition,
                        buffer: 100
                    });
                }
            }
            this.firstPosition = false;
        }

        /**
         * Toggle GPS positioning
         *
         * @returns {MbGpsPosition}
         */
        toggleTracking() {
            if (this.observer) {
                this.deactivate();
            } else {
                this.activate();
            }
        }

        /**
         * @param {(*|GeolocationPosition)} position see https://developer.mozilla.org/en-US/docs/Web/API/GeolocationPosition
         * @private
         */
        _handleGeolocationPosition(position) {
            var p = this._transformCoordinate(position.coords.longitude, position.coords.latitude);

            // Averaging: Building a queue...
            this.stack.push(p);
            if (this.stack.length > this.options.average) {
                this.stack.splice(0, 1);
            }
            var averaged = {
                lon: 0,
                lat: 0
            };
            var nEntries = this.stack.length;
            for (var i = 0; i < nEntries; ++i) {
                averaged.lon += this.stack[i].lon / nEntries;
                averaged.lat += this.stack[i].lat / nEntries;
            }
            this._showLocation(averaged, position.coords.accuracy);
        }

        /**
         * @param {(*|GeolocationPositionError)} gle see https://developer.mozilla.org/en-US/docs/Web/API/GeolocationPositionError
         * @private
         */
        _handleGeolocationError(gle) {
            Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.nosignal"));
            this.deactivate();
        }

        _transformCoordinate(lon, lat) {
            var xy = {
                x: lon,
                y: lat
            };
            var targetSrsName = this.map.model.getCurrentProjectionCode();
            var xyTransformed = Mapbender.mapEngine.transformCoordinate(xy, 'EPSG:4326', targetSrsName);
            return {
                lon: xyTransformed.x,
                lat: xyTransformed.y
            };
        }

        /**
         * Activate GPS positioning
         */
        activate() {
            this.layer.show();
            Mapbender.vectorLayerPool.raiseElementLayers(this);

            if (!this.observer) {
                if (this.geolocationProvider_) {
                    this.firstPosition = true;
                    this.observer = this.geolocationProvider_.watchPosition((position) => {
                        this._handleGeolocationPosition(position);
                    }, (gle) => {
                        this._handleGeolocationError(gle);
                    }, { enableHighAccuracy: true, maximumAge: 0 });

                    $(this.$element).addClass("toolBarItemActive");
                } else {
                    Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
                }
            }
        }

        /**
         * Deactivate GPS positioning
         * @returns {object}
         */
        deactivate() {
            if (this.observer) {
                this.geolocationProvider_.clearWatch(this.observer);
                this.observer = null;
            }
            $(this.$element).removeClass("toolBarItemActive");
            this.layer.hide();
        }

        getGPSPosition(callback) {
            if (this.geolocationProvider_) {
                if (this.observer) {
                    this.geolocationProvider_.clearWatch(this.observer);
                    this.observer = null;
                }
                this.layer.show();
                this.firstPosition = true;
                this.geolocationProvider_.getCurrentPosition((position) => {
                    var p = this._transformCoordinate(position.coords.longitude, position.coords.latitude);
                    this._showLocation(p, position.coords.accuracy);

                    if (typeof callback === 'function') {
                        callback(p);
                    }

                }, () => {
                    Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.nosignal"));
                    this.deactivate();
                }, { enableHighAccuracy: true, maximumAge: 0 });
            } else {
                Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
            }
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbGpsPosition = MbGpsPosition;
})();

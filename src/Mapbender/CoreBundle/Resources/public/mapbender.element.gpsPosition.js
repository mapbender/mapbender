/**
 *
 * @author Arne Schubert <atd.schubert@gmail.com>
 * @namespace mapbender.mbGpsPosition
 */
(function ($) {
    'use strict';

    $.widget("mapbender.mbGpsPosition", {
        options: {
            autoStart: false,
            follow: false,
            average: 1,
            centerOnFirstPosition: true,
            zoomToAccuracyOnFirstPosition: true
        },
        map: null,
        observer: null,
        firstPosition: true,
        stack: [],
        geolocationAccuracyId: null,
        geolocationMarkerId: null,
        layer: null,
        internalProjection: null,
        metricProjection: null,
        geolocationProvider_: null,

        _create: function () {
            var self = this;
            this.geolocationProvider_ = navigator.geolocation || null;
            // Uncomment to use mock data
            // this.geolocationProvider_ = window.Mapbender.GeolocationMock;
            if (!this.geolocationProvider_) {
                Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
                throw new Error("No geolocation support");
            }

            // Ensure the toolbar item is tabbable
            var $toolBarItem = $(this.element).closest('.toolBarItem');
            if (!$toolBarItem.attr('tabindex')) {
                $toolBarItem.attr('tabindex', '0');
            }

            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.map = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget("mbGpsPosition");
            });
            this.options.average = Math.max(1, parseInt(this.options.average) || 1);
            this.element
                .on('click', $.proxy(this.toggleTracking, this))
                .on('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        this.toggleTracking();
                    }
                });
        },
        _setup: function () {
            this.layer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            if (this.options.autoStart === true) {
                this.activate();
            }
        },
        _getMarkerFeatures: function (position, accuracy) {
            var model = this.map.model,
                upm = model.getProjectionUnitsPerMeter()
            ;
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
        },

        _showLocation: function (position, accuracy) {
            var olmap = this.map.map.olMap;
            var features;
            features = this._getMarkerFeatures(position, accuracy);
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
        },
        /**
         * Toggle GPS positioning
         *
         * @returns {self}
         */
        toggleTracking: function () {
            if (this.observer) {
                this.deactivate();
            } else {
                this.activate();
            }
        },
        /**
         * @param {(*|GeolocationPosition)} position see https://developer.mozilla.org/en-US/docs/Web/API/GeolocationPosition
         * @private
         */
        _handleGeolocationPosition: function(position) {
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
        },
        /**
         * @param {(*|GeolocationPositionError)} gle see https://developer.mozilla.org/en-US/docs/Web/API/GeolocationPositionError
         * @private
         */
        _handleGeolocationError: function(gle) {
            Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.nosignal"));
            this.deactivate();
        },
        _transformCoordinate: function(lon, lat) {
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
        },
        /**
         * Activate GPS positioning
         */
        activate: function () {
            var widget = this;
            this.layer.show();
            Mapbender.vectorLayerPool.raiseElementLayers(this);

            if (!this.observer) {
                if (this.geolocationProvider_) {
                    this.firstPosition = true;
                    this.observer = this.geolocationProvider_.watchPosition(function success(position) {
                        widget._handleGeolocationPosition(position);
                    }, function error(gle) {
                        widget._handleGeolocationError(gle);
                    }, { enableHighAccuracy: true, maximumAge: 0 });

                    $(widget.element).addClass("toolBarItemActive");
                } else {
                    Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
                }
            }
        },
        /**
         * Deactivate GPS positioning
         * @returns {object}
         */
        deactivate: function() {
            if (this.observer) {
                this.geolocationProvider_.clearWatch(this.observer);
                this.observer = null;
            }
            $(this.element).removeClass("toolBarItemActive");
            this.layer.hide();
        },

        getGPSPosition: function(callback) {
            var widget = this;

            if (this.geolocationProvider_) {
                if (this.observer) {
                    this.geolocationProvider_.clearWatch(this.observer);
                    this.observer = null;
                }
                this.layer.show();
                this.firstPosition = true;
                this.geolocationProvider_.getCurrentPosition(function success(position) {
                    var p = widget._transformCoordinate(position.coords.longitude, position.coords.latitude);
                    widget._showLocation(p, position.coords.accuracy);

                    if (typeof callback === 'function') {
                        callback(p);
                    }

                }, function error(msg) {
                    Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.nosignal"));
                    widget.deactivate();
                }, { enableHighAccuracy: true, maximumAge: 0 });
            } else {
                Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
            }
        }
    });

}(jQuery));

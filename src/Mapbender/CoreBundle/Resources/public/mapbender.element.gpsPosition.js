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
            if (!Mapbender.checkTarget("mbGpsPosition", this.options.target)) {
                return;
            }
            this.geolocationProvider_ = navigator.geolocation || null;
            // Uncomment to use mock data
            // this.geolocationProvider_ = window.Mapbender.GeolocationMock;
            if (!this.geolocationProvider_) {
                Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
                throw new Error("No geolocation support");
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
            this.options.average = Math.max(1, parseInt(this.options.average) || 1);
            this.element.on('click', $.proxy(this.toggleTracking, this));
        },

        _setup: function () {
            this.map = $('#' + this.options.target).data('mapbenderMbMap');
            this.layer = new OpenLayers.Layer.Vector();
            if (this.options.autoStart === true) {
                this.activate();
            }
        },
        _createMarker: function (position, accuracy) {
            var model = this.map.model,
                upm = model.getProjectionUnitsPerMeter()
            ;
            var markerStyle = new ol.style.Style({
                image: new ol.style.Circle({
                    radius: 10,
                    fill: null,
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

        _centerMap: function (position) {
            var extent = this.map.model.getMapExtent();
            var coordinate = [position.lon, position.lat];

            if (!this.map.model.containsCoordinate(extent, coordinate)) {
                if (this.options.follow || (this.firstPosition && this.options.centerOnFirstPosition)) {
                    this.map.model.centerXy(position.lon, position.lat);
                }
            }
        },
        _showLocation: function (position, accuracy) {
            var olmap = this.map.map.olMap;
            var features = this._getMarkerFeatures(position, accuracy);
            this.layer.removeAllFeatures();
            this.layer.addFeatures([features.point]);
            if (features.circle) {
                this.layer.addFeatures([features.circle]);
            }
            if ((this.firstPosition && this.options.centerOnFirstPosition) || this.options.follow) {
                if (features.circle && this.options.zoomToAccuracyOnFirstPosition && this.firstPosition) {
                    this.map.getModel().zoomToFeature(features.circle);
                } else {
                    if (this.firstPosition || !olmap.getExtent().containsLonLat(position)) {
                        olmap.panTo(new OpenLayers.LonLat(position.lon, position.lat));
                    }
                }
            }
            this.firstPosition = false;
            this.layer.redraw();
        },
        /**
         *
         * @param {OpenLayers.LonLat} position
         * @param {number} accuracy in meters
         * @return {Object<String, OpenLayers.Feature.Vector>}
         * @private
         */
        _getMarkerFeatures: function(position, accuracy) {
            var centerPoint = new OpenLayers.Geometry.Point(position.lon, position.lat);
            var features = {
                point: new OpenLayers.Feature.Vector(centerPoint, null, {
                    strokeColor:   "#ff0000",
                    strokeWidth:   3,
                    strokeOpacity: 1,
                    fillOpacity:   0,
                    pointRadius:   10
                })
            };
            if (accuracy) {
                var metricProjection = new OpenLayers.Projection('EPSG:900913');
                var currentProj = this.map.map.olMap.getProjectionObject();
                var metricOrigin = centerPoint.clone().transform(currentProj, metricProjection);
                var circleGeom = OpenLayers.Geometry.Polygon.createRegularPolygon(
                    metricOrigin,
                    accuracy / 2,
                    40,
                    0
                );
                circleGeom = circleGeom.transform(metricProjection, currentProj);

                features.circle = new OpenLayers.Feature.Vector(circleGeom, null, {
                    fillColor: '#FFF',
                    fillOpacity: 0.5,
                    strokeWidth: 1,
                    strokeColor: '#FFF'
                });
            }
            return features;
        },
        _zoomMap: function (position, accuracy) {
            if (!accuracy) {
                return; // no accurancy
            }

            if (!this.options.zoomToAccuracy && !(this.options.zoomToAccuracyOnFirstPosition && this.firstPosition)) {
                return;
            }

            var olmap = this.map,
                positionProj = 'EPSG:4326',
                metersProj = 'EPSG:3857',
                currentProj = olmap.model.getCurrentProjectionCode(),
                pointInMeters = olmap.model.transformCoordinate(position,positionProj,metersProj),
                calLon = pointInMeters[0] - (accuracy / 2),
                calLat = pointInMeters[1] - (accuracy / 2),
                calLonPlus = pointInMeters[0] + (accuracy / 2),
                calLatPlus = pointInMeters[1] + (accuracy / 2),
                min = olmap.model.transformCoordinate([calLon,calLat], metersProj, currentProj),
                max = olmap.model.transformCoordinate([calLonPlus,calLatPlus], metersProj, currentProj);
            var extent = {
                left: min[0],
                bottom: min[1],
                right: max[0],
                top: max[1]
            };

            olmap.model.zoomToExtent(extent);
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
            var olmap = widget.map.map.olMap;
            olmap.addLayer(this.layer);

            if (!this.observer) {
                if (this.geolocationProvider_) {
                    this.firstPosition = true;
                    this.observer = this.geolocationProvider_.watchPosition(function success(position) {
                        widget._handleGeolocationPosition(position);
                    }, function error(gle) {
                        widget._handleGeolocationError(gle);
                    }, { enableHighAccuracy: true, maximumAge: 0 });

                    $(widget.element).parent().addClass("toolBarItemActive");
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
            $(this.element).parent().removeClass("toolBarItemActive");

            var olmap = this.map.map.olMap;
            if (this.layer) {
                try {
                    olmap.removeLayer(this.layer);
                } catch (e) {
                    // unholy POI connection may cause multiple removal of layer
                }
            }
        },

        getGPSPosition: function(callback) {
            var widget = this;
            var olmap = widget.map.map.olMap;

            if (this.geolocationProvider_) {
                if (this.observer) {
                    this.geolocationProvider_.clearWatch(this.observer);
                    this.observer = null;
                }
                olmap.addLayer(this.layer);
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

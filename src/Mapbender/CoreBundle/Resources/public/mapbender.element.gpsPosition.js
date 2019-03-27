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
        layer: null,
        internalProjection: null,
        metricProjection: null,

        _create: function () {
            if (!Mapbender.checkTarget("mbGpsPosition", this.options.target)) {
                return;
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
            this.options.average = Math.max(1, parseInt(this.options.average) || 1);
            this.element.on('click', $.proxy(this.toggleTracking, this));
        },

        _setup: function () {
            this.map = $('#' + this.options.target).data('mapbenderMbMap');
            this.layer = new OpenLayers.Layer.Vector();
            this.metricProjection = new OpenLayers.Projection('EPSG:900913');
            this.internalProjection = new OpenLayers.Projection("EPSG:4326");
            if (this.options.autoStart === true) {
                this.activate();
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
                    olmap.zoomToExtent(features.circle.geometry.getBounds());
                } else {
                    if (this.firstPosition || !olmap.getExtent().containsLonLat(position)) {
                        olmap.panTo(position);
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
                var currentProj = this.map.map.olMap.getProjectionObject();
                var metricOrigin = centerPoint.clone().transform(currentProj, this.metricProjection);
                var circleGeom = OpenLayers.Geometry.Polygon.createRegularPolygon(
                    metricOrigin,
                    accuracy / 2,
                    40,
                    0
                );
                circleGeom = circleGeom.transform(this.metricProjection, currentProj);

                features.circle = new OpenLayers.Feature.Vector(circleGeom, null, {
                    fillColor: '#FFF',
                    fillOpacity: 0.5,
                    strokeWidth: 1,
                    strokeColor: '#FFF'
                });
            }
            return features;
        },

        defaultAction: function() {
            this.activate();
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
         * Activate GPS positioning
         *
         * @returns {object}
         */
        activate: function () {
            var widget = this;
            var olmap = widget.map.map.olMap;
            olmap.addLayer(this.layer);

            if (navigator.geolocation && !this.observer) {
                this.firstPosition = true;
                this.observer = navigator.geolocation.watchPosition(function success(position) {
                    var newProj = olmap.getProjectionObject(),
                        p = new OpenLayers.LonLat(position.coords.longitude, position.coords.latitude);

                    p.transform(widget.internalProjection, newProj);

                    // Averaging: Building a queue...
                    widget.stack.push(p);
                    if (widget.stack.length > widget.options.average) {
                        widget.stack.splice(0, 1);
                    }

                    // ...and reducing it.
                    p = _.reduce(widget.stack, function (memo, p) {
                        memo.lon += p.lon / widget.stack.length;
                        memo.lat += p.lat / widget.stack.length;
                        return memo;
                    }, new OpenLayers.LonLat(0, 0));

                    widget._showLocation(p, position.coords.accuracy);

                }, function error(msg) {
                    Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.nosignal"));
                    widget.deactivate();
                }, { enableHighAccuracy: true, maximumAge: 0 });

                $(widget.element).parent().addClass("toolBarItemActive");
            } else {
                Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
            }
            return widget;
        },

        /**
         * Deactivate GPS positioning
         * @returns {object}
         */
        deactivate: function() {
            if (this.observer) {
                navigator.geolocation.clearWatch(this.observer);
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

            if (navigator.geolocation) {
                if (this.observer) {
                    navigator.geolocation.clearWatch(this.observer);
                    this.observer = null;
                }
                olmap.addLayer(this.layer);
                this.firstPosition = true;
                navigator.geolocation.getCurrentPosition(function success(position) {
                    var newProj = olmap.getProjectionObject(),
                        p = new OpenLayers.LonLat(position.coords.longitude, position.coords.latitude);

                    p.transform(widget.internalProjection, newProj);

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

            return widget;
        }
    });

}(jQuery));

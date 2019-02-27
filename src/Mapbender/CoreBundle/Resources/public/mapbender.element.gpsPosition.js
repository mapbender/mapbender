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
            zoomToAccuracy: false,
            centerOnFirstPosition: true,
            zoomToAccuracyOnFirstPosition: true
        },
        map: null,
        observer: null,
        firstPosition: true,
        stack: [],
        layer: null,

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
            if (this.options.autoStart === true) {
                this.activate();
            }
        },

        _createMarker: function (position, accuracy) {
            var self = this,
                olmap = this.map.map.olMap,
                metersProj,
                currentProj,
                originInMeters,
                accuracyPoint,
                radius,
                circle
            ;
            this.layer.removeAllFeatures();
            var center = new OpenLayers.Geometry.Point(position.lon, position.lat);
            if (accuracy) {
                metersProj = new OpenLayers.Projection('EPSG:900913');
                currentProj = olmap.getProjectionObject();

                originInMeters = new OpenLayers.LonLat(position.lon, position.lat);
                originInMeters.transform(currentProj, metersProj);

                accuracyPoint = new OpenLayers.LonLat(originInMeters.lon + (accuracy / 2), originInMeters.lat + (accuracy / 2));
                accuracyPoint.transform(metersProj, currentProj);

                radius = accuracyPoint.lon - position.lon;
                var circleGeom = OpenLayers.Geometry.Polygon.createRegularPolygon(
                    center,
                    radius,
                    40,
                    0
                );

                circle = new OpenLayers.Feature.Vector(circleGeom, null, {
                    fillColor: '#FFF',
                    fillOpacity: 0.5,
                    strokeWidth: 1,
                    strokeColor: '#FFF'
                });
                this.layer.addFeatures([circle]);
            }

            var point = new OpenLayers.Feature.Vector(center, null, {
                strokeColor:   "#ff0000",
                strokeWidth:   3,
                strokeOpacity: 1,
                fillOpacity:   0,
                pointRadius:   10
            });
            this.layer.addFeatures([point]);
            olmap.addLayer(this.layer);
        },

        _centerMap: function (point) {
            var olmap = this.map.map.olMap,
                extent = olmap.getExtent();
            if (extent.containsLonLat(point) === false) // point is in extent?
            {
                if (this.options.follow) {
                    olmap.panTo(point);
                } else if (this.firstPosition && this.options.centerOnFirstPosition) {
                    olmap.panTo(point);
                } 
            }
        },

        _zoomMap: function (point, accuracy) {
            if (!accuracy) {
                return; // no accurancy
            }

            if (!this.options.zoomToAccuracy && !(this.options.zoomToAccuracyOnFirstPosition && this.firstPosition)) {
                return;
            }

            var olmap = this.map.map.olMap,
                metersProj = new OpenLayers.Projection("EPSG:900913"),
                currentProj = olmap.getProjectionObject(),
                pointInMeters = point.transform(currentProj, metersProj),
                min = new OpenLayers.LonLat(pointInMeters.lon - (accuracy / 2), pointInMeters.lat - (accuracy / 2)).transform(metersProj, currentProj),
                max = new OpenLayers.LonLat(pointInMeters.lon + (accuracy / 2), pointInMeters.lat + (accuracy / 2)).transform(metersProj, currentProj);

            olmap.zoomToExtent(new OpenLayers.Bounds(min.lon, min.lat, max.lon, max.lat));
        },

        defaultAction: function() {
            return this.activate();
        },

        /**
         * Is button active?
         */
        isActive: function() {
            var widget = this;
            return widget.observer !== null;
        },

        /**
         * Toggle GPS positioning
         *
         * @returns {self}
         */
        toggleTracking: function () {
            if (this.isActive()) {
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

            if (navigator.geolocation) {
                widget.observer = navigator.geolocation.watchPosition(function success(position) {
                    var proj = new OpenLayers.Projection("EPSG:4326"),
                        newProj = olmap.getProjectionObject(),
                        p = new OpenLayers.LonLat(position.coords.longitude, position.coords.latitude);

                    p.transform(proj, newProj);

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

                    widget._createMarker(p, position.coords.accuracy);
                    widget._centerMap(p);
                    widget._zoomMap(p, position.coords.accuracy);

                    if (widget.firstPosition) {
                        widget.firstPosition = false;
                    }

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
            this.firstPosition = true;

            var olmap = this.map.map.olMap;
            if (this.layer) {
                olmap.removeLayer(this.layer);
            }
        },

        getGPSPosition: function(callback) {
            var widget = this;
            var openLayerMap = widget.map.map.olMap;

            if (navigator.geolocation) {
                widget.observer = navigator.geolocation.getCurrentPosition(function success(position) {
                    var epsgProjectionCode = new OpenLayers.Projection("EPSG:4326"),
                        newProj = openLayerMap.getProjectionObject(),
                        p = new OpenLayers.LonLat(position.coords.longitude, position.coords.latitude);

                    p.transform(epsgProjectionCode, newProj);

                    /*
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
                    */

                    widget._createMarker(p, position.coords.accuracy);
                    widget._centerMap(p);
                    widget._zoomMap(p, position.coords.accuracy);

                    if (typeof callback === 'function') {
                        callback();
                    }

                }, function error(msg) {
                    Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.nosignal"));
                    widget.deactivate();
                }, { enableHighAccuracy: true, maximumAge: 0 });

                $(widget.element).parent().addClass("toolBarItemActive");
            } else {
                Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
            }

            return widget;
        }
    });

}(jQuery));

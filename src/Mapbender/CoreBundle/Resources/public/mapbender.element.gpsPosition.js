/*jslint browser: true, nomen: true*/
/*globals Mapbender, OpenLayers, _, jQuery*/

(function ($) {
    'use strict';
    /*jslint nomen: true*/
    /**
     * Description of what this does.
     *
     * @author Arne Schubert <atd.schubert@gmail.com>
     * @namespace mapbender.mbGpsPosition
     */
    $.widget("mapbender.mbGpsPosition", {
        options: {
            follow: false,
            average: 1,
            zoomToAccuracy: false,
            centerOnFirstPosition: true,
            zoomToAccuracyOnFirstPosition: true,
            accurancyStyle: {
                fillColor: '#FFF',
                fillOpacity: 0.5,
                strokeWidth: 1,
                strokeColor: '#FFF'
            }
        },
        map: null,
        observer: null,
        firstPosition: true,
        stack: [],

        _create: function () {
            var self = this,
                me = $(this.element);

            if (!Mapbender.checkTarget("mbGpsPosition", this.options.target)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));

            if (!this.options.average) {
                this.options.average = 1;
            }

            me.click(function () {
                //me.parent().addClass("toolBarItemActive");
                self.activate();
            });
        },

        _setup: function () {
            this.map = $('#' + this.options.target).data('mapbenderMbMap');
            if (this.options.autoStart === true) {
                this.toggleTracking();
            }
        },

        _createMarker: function (position, accuracy) {
            var self = this,
                olmap = this.map.map.olMap,
                markers,
                size,
                icon,
                candidates = olmap.getLayersByName('Markers'),

                vector,
                metersProj,
                currentProj,
                originInMeters,
                accuracyPoint,
                differance,
                circle;
            if (candidates.length > 0) {
                markers = candidates[0];
                olmap.removeLayer(markers);
                markers.destroy();
            }
            markers = new OpenLayers.Layer.Markers('Markers');
            olmap.addLayer(markers);
            size = new OpenLayers.Size(20, 20);
            icon = new OpenLayers.Icon(Mapbender.configuration.application.urls.asset + 'bundles/mapbendercore/image/marker_fett.gif', size);
            markers.addMarker(new OpenLayers.Marker(position, icon));

            // Accurancy
            if (!accuracy) {
                return;
            }
            candidates = olmap.getLayersByName('Accuracy');
            if (candidates.length > 0) {
                olmap.removeLayer(candidates[0]);
                candidates[0].destroy();
            }
            vector = new OpenLayers.Layer.Vector('Accuracy');
            olmap.addLayer(vector);

            metersProj = new OpenLayers.Projection('EPSG:900913');
            currentProj = olmap.getProjectionObject();

            originInMeters = new OpenLayers.LonLat(position.lon, position.lat);
            originInMeters.transform(currentProj, metersProj);

            accuracyPoint = new OpenLayers.LonLat(originInMeters.lon + (accuracy / 2), originInMeters.lat + (accuracy / 2));
            accuracyPoint.transform(metersProj, currentProj);

            differance = accuracyPoint.lon - position.lon;

            circle = new OpenLayers.Feature.Vector(
                OpenLayers.Geometry.Polygon.createRegularPolygon(

                    new OpenLayers.Geometry.Point(position.lon, position.lat),
                    differance,
                    40,
                    0
                ),
                {},
                self.options.accurancyStyle
            );
            vector.addFeatures([circle]);
        },

        _centerMap: function (point) {
            var olmap = this.map.map.olMap,
                extent = olmap.getExtent();
            if (extent.containsLonLat(point) === false || true === this.options.follow) {
                olmap.panTo(point);
            } else if (this.firstPosition && this.options.centerOnFirstPosition) {
                olmap.panTo(point);
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

        /**
         * Toggle GPS positioning
         *
         * @returns {self}
         */
        toggleTracking: function () {
            if (this.observer) {
                return this.deactivate();
            }
            return this.activate();
        },
        /**
         * Activate GPS positioning
         *
         * @returns {self}
         */
        activate: function () {
            var olmap = this.map.map.olMap,
                self = this;
            if (navigator.geolocation) {
                self.observer = navigator.geolocation.watchPosition(function success(position) {
                    var proj = new OpenLayers.Projection("EPSG:4326"),
                        newProj = olmap.getProjectionObject(),
                        p = new OpenLayers.LonLat(position.coords.longitude, position.coords.latitude);

                    p.transform(proj, newProj);

                    // Averaging: Building a queue...
                    self.stack.push(p);
                    if (self.stack.length > self.options.average) {
                        self.stack.splice(0, 1);
                    }

                    // ...and reducing it.
                    p = _.reduce(self.stack, function (memo, p) {
                        memo.lon += p.lon / self.stack.length;
                        memo.lat += p.lat / self.stack.length;
                        return memo;
                    }, new OpenLayers.LonLat(0, 0));

                    self._createMarker(p, position.coords.accuracy);
                    self._centerMap(p);
                    self._zoomMap(p, position.coords.accuracy);

                    if (self.firstPosition) {
                        self.firstPosition = false;
                    }
                }, function error(msg) {
                    Mapbender.error(msg);
                }, { enableHighAccuracy: true, maximumAge: 0 });

                $(this.element).parent().addClass("toolBarItemActive");

            } else {
                Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
            }
            return this;
        },
        /**
         * Deactivate GPS positioning
         *
         * @param
         * @returns {self}
         */
        deactivate: function () {
            if (this.observer) {
                navigator.geolocation.clearWatch(this.observer);
                $(this.element).parent().removeClass("toolBarItemActive");
                this.firstPosition = true;
                this.observer = null;
            }
            // Delete Markers
            var olmap = this.map.map.olMap,
                markers,
                candidates = olmap.getLayersByName('Markers');
            if (candidates.length > 0) {
                markers = candidates[0];
                olmap.removeLayer(markers);
                markers.destroy();
            }

            candidates = olmap.getLayersByName('Accuracy');
            if (candidates.length > 0) {
                olmap.removeLayer(candidates[0]);
                candidates[0].destroy();
            }
            return this;
        },
        /**
         * Determinate ready state of plugin
         *
         * @param {mapbender.mbGpsPosition~readyCallback} callback - Callback to run on plugin ready
         * @returns {self}
         */
        ready: function (callback) {
            if (this.readyState === true) {
                /**
                 * Description of what this does.
                 *
                 * @callback mapbender.mbGpsPosition~readyCallback
                 * @param
                 */
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
            return this;
        },
        _ready: function () {
            var i;
            for (i = 0; i <  this.readyCallbacks.length; i += 1) {
                this.readyCallbacks.splice(0, 1)();
            }
            this.readyState = true;
        }
    });

}(jQuery));

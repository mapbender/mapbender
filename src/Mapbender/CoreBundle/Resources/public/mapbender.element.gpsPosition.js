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
        model: null,
        observer: null,
        firstPosition: true,
        stack: [],
        olGeolocation: null,
        geolocationLayerId: null,

        _create: function () {
            var widget = this;
            var element = $(widget.element);
            var options = widget.options;
            var target = options.target;

            if (!Mapbender.checkTarget("mbGpsPosition", target)) {
                return;
            }

            Mapbender.elementRegistry.onElementReady(target, $.proxy(widget._setup, widget));

            if (!options.average) {
                options.average = 1;
            }

            element.click(function () {
                if(widget.isActive()) {
                    widget.deactivate();
                } else {
                    widget.activate();
                }
                return false;
            });
        },

        _setup: function () {
            this.map = Mapbender.elementRegistry.listWidgets().mapbenderMbMap;
            this.model = this.map.model;
            var layerStyle = model.createVectorLayerStyle();

            var drawLayerId = model.createDrawControl(type, "gpsPosition", layerStyle, {
                'drawstart': function(event) {
                    var obvservable = {value: null};
                    console.log(model.eventFeatureWrapper(event, model.onFeatureChange, [function(f) {
                        this._handleModify(model.getLineStringLength(f))
                    }.bind(this), obvservable]));
                    window.a = obvservable;
                }.bind(this)
            });
            if (this.options.autoStart === true) {
                this.toggleTracking();
            }
        },

        _createMarker: function (position, accuracy) {
            var self = this,
                olmap = this.map.map.olMap,
                markers,
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

            markers = new OpenLayers.Layer.Vector('Markers');
            var point = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(position.lon, position.lat), null, {
                strokeColor:   "#ff0000",
                strokeWidth:   3,
                strokeOpacity: 1,
                strokeLinecap: "butt",
                fillOpacity:   0,
                pointRadius:   10
            });
            markers.addFeatures([point]);
            olmap.addLayer(markers);

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

        /**
         * Is button active?
         */
        isActive: function() {
            var widget = this;
            return widget.observer != null;
        },

        /**
         * Toggle GPS positioning
         *
         * @returns {self}
         */
        toggleTracking: function () {
            var widget = this;
            if (widget.isActive()) {
                return widget.deactivate();
            }
            return widget.activate();
        },
        /**
         * Activate GPS positioning
         *
         * @returns {self}
         */
        activate: function () {
            var widget = this;
            if (navigator.geolocation) {
                widget.observer = navigator.geolocation.watchPosition(function success(position) {
                    // var proj = new OpenLayers.Projection("EPSG:4326"),
                    //     newProj = olmap.getProjectionObject(),
                    //     p = new OpenLayers.LonLat(position.coords.longitude, position.coords.latitude);
                    //
                    // p.transform(proj, newProj);

                    //ol4
                    var model = widget.model,
                        map = widget.map,
                        proj = 'EPSG:4326',
                        newProj = model.getCurrentProjectionCode();

                    var p = model.transformCoordinate([position.coords.longitude, position.coords.latitude], proj, newProj);

                    var iconStyle = new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 6,
                            fill: new ol.style.Fill({
                                color: '#3399CC'
                            }),
                            stroke: new ol.style.Stroke({
                                color: '#fff',
                                width: 2
                            })
                        })
                    });

                    // add an empty iconFeature to the source of the layer
                    var iconFeature = new ol.Feature();
                    var iconSource = new ol.source.Vector({
                        features: [iconFeature]
                    });

                    iconFeature.setGeometry(new ol.geom.Point(p));

                    var geolocationLayerId = widget.model.createVectorLayer({
                        source: iconSource,
                        style : iconStyle
                    },{},'gpsPosition');

                    model.setCenter(p);
                    model.setZoom(10);

                    widget.geolocationLayerId = geolocationLayerId;
                    console.log(geolocationLayerId);


                    // Averaging: Building a queue...
                    widget.stack.push(p);
                    if (widget.stack.length > widget.options.average) {
                        widget.stack.splice(0, 1);
                    }

                    // ...and reducing it.
                    // p = _.reduce(widget.stack, function (memo, p) {
                    //     memo.lon += p.lon / widget.stack.length;
                    //     memo.lat += p.lat / widget.stack.length;
                    //     return memo;
                    // }, new OpenLayers.LonLat(0, 0));

                    p = _.reduce(widget.stack, function (memo, p) {
                        memo.lon += p.lon / widget.stack.length;
                        memo.lat += p.lat / widget.stack.length;
                        return memo;
                    }, ol.proj.transform([0, 0], proj, newProj));

                     //widget._createMarker(p, position.coords.accuracy);
                    // widget._centerMap(p);
                    // widget._zoomMap(p, position.coords.accuracy);

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
         *
         * @param
         * @returns {self}
         */
        deactivate: function() {
            if(this.isActive()) {
                navigator.geolocation.clearWatch(this.observer);
                $(this.element).parent().removeClass("toolBarItemActive");
                this.firstPosition = true;
                this.observer = null;
            }
            // Delete Markers
            // var olmap = this.map.map.olMap,
            //     markers,
            //     candidates = olmap.getLayersByName('Markers');
            // if (candidates.length > 0) {
            //     markers = candidates[0];
            //     olmap.removeLayer(markers);
            //     markers.destroy();
            // }
            //
            // candidates = olmap.getLayersByName('Accuracy');
            // if (candidates.length > 0) {
            //     olmap.removeLayer(candidates[0]);
            //     candidates[0].destroy();
            // }

            var model= this.map.model;
            var layersToRemove = model.removeVectorLayerbyName('gpsPosition');
            console.log('delete: '+layersToRemove);
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

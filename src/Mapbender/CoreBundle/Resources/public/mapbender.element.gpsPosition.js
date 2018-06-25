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
                //fillColor: '#FFF',
                //fillOpacity: 0.5,
                //strokeWidth: 1,
                //strokeColor: '#FFF'
                stroke: new ol.style.Stroke({
                    color: 'rgba(255,255,255, 1)',
                    width: 1
                }),
                fill: new ol.style.Fill({
                    color: 'rgba(255,255,255, 0.5)'
                })
            },
            markerStyle: {
                radius: 10,
                fill: null,
                stroke: new ol.style.Stroke({
                    color: 'rgba(255, 0, 0, 1)',
                    width: 3,
                    lineCap: 'butt'
                })
            }
        },
        map: null,
        model: null,
        observer: null,
        firstPosition: true,
        stack: [],
        olGeolocation: null,
        geolocationAccuracyId: null,
        geolocationMarkerId: null,

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

            if (this.options.autoStart === true) {
                this.toggleTracking();
            }
        },

        _createMarker: function (position, accuracy) {
            var self = this,
                olmap = self.map,
                markerId = this.geolocationMarkerId,
                accuracyId = this.geolocationAccuracyId,
                positionProj = 'EPSG:4326',
                metersProj = 'EPSG:3857',
                currentProj = olmap.model.getCurrentProjectionCode(),
                transPositionCurrentProj = olmap.model.transformCoordinate(position,positionProj,currentProj),
                pointInMeters = olmap.model.transformCoordinate(position,positionProj,metersProj),
                accuracyOrgPoint,
                differance;

            var markerStyle = new ol.style.Style({
                image: new ol.style.Circle({
                    radius: 10,
                    fill: null,
                    stroke: new ol.style.Stroke({
                        color: 'rgba(255, 0, 0, 1)',
                        width: 3,
                        lineCap: 'butt'
                    })
                })
            });

            // add an empty iconFeature to the source of the layer
            var iconFeature = new ol.Feature(
                new ol.geom.Point(transPositionCurrentProj)
            );
            var markersSource = new ol.source.Vector({
                features: [iconFeature]
            });

            // check vectorlayer and set an new Source
            if (markerId){
                var markerVectorLayer = olmap.model.getVectorLayerByNameId('Position',markerId);
                markerVectorLayer.setSource(markersSource);
            }else {
                markerId = olmap.model.createVectorLayer({
                    source: markersSource,
                    style: markerStyle
                }, 'Position');
            }

            // set geolocationMarkerId
            this.geolocationMarkerId = markerId;


            // Accurancy
            if (!accuracy) {
                return;
            }

            var accurancyStyle = new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: 'rgba(255,255,255, 1)',
                    width: 1
                }),
                fill: new ol.style.Fill({
                    color: 'rgba(255,255,255, 0.5)'
                })
            });

            accuracyOrgPoint = [pointInMeters[0] + (accuracy / 2), pointInMeters[1] + (accuracy / 2)];
            differance = accuracyOrgPoint[0] - pointInMeters[0];

            var accurancyFeature = new ol.Feature(
                new ol.geom.Circle(transPositionCurrentProj,differance)
            );
            var accurancySource = new ol.source.Vector({
                features: [accurancyFeature]
            });

            // check vectorlayer and set an new Source
            if (accuracyId) {
                var accuracyVectorLayer = olmap.model.getVectorLayerByNameId('Accuracy', accuracyId);
                accuracyVectorLayer.setSource(accurancySource);
            }else{
                accuracyId = olmap.model.createVectorLayer({
                    source: accurancySource,
                    style : accurancyStyle
                },'Accuracy');
            }

            // set geolocationMarkerId
            this.geolocationAccuracyId = accuracyId;

            // create console messages
            console.log('GPS-Position: '+ transPositionCurrentProj + ' with Accuracy: '+ accuracy);

        },

        _centerMap: function (position) {
            var olmap = this.map,
                extent = olmap.model.getMapExtent(),
                positionProj = 'EPSG:4326',
                currentProj = olmap.model.getCurrentProjectionCode(),
                transPositionCurrentProj = olmap.model.transformCoordinate(position,positionProj,currentProj);

            if (olmap.model.containsCoordinate(extent, transPositionCurrentProj) === false) // point is in extent?
            {
                if (this.options.follow) {
                    olmap.model.setCenter(transPositionCurrentProj);
                } else if (this.firstPosition && this.options.centerOnFirstPosition) {
                    olmap.model.setCenter(transPositionCurrentProj);
                }
            }
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
                    var model = widget.model,
                        proj = 'EPSG:4326',
                        newProj = model.getCurrentProjectionCode(),
                        p = model.addCoordinate([position.coords.longitude, position.coords.latitude]);
                        // transCoord= model.transformCoordinate(coord, proj, newProj),
                        // p = model.toLonLat(transCoord,newProj);

                    // Averaging: Building a queue...
                    widget.stack.push(p);
                    if (widget.stack.length > widget.options.average) {
                        widget.stack.splice(0, 1);
                    }

                    // ...and reducing it.
                    p = _.reduce(widget.stack, function (memo, p) {
                        memo.lon += p[0] / widget.stack.length;
                        memo.lat += p[1] / widget.stack.length;
                        return memo;
                    });

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
            var olmap =  this.map,
                 candidates = olmap.model.getVectorLayerByNameId('Position', this.geolocationMarkerId);

            if (candidates) {
                candidates.getSource().clear();
                olmap.model.removeVectorLayer('Position', this.geolocationMarkerId);
                this.geolocationMarkerId = null;

            }

             candidates = olmap.model.getVectorLayerByNameId('Accuracy', this.geolocationAccuracyId);
             if (candidates) {
                 candidates.getSource().clear();
                 olmap.model.removeVectorLayer('Accuracy', this.geolocationAccuracyId);
                 this.geolocationAccuracyId = null;
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

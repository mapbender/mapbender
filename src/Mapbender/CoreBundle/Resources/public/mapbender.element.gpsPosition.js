/**
 *
 * @author Arne Schubert <atd.schubert@gmail.com>
 * @namespace mapbender.mbGpsPosition
 */
(function ($) {
    'use strict';

    $.widget("mapbender.mbGpsPosition", {
        options: {
            follow: false,
            average: 1,
            zoomToAccuracy: false,
            centerOnFirstPosition: true,
            zoomToAccuracyOnFirstPosition: true,
            accurancyStyle: {
                stroke: new ol.style.Stroke({
                    color: 'rgba(255,255,255, 1)',
                    width: 1
                }),
                fill: new ol.style.Fill({
                    color: 'rgba(255,255,255, 0.5)'
                })
            },
            circleStyle: {
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
                markerId = self.geolocationMarkerId,
                accuracyId = self.geolocationAccuracyId,
                positionProj = 'EPSG:4326',
                metersProj = 'EPSG:3857',
                currentProj = olmap.model.getCurrentProjectionCode(),
                transPositionCurrentProj = olmap.model.transformCoordinate(position,positionProj,currentProj),
                currentUnit = olmap.model.getUnitsOfCurrentProjection(),
                mpu = olmap.model.getMeterPersUnit(currentUnit),
                pointInMeters = olmap.model.transformCoordinate(position,positionProj,metersProj),
                accuracyOrgPoint,
                differancePerUnit,
                accurancyStyleParams = self.options.accurancyStyle,
                circleStyleParams = self.options.circleStyle
            ;

            var markerStyle = new ol.style.Style({
                image: new ol.style.Circle(circleStyleParams)
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

            var accurancyStyle = new ol.style.Style(accurancyStyleParams);

            accuracyOrgPoint = [pointInMeters[0] + (accuracy / 2), pointInMeters[1] + (accuracy / 2)];
            differancePerUnit = (accuracyOrgPoint[0] - pointInMeters[0]) / mpu;

            var accurancyFeature = new ol.Feature(
                new ol.geom.Circle(transPositionCurrentProj, differancePerUnit)
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
            var widget = this;
            if (widget.isActive()) {
                return widget.deactivate();
            }
            return widget.activate();
        },

        /**
         * Activate GPS positioning
         *
         * @returns {object}
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
         * @returns {object}
         */
        deactivate: function() {
            var widget = this;

            if(widget.isActive()) {
                navigator.geolocation.clearWatch(widget.observer);
                $(widget.element).parent().removeClass("toolBarItemActive");
                widget.firstPosition = true;
                widget.observer = null;
            }

            // Check if id of Position and Accuracy has been set
            if ( (this.geolocationMarkerId !== null) && (this.geolocationAccuracyId !== null) ) {
                // Delete Markers
                var olmap = this.map,
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

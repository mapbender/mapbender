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
            },
            zoomToAccuracyOnFirstPosition: true
        },
        map: null,
        model: null,
        observer: null,
        firstPosition: true,
        stack: [],
        olGeolocation: null,
        geolocationAccuracyId: null,
        geolocationMarkerId: null,
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
        _createMarker: function (position, accuracy) {
            var self = this,
                mbMap = self.map,
                markerId = self.geolocationMarkerId,
                accuracyId = self.geolocationAccuracyId,
                positionProj = 'EPSG:4326',
                metersProj = 'EPSG:3857',
                currentProj = mbMap.model.getCurrentProjectionCode(),
                transPositionCurrentProj = mbMap.model.transformCoordinate(position,positionProj,currentProj),
                currentUnit = mbMap.model.getCurrentProjectionUnits(),
                mpu = mbMap.model.getMeterPersUnit(currentUnit),
                pointInMeters = mbMap.model.transformCoordinate(position,positionProj,metersProj),
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
                var markerVectorLayer = mbMap.model.getVectorLayerByNameId('Position',markerId);
                markerVectorLayer.setSource(markersSource);
            }else {
                markerId = mbMap.model.createVectorLayer({
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
                var accuracyVectorLayer = mbMap.model.getVectorLayerByNameId('Accuracy', accuracyId);
                accuracyVectorLayer.setSource(accurancySource);
            }else{
                accuracyId = mbMap.model.createVectorLayer({
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
            this.firstPosition = false;
            this.layer.redraw();
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
                    if (4) {
                    var newProj = model.getCurrentProjectionCode(),
                        p = model.addCoordinate([position.coords.longitude, position.coords.latitude]);
                        // transCoord= model.transformCoordinate(coord, proj, newProj),
                        // p = model.toLonLat(transCoord,newProj);
                    }else {
                    var newProj = olmap.getProjectionObject(),
                        p = new OpenLayers.LonLat(position.coords.longitude, position.coords.latitude);

                    p.transform(widget.internalProjection, newProj);
                    }

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

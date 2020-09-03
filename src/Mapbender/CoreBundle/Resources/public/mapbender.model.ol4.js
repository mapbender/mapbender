window.Mapbender = Mapbender || {};
window.Mapbender.MapModelOl4 = (function() {
    'use strict';

    var rad2deg = 360. / (2 * Math.PI);
    var deg2rad = 2 * Math.PI / 360;

    /**
     * @param {Object} mbMap
     * @constructor
     */
    function MapModelOl4(mbMap) {
        Mapbender.MapModelBase.apply(this, arguments);
        this._geojsonFormat = new ol.format.GeoJSON();
        this._wktFormat = new ol.format.WKT();
        this._initMap();
        window.Mapbender.vectorLayerPool = window.Mapbender.VectorLayerPool.factory(Mapbender.mapEngine, this.olMap);
        this.displayPois(this._poiOptions);
    }

    MapModelOl4.prototype = Object.create(Mapbender.MapModelBase.prototype);
    Object.assign(MapModelOl4.prototype, {
        constructor: MapModelOl4,
        _geojsonFormat: null,
        sourceTree: [],



    _initMap: function() {
        var maxExtent = Mapbender.mapEngine.transformBounds(this.mapMaxExtent, this._configProj, this._startProj);
        var startExtent = Mapbender.mapEngine.transformBounds(this.mapStartExtent, this._configProj, this._startProj);

        this.viewOptions_ = this.calculateViewOptions_(this._startProj, this.mbMap.options.scales, maxExtent, this.mbMap.options.dpi);
        var view = new ol.View(this.viewOptions_);
        // remove zoom after creating view
        delete this.viewOptions_['zoom'];
        this.olMap = new ol.Map({
            view: view,
            controls: [],
            target: this.mbMap.element.attr('id')
        });
        this._patchNavigation(this.olMap);
        this.map = new Mapbender.NotMapQueryMap(this.mbMap.element, this.olMap);

        this._initEvents(this.olMap, this.mbMap);
        this._setInitialView(this.olMap, startExtent, this.mbMap.options, this._startProj);

        this.initializeSourceLayers();
        this.processUrlParams();
    },
    _setInitialView: function(olMap, startExtent, mapOptions, srsName) {
        var resolution = this._getInitialResolution(olMap, startExtent, mapOptions, srsName);
        var center = this._getInitialCenter(mapOptions, startExtent);
        var view = olMap.getView();
        view.setCenter(center);
        view.setResolution(resolution);
    },
    /**
     * @param {ol.Map} olMap
     * @private
     */
    _patchNavigation: function(olMap) {
        var interactions = olMap.getInteractions().getArray();
        for (var i = 0; i < interactions.length; ++i) {
            var interaction = interactions[i];
            if (interaction instanceof ol.interaction.MouseWheelZoom) {
                // Reign in built-in trackpad specialization for better stability on high-resolution pads
                /** @type {(ol.interaction.MouseWheelZoom)} */ interaction;
                interaction.constrainResolution_ = true;    // disable transient touchpad zoom overshooting (inconsistent with wheel)
                interaction.trackpadEventGap_ = 60;         // discrete event debounce milliseconds; reduced from original 400
                interaction.trackpadDeltaPerZoom_ = 2000;   // increased from original 300
            }
        }
    },
    _initEvents: function(olMap, mbMap) {
        var self = this;
        olMap.on('moveend', function() {
            var scales = self._getScales();
            var zoom = self.getCurrentZoomLevel();
            self.sourceTree.map(function(source) {
                self._checkSource(source, true);
            });
            // @todo: figure out how to distinguish zoom change from panning
            mbMap.element.trigger('mbmapzoomchanged', {
                mbMap: mbMap,
                zoom: zoom,
                scale: scales[zoom],
                scaleExact: self._getFractionalScale()
            });
        });
        olMap.on("singleclick", function(data) {
            $(mbMap.element).trigger('mbmapclick', {
                mbMap: mbMap,
                pixel: data.pixel.slice(),
                coordinate: data.coordinate.slice()
            });
        });
        var initRotation = function(view) {
            view.on('change:rotation', function() {
                $(mbMap.element).trigger('mbmaprotationchanged', {
                    mbMap: mbMap,
                    degrees: self.getViewRotation()
                });
            });
        };
        initRotation(olMap.getView());
        // Rebind view events on replacement of view object (happens on SRS switch)
        olMap.on('change:view', function(e) {
            initRotation(e.target.getView());
        });
    },
    /**
     * Injects native layers into the map at the "natural" position for the source.
     * This supports multiple layers for the same source.
     *
     * @param {Mapbender.Source} source
     * @param {Array<ol.Layer>} olLayers
     * @private
     */
    _spliceLayers: function(source, olLayers) {
        var sourceIndex = this.sourceTree.indexOf(source);
        if (sourceIndex === -1) {
            console.error("Can't splice layers for source with unknown position", source, olLayers);
            throw new Error("Can't splice layers for source with unknown position");
        }
        var olMap = this.olMap;
        var layerCollection = olMap.getLayers();
        var afterLayer = layerCollection[0]; // hopefully, that's a base layer
        for (var s = sourceIndex - 1; s >= 0; --s) {
            var previousSource = this.sourceTree[s];
            var previousLayer = (previousSource.nativeLayers.slice(-1))[0];
            if (previousLayer) {
                afterLayer = previousLayer;
                break;
            }
        }
        var baseIndex = layerCollection.getArray().indexOf(afterLayer) + 1;
        for (var i = 0; i < olLayers.length; ++i) {
            var olLayer = olLayers[i];
            layerCollection.insertAt(baseIndex + i, olLayer);
            olLayer.mbConfig = source;
            this._initLayerEvents(olLayer, source, i);
        }
    },
    _initLayerEvents: function(olLayer, source, sourceLayerIndex) {
        var mbMap = this.mbMap;
        var nativeSource = olLayer.getSource();
        var engine = Mapbender.mapEngine;
        var tmp = {
            pendingLoads: 0
        };
        nativeSource.on(["tileloadstart", "imageloadstart"], function() {
            if (!tmp.pendingLoads) {
                mbMap.element.trigger('mbmapsourceloadstart', {
                    mbMap: mbMap,
                    source: source
                });
            }
            ++tmp.pendingLoads;
        });
        nativeSource.on(["tileloaderror", "imageloaderror"], function(data) {
            tmp.pendingLoads = Math.max(0, tmp.pendingLoads - 1);
            if (engine.getLayerVisibility(olLayer)) {
                mbMap.element.trigger('mbmapsourceloaderror', {
                    mbMap: mbMap,
                    source: source
                });
            }
        });
        nativeSource.on(["tileloadend", "imageloadend"], function() {
            tmp.pendingLoads = Math.max(0, tmp.pendingLoads - 1);
            if (!tmp.pendingLoads) {
                mbMap.element.trigger('mbmapsourceloadend', {
                    mbMap: mbMap,
                    source: source
                });
            }
        });
    },
    zoomToFullExtent: function() {
        var currentSrsName = this.getCurrentProjectionCode();
        var extent = Mapbender.mapEngine.transformBounds(this.mapMaxExtent, this._configProj, currentSrsName);
        this.setExtent(extent);
    },
    /**
     * @param {Array<number>} boundsOrCoords
     */
    setExtent: function(boundsOrCoords) {
        var bounds;
        if ($.isArray(boundsOrCoords)) {
            bounds = boundsOrCoords;
        } else {
            bounds = [
                boundsOrCoords.left,
                boundsOrCoords.bottom,
                boundsOrCoords.right,
                boundsOrCoords.top
            ];
        }
        this.olMap.getView().fit(bounds);
    },
    /**
     * @param {Number} x projected
     * @param {Number} y projected
     * @param {Object} [options]
     * @param {Number} [options.minScale]
     * @param {Number} [options.maxScale]
     * @param {Number|String} [options.zoom]
     */
    centerXy: function(x, y, options) {
        var resolution = null;
        var zoomOption = (options || {}).zoom;
        if (typeof zoomOption === 'number') {
            resolution = this.olMap.getView().getResolutionForZoom(zoomOption);
        } else {
            var resNow = this.olMap.getView().getResolution();
            if (typeof ((options || {}).minScale) === 'number') {
                var minRes = this.scaleToResolution(options.minScale);
                if (resNow < minRes) {
                    resolution = minRes;
                }
            }
            if (typeof ((options || {}).maxScale) === 'number') {
                var maxRes = this.scaleToResolution(options.maxScale);
                if (resNow > maxRes) {
                    resolution = maxRes;
                }
            }
        }
        var view = this.olMap.getView();
        view.setCenter([x, y]);
        if (resolution !== null) {
            view.setResolution(resolution);
        }
    },
    /**
     * @param {ol.Feature} feature
     * @param {Object} [options]
     * @param {number=} options.buffer in meters
     * @param {number=} options.minScale
     * @param {number=} options.maxScale
     * @param {boolean=} options.center to forcibly recenter map (default: true); otherwise
     *      just keeps feature in view
     */
    zoomToFeature: function(feature, options) {
        var center_ = !options || (options.center || typeof options.center === 'undefined');
        var bounds = this._getBufferedFeatureBounds(feature, (options && options.buffer) || 0);

        var view = this.olMap.getView();
        var zoom0 = Math.floor(view.getZoomForResolution(view.getResolutionForExtent(bounds)));
        var zoom = this._adjustZoom(zoom0, options);
        var zoomNow = this.getCurrentZoomLevel();
        var viewExtent = view.calculateExtent();
        var featureInView = ol.extent.intersects(viewExtent, bounds);
        if (center_ || zoom !== zoomNow || !featureInView) {
            view.setCenter(ol.extent.getCenter(bounds));
            this.setZoomLevel(zoom, false);
        }
    },
    /**
     * @param {ol.Feature} feature
     * @param {Object} [options]
     * @param {number=} options.buffer in meters
     * @param {boolean=} options.center to forcibly recenter map (default: true); otherwise
     *      just keeps feature in view
     */
    panToFeature: function(feature, options) {
        var center_ = !options || (options.center || typeof options.center === 'undefined');
        var bounds = this._getBufferedFeatureBounds(feature, (options && options.buffer) || 0);

        var view = this.olMap.getView();
        var viewExtent = view.calculateExtent();
        var featureInView = ol.extent.intersects(viewExtent, bounds);
        if (center_ || !featureInView) {
            view.setCenter(ol.extent.getCenter(bounds));
        }
    },
    setZoomLevel: function(level, allowTransitionEffect) {
        var _level = this._clampZoomLevel(level);
        if (_level !== this.getCurrentZoomLevel(false)) {
            if (allowTransitionEffect) {
                this.olMap.getView().animate({zoom: _level, duration: 300});
            } else {
                this.olMap.getView().setZoom(_level);
            }
        }
    },
    _getFractionalZoomLevel: function() {
        return this.olMap.getView().getZoom();
    },
    _getFractionalScale: function() {
        var resolution = this.olMap.getView().getResolution();
        return this.resolutionToScale(resolution);
    },
    zoomIn: function() {
        this.setZoomLevel(this.getCurrentZoomLevel() + 1, true);
    },
    zoomOut: function() {
        this.setZoomLevel(this.getCurrentZoomLevel() - 1, true);
    },
    getCurrentProjectionUnits: function() {
        var proj;
        if (this.olMap) {
            proj = this.olMap.getView().getProjection();
        } else {
            proj = ol.proj.get(this._startProj);
        }
        return proj.getUnits() || 'degrees';
    },
    /**
     * @return {String}
     */
    getCurrentProjectionCode: function() {
        if (this.olMap) {
            return this.olMap.getView().getProjection().getCode();
        } else {
            return this._startProj;
        }
    },
    /**
     * Parses a single (E)WKT feature from text. Returns the engine-native feature.
     *
     * @param {String} text
     * @param {String} [sourceSrsName]
     * @return {OpenLayers.Feature.Vector}
     */
    parseWktFeature: function(text, sourceSrsName) {
        var ewktMatch = text.match(/^SRID=([^;]*);(.*)$/);
        if (ewktMatch) {
            return this.parseWktFeature(ewktMatch[2], ewktMatch[1]);
        }
        var targetSrsName = this.olMap.getView().getProjection().getCode();
        return this._wktFormat.readFeatureFromText(text, {
            dataProjection: sourceSrsName || null,
            featureProjection: targetSrsName
        });
    },
    /**
     * @param {*} data
     * @param {String} [sourceSrsName]
     * @return {*}
     */
    parseGeoJsonFeature: function(data, sourceSrsName) {
        var feature = this._geojsonFormat.readFeature(data);
        var geometry = feature && feature.getGeometry();
        if (geometry && sourceSrsName) {
            var targetSrsName = this.olMap.getView().getProjection().getCode();
            geometry.transform(sourceSrsName, targetSrsName);
        }
        return feature;
    },
    /**
     *
     * @param {ol.Feature} feature
     * @return {Object}
     */
    featureToGeoJsonGeometry: function(feature) {
        return this._geojsonFormat.writeFeatureObject(feature).geometry;
    },
    /**
     * Centered feature rotation (counter-clockwise)
     *
     * @param {ol.Feature} feature
     * @param {Number} degrees
     */
    rotateFeature: function(feature, degrees) {
        var geometry = feature.getGeometry();
        var deg2rad = 2 * Math.PI / 360;
        var center = ol.extent.getCenter(geometry.getExtent());
        geometry.rotate(degrees * deg2rad, center);
    },
    /**
     * Returns the center coordinate of the given feature as an array, ordered x, y (aka lon, lat)
     * @param {ol.Feature} feature
     * @returns {Array<Number>}
     */
    getFeatureCenter: function(feature) {
        return ol.extent.getCenter(feature.getGeometry().getExtent());
    },
    _getScales: function() {
        // @todo: fractional zoom: method must not be called
        var view = this.olMap.getView();
        var dpi = parseFloat(this.mbMap.options.dpi) || 72;
        var self = this;
        return view.getResolutions().map(function(res) {
            var scale0 = self.resolutionToScale(res, dpi);
            return parseInt('' + Math.round(scale0));
        });
    },
    _countScales: function() {
        return this.olMap.getView().getResolutions().length;
    },

    _changeLayerProjection: function(olLayer, newProj) {
        var nativeSource = olLayer.getSource();
        if (nativeSource) {
            nativeSource.projection_ = newProj;
        }
    },
    /**
     * Update map view according to selected projection
     *
     * @param {string} srsNameFrom
     * @param {string} srsNameTo
     */
    _changeProjectionInternal: function(srsNameFrom, srsNameTo) {
        var engine = Mapbender.mapEngine;
        var currentView = this.olMap.getView();
        var fromProj = ol.proj.get(srsNameFrom);
        var toProj = ol.proj.get(srsNameTo);
        var i, j, source, olLayers;
        if (!fromProj || !fromProj.getUnits() || !toProj || !toProj.getUnits()) {
            console.error("Missing / incomplete transformations (log order from / to)", [srsNameFrom, srsNameTo], [fromProj, toProj]);
            throw new Error("Missing / incomplete transformations");
        }
        for (i = 0; i < this.sourceTree.length; ++i) {
            source = this.sourceTree[i];
            if (source.checkRecreateOnSrsSwitch(srsNameFrom, srsNameTo)) {
                Mapbender.mapEngine.removeLayers(this.olMap, source.getNativeLayers());
                source.destroyLayers();
            } else {
                olLayers = source.getNativeLayers();
                for (j = 0; j < olLayers.length; ++ j) {
                    this._changeLayerProjection(olLayers[j], toProj);
                }
            }
        }

        // transform projection extent (=max extent)
        // DO NOT use currentView.getProjection().getExtent() here!
        // Going back and forth between SRSs, there is extreme drift in the
        // calculated values. Always start from the configured maxExtent.
        var newMaxExtent = Mapbender.mapEngine.transformBounds(this.mapMaxExtent, this._configProj, srsNameTo);
        var zoomLevel = this.getCurrentZoomLevel();

        var currentCenter = currentView.getCenter();
        var newCenter = ol.proj.transform(currentCenter, fromProj, toProj);

        var mbMapOptions = this.mbMap.options;
        var resolutionOptions = this.calculateViewOptions_(srsNameTo, mbMapOptions.scales, newMaxExtent, mbMapOptions.dpi);
        var newViewOptions = Object.assign({}, this.viewOptions_, resolutionOptions, {
            projection: srsNameTo,
            center: newCenter,
            rotation: currentView.getRotation(),
            zoom: zoomLevel
        });

        var newView = new ol.View(newViewOptions);
        this.olMap.setView(newView);
        for (i = 0; i < this.sourceTree.length; ++i) {
            source = this.sourceTree[i];
            if (source.checkRecreateOnSrsSwitch(srsNameFrom, srsNameTo)) {
                olLayers = source.initializeLayers(srsNameTo);
                for (j = 0; j < olLayers.length; ++j) {
                    var olLayer = olLayers[j];
                    engine.setLayerVisibility(olLayer, false);
                }
                this._spliceLayers(source, olLayers);
            }
        }
        var self = this;
        self.sourceTree.map(function(source) {
            self._checkSource(source, false);
        });
    },

        /**
         * Returns the center coordinate of the current map view as an array, ordered x, y (aka lon, lat)
         * @return {Array<Number>}
         */
        getCurrentMapCenter: function() {
            return this.olMap.getView().getCenter();
        },
        /**
         * @return {Array<Number>}
         */
        getCurrentExtentArray: function() {
            return this.olMap.getView().calculateExtent();
        },
        /**
         * @returns {number} in degrees
         */
        getViewRotation: function() {
            return rad2deg * this.olMap.getView().getRotation();
        },
        /**
         * @param {Number} degrees
         * @param {boolean} animate
         */
        setViewRotation: function(degrees, animate) {
            var radians = deg2rad * degrees;
            var view = this.olMap.getView();
            if (animate) {
                view.animate({rotation: radians, duration: 400});
            } else {
                view.setRotation(radians);
            }
        },
        /**
         * @param {ol.layer.Vector} olLayer
         * @param {ol.Feature} feature
         * @param {Number} resolution
         * @return {Object}
         */
        extractSvgFeatureStyle: function(olLayer, feature, resolution) {
            var styleOptions = {};
            var layerStyleFn = olLayer.getStyleFunction();
            var featureStyleFn = feature.getStyleFunction();
            var olStyle = (featureStyleFn || layerStyleFn)(feature, resolution);
            if (Array.isArray(olStyle)) {
                olStyle = olStyle[0];
            }
            /** @var {ol.style.Style} olStyle */
            Object.assign(styleOptions, this._extractSvgGeometryStyle(olStyle));
            var text = olStyle.getText();
            var label = text && text.getText();
            if (label) {
                Object.assign(styleOptions, this._extractSvgLabelStyle(text), {
                    label: label
                });
            }
            return styleOptions;
        },
        /**
         * @param {ol.style.Style} olStyle
         * @return {Object}
         * @private
         */
        _extractSvgGeometryStyle: function(olStyle) {
            var style = {};
            var image = olStyle.getImage();
            if (image && (image instanceof ol.style.Circle)){
                olStyle = image;
            }
            var fill = olStyle.getFill();
            var stroke = olStyle.getStroke();

            var scale =  image.getScale() || 1;
            if (fill) {
                Object.assign(style, Mapbender.StyleUtil.cssColorToSvgRules(fill.getColor(), 'fillColor', 'fillOpacity'))
            }
            if (stroke) {
                Object.assign(style, Mapbender.StyleUtil.cssColorToSvgRules(stroke.getColor(), 'strokeColor', 'strokeOpacity'));
                style['strokeWidth'] = stroke.getWidth();
                style['strokeDashstyle'] = stroke.getLineDash() ||  'solid';
            }
            if (image && (image instanceof ol.style.RegularShape)) {
                style['pointRadius'] = image.getRadius() || 6;
            }
            if (image && (image instanceof ol.style.Icon)) {
                var anchor = image.getAnchor();
                var iconElement = image.getImage(1);
                var iconUrl = iconElement && iconElement.src;
                if (anchor !== null && iconUrl) {
                    var size = image.getSize() || [iconElement.naturalWidth, iconElement.naturalHeight];
                    Object.assign(style, {
                        externalGraphic: iconUrl,
                        graphicXOffset: -anchor[0],
                        graphicYOffset: -anchor[1],
                        graphicWidth: size[0] * scale,
                        graphicHeight: size[1]* scale
                    });
                }
            }
            return style;
        },
        /**
         * @param {ol.style.Text} olTextStyle
         * @return {Object}
         * @private
         */
        _extractSvgLabelStyle: function(olTextStyle) {
            var style = {};
            var stroke = olTextStyle.getStroke();
            Object.assign(style,
                Mapbender.StyleUtil.cssColorToSvgRules(olTextStyle.getFill().getColor(), 'fontColor', 'fontOpacity'),
                Mapbender.StyleUtil.cssColorToSvgRules(stroke.getColor(), 'labelOutlineColor', 'labelOutlineOpacity')
            );
            style['labelOutlineWidth'] = stroke.getWidth();

            style['labelAlign'] = [olTextStyle.getTextAlign().slice(0, 1), olTextStyle.getTextBaseline().slice(0, 1)].join('');
            style['labelXOffset'] = olTextStyle.getOffsetX();
            style['labelYOffset'] = olTextStyle.getOffsetY();
            return style;
        },
        /**
         * @param {Number} x
         * @param {Number} y
         * @param {Element} content
         * @private
         * @return {Promise}
         */
        openPopupInternal_: function(x, y, content) {
            var olMap = this.olMap;
            // @todo: use native Promise (needs polyfill)
            var def = $.Deferred();
            // Always make a new clone of the template
            var $popup = $(
                '<div class="mbmappopup"><span class="close-btn -fn-close"><i class="fa fas fa-times"></i></span></div>'
            );
            $popup.append(content);
            $popup.append('<div class="clear"></div>');
            var overlay = new ol.Overlay({element: $popup.get(0)});
            olMap.addOverlay(overlay);
            overlay.setPosition([x, y]);
            $popup.one('click', '-fn-close', function() {
                olMap.removeOverlay(overlay);
                def.resolve();
            });
            return def.promise();
        },
        /**
         * @param {String} srsName
         * @param {Array<Number>}scales
         * @param {Array<Number>=} [maxExtent]
         * @param {Number=} [dpi]
         * @return {{}}
         * @private
         */
        calculateViewOptions_: function(srsName, scales, maxExtent, dpi) {
            var viewOptions = {
                projection: srsName
            };
            if (scales && scales.length) {
                var upm = Mapbender.mapEngine.getProjectionUnitsPerMeter(srsName);
                var inchesPerMetre = 39.37;
                var dpi_ = dpi || 72;
                viewOptions['resolutions'] = scales.map(function(scale) {
                    return scale * upm / (inchesPerMetre * dpi_);
                });
            } else {
                viewOptions.zoom = 7; // hope for the best
            }
            if (maxExtent) {
                viewOptions.extent = maxExtent;
            }
            return viewOptions;
        }
    });

    return MapModelOl4;
}());

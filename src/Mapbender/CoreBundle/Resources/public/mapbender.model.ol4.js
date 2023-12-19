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
        var maxExtent = Mapbender.mapEngine.transformBounds(this.mapMaxExtent, this._configProj, this.initialViewParams.srsName);

        this.viewOptions_ = this.calculateViewOptions_(this.initialViewParams, this.mbMap.options, maxExtent);
        let view;
        try {
            view = new ol.View(this.viewOptions_);
        } catch(e) {
            if (e instanceof TypeError) {
                this.viewOptions_.projection = window.Mapbender.MapEngineOl4.fallbackProjection;
                view = new ol.View(this.viewOptions_);
            } else {
                throw e;
            }
        }
        // remove zoom after creating view
        delete this.viewOptions_['zoom'];
        try {
            var shareParams = this._decodeViewparamFragment();
            var deg2rad = 2 * Math.PI / 360;
            view.setRotation(deg2rad * shareParams.rotation);
        } catch (e) {
            // ignore missing / invalid share fragment
        }

        this.olMap = new ol.Map({
            view: view,
            controls: [],
            target: this.mbMap.element.attr('id')
        });
        this._patchNavigation(this.olMap);
        this.map = new Mapbender.NotMapQueryMap(this.mbMap.element, this.olMap);

        this._initEvents(this.olMap, this.mbMap);

        this._setInitialView(this.olMap, this.initialViewParams, this.mbMap.options);

        this.processUrlParams();
        this.initializeSourceLayers(this.sourceTree);
        this._startShare();
    },
    /**
     * @param {ol.PluggableMap} olMap
     * @param {mmViewParams} viewParams
     * @param {Object} mapOptions
     * @private
     */
    _setInitialView: function(olMap, viewParams, mapOptions) {
        var resolution = this.scaleToResolution(viewParams.scale, mapOptions.dpi, viewParams.srsName);
        var view = olMap.getView();
        view.setCenter(viewParams.center);
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
            self.sourceTree.map(function(source) {
                self._checkSource(source, true);
            });
        });
        olMap.on("singleclick", function(data) {
            $(mbMap.element).trigger('mbmapclick', {
                mbMap: mbMap,
                pixel: data.pixel.slice(),
                coordinate: data.coordinate.slice()
            });
        });
        this._initViewEvents(olMap.getView(), mbMap);
        // Rebind view events on replacement of view object (happens on SRS switch)
        olMap.on('change:view', function(e) {
            self._initViewEvents(e.target.getView(), mbMap);
        });
    },
    _initViewEvents: function(view, mbMap) {
        var self = this;
        view.on(['change:resolution', 'change:center', 'change:rotation'], function(e) {
            switch (e.type) {
                case 'change:resolution':
                    var scales = self._getScales();
                    var zoom = self.getCurrentZoomLevel();
                    mbMap.element.trigger('mbmapzoomchanged', {
                        mbMap: mbMap,
                        zoom: zoom,
                        scale: scales[zoom],
                        scaleExact: self._getFractionalScale()
                    });
                    break;
                case 'change:rotation':
                    $(mbMap.element).trigger('mbmaprotationchanged', {
                        mbMap: mbMap,
                        degrees: self.getViewRotation()
                    });
                    break;
                default:
                    break;
            }
            mbMap.element.trigger('mbmapviewchanged', {
                mbMap: mbMap,
                params: self.getCurrentViewParams()
            });
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
     * @param {Number} [options.zoom]
     * @param {boolean} [options.ignorePadding]
     */
    centerXy: function(x, y, options) {
        var feature = new ol.Feature(new ol.geom.Point([x, y]));
        var ztfOptions = Object.assign({}, options, {
            buffer: 0
        });
        if (typeof (options || {}).zoom === 'number') {
            var fixedScale = this._getScales()[this._clampZoomLevel(options.zoom)];
            ztfOptions.minScale = fixedScale;
            ztfOptions.maxScale = fixedScale;
            delete ztfOptions.zoom;
        }
        this.zoomToFeature(feature, ztfOptions);
    },
    /**
     * @param {ol.Feature} feature
     * @param {Model~BufferOptions} [options]
     * @param {boolean=} options.center to forcibly recenter map (default: true); otherwise
     *      just keeps feature in view
     */
    zoomToFeature: function(feature, options) {
        var center_ = !options || (options.center || typeof options.center === 'undefined');
        var bounds = this._getBufferedFeatureBounds(feature, options);
        var view = this.olMap.getView();
        var padding = !(options || {}).ignorePadding && this.getMapPadding_() || [0, 0, 0, 0];
        var sizeFull = this.olMap.getSize();
        var sizeLimited = [
            Math.max(0, sizeFull[0] - padding[1] - padding[3]),
            Math.max(0, sizeFull[1] - padding[0] - padding[2])
        ];

        /** @see https://github.com/openlayers/openlayers/blob/main/src/ol/View.js#L1001 */
        var viewCenter = this.getCenterWithoutPadding_(padding);
        var viewExtent = ol.extent.getForViewAndSize(viewCenter, view.getResolution(), view.getRotation(), sizeLimited);
        var featureInView = ol.extent.containsExtent(viewExtent, bounds);
        if (center_ || !featureInView) {
            var fitOptions = {
                padding: padding
            };
            if (options && options.minScale) {
                fitOptions.minResolution = this.scaleToResolution(options.minScale);
            }
            view.fit(bounds, fitOptions);
        }
        if (options && options.maxScale && options.maxScale >= (options.minScale || 0)) {
            var maxResolution = this.scaleToResolution(options.maxScale);
            if (view.getResolution() >= maxResolution) {
                view.setResolution(maxResolution);
            }
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
    /**
     * @param {MouseEvent} event
     * @return {mmClickData}
     */
    locateClickEvent: function(event) {
        var pixel = this.olMap.getEventPixel(event);
        return {
            pixel: pixel,
            coordinate: this.olMap.getCoordinateFromPixel(pixel)
        };
    },
    _getFractionalZoomLevel: function() {
        return this.olMap.getView().getZoom();
    },
    _getFractionalScale: function() {
        var resolution = this.olMap.getView().getResolution();
        // Constrain resolution. This fixes extracting (transient / animating) values that exceed limits set
        // by max extent / max scale denominator and which cannot be restored. Unrestorable scales break
        // view param history
        var constrained = this.olMap.getView().getConstrainedResolution(resolution);
        return this.resolutionToScale(constrained);
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
            proj = ol.proj.get(this.initialViewParams.srsName);
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
            return this.initialViewParams.srsName;
        }
    },
    /**
     * Get gedesic units per meter at given point. UPMs are returned separately
     * for vertical and horizontal axes.
     *
     * @param {Array<Number>} point
     * @param {String} [srsName]
     * @returns {{v: number, h: number}}
     */
    getUnitsPerMeterAt: function(point, srsName) {
        var xform84 = proj4(srsName || this.getCurrentProjectionCode(), 'EPSG:4326').forward;
        var left84 = xform84([point[0] - 0.5, point[1]]);
        var right84 = xform84([point[0] + 0.5, point[1]]);
        var bottom84 = xform84([point[0], point[1] - 0.5]);
        var top84 = xform84([point[0], point[1] + 0.5]);

        var distanceH = ol.sphere.getDistance(left84, right84);
        var distanceV = ol.sphere.getDistance(bottom84, top84);
        return {
            h: 1.0 / distanceH,
            v: 1.0 / distanceV
        };
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
    dumpGeoJsonFeatures: function(features, layer, resolution, includeStyle) {
        // Sort features like the canvas renderer would
        /** @see https://github.com/openlayers/openlayers/blob/v6.4.3/src/ol/renderer/canvas/VectorLayer.js#L651 */
        /** @see https://github.com/openlayers/openlayers/blob/v6.4.3/src/ol/renderer/vector.js#L37 */
        var orderFn = layer.getRenderOrder() || function(a, b) {
            return parseInt(ol.getUid(a), 10) - parseInt(ol.getUid(b), 10);
        };
        features.sort(orderFn);

        var self = this;
        var gjf = this._geojsonFormat;
        var dumpFeature = this._geojsonFormat.writeFeatureObject.bind(this._geojsonFormat);
        var dumpGeometry = function(geometry) {
            var geometry_ = (geometry instanceof ol.geom.Circle) ? ol.geom.Polygon.fromCircle(geometry, 128) : geometry;
            return gjf.writeGeometryObject(geometry_);
        };
        var layerStyleFn = layer.getStyleFunction();
        var featuresDump = [];
        for (var i = 0; i < features.length; ++i) {
            /** @type {ol.Feature} */
            var feature = features[i];
            var styles = (feature.getStyleFunction() || layerStyleFn)(feature, resolution);
            if (!Array.isArray(styles)) {
                styles = [styles];
            }
            var baseFeatureDump = dumpFeature(feature);
            var components = styles.map(function(style) {
                var featureDump = Object.assign({}, baseFeatureDump);
                var geom = (style.getGeometryFunction())(feature);
                featureDump.geometry = dumpGeometry(geom);
                if (includeStyle) {
                    featureDump.style = self._dumpSvgStyle(style);
                }
                return featureDump;
            }).filter(function(dump) {
                return (!includeStyle) || !!dump.style;
            });
            if (components.length) {
                Array.prototype.push.apply(featuresDump, components);
            }
        }
        return featuresDump;
    },
    /**
     * @param {ol.style.Style} style
     * @return {Object|null}
     * @private
     */
    _dumpSvgStyle: function(style) {
        var styleRules = this._extractSvgGeometryStyle(style);
        var text = style.getText();
        var label = text && text.getText();
        if (label) {
            Object.assign(styleRules, this._extractSvgLabelStyle(text), {
                label: label
            });
        }
        if (!style.getFill()) {
            styleRules['fillColor'] = '#000000';
            styleRules['fillOpacity'] = 0;
        }
        if (!style.getStroke()) {
            styleRules['strokeColor'] = '#000000';
            styleRules['strokeOpacity'] = 0;
        }

        if (style.getFill() || style.getStroke() || style.getImage() || label) {
            Mapbender.StyleUtil.fixSvgStyleAssetUrls(styleRules);
            return styleRules;
        } else {
            return null;
        }
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
                source.destroyLayers(this.olMap);
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
        var zoomLevel = this.getCurrentZoomLevel(false);

        var currentCenter = currentView.getCenter();
        var newCenter = ol.proj.transform(currentCenter, fromProj, toProj);

        var fakeViewParams = {
            srsName: srsNameTo,
            rotation: 0     // Not worth doing rad2deg + deg2rad; we will replace it anyway
        };
        var mbMapOptions = this.mbMap.options;
        var resolutionOptions = this.calculateViewOptions_(fakeViewParams, mbMapOptions, newMaxExtent);
        var newViewOptions = Object.assign({}, this.viewOptions_, resolutionOptions, {
            projection: srsNameTo,
            center: newCenter,
            rotation: currentView.getRotation(),
            zoom: zoomLevel
        });

        var newView = new ol.View(newViewOptions);
        this.olMap.setView(newView);
    },

        /**
         * Returns the center coordinate of the current map view as an array, ordered x, y (aka lon, lat)
         * @return {Array<Number>}
         */
        getCurrentMapCenter: function() {
            // Constrain center. This fixes extracting (transient / animating) values that exceed limits allowable
            // by max extent and which cannot be restored. Unrestorable center breaks view param history.
            var view = this.olMap.getView();
            var resolution = view.getConstrainedResolution(view.getResolution());
            return view.getConstrainedCenter(view.getCenter(), resolution);
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
            Mapbender.StyleUtil.fixSvgStyleAssetUrls(styleOptions);
            return styleOptions;
        },
        /**
         * @param {String|Array<Number>} olColor
         * @param {String} colorProp
         * @param {String} opacityProp
         * @return {Object}
         */
        _extractColor: function(olColor, colorProp, opacityProp) {
            var rule;
            if (typeof olColor === 'string') {
                rule = olColor;
            } else {
                if (olColor.length === 4) {
                    rule = ['rgba(', olColor.join(', '), ')'].join('');
                } else {
                    rule = ['rgb(', olColor.join(', '), ')'].join('');
                }
            }
            return Mapbender.StyleUtil.cssColorToSvgRules(rule, colorProp, opacityProp);
        },
        /**
         * @param {ol.style.Style} olStyle
         * @return {Object}
         * @private
         */
        _extractSvgGeometryStyle: function(olStyle) {
            var style = {};
            var image = olStyle.getImage();
            var circleImage = image && (image instanceof ol.style.Circle) && image;
            var fill = olStyle.getFill() || (circleImage && circleImage.getFill());
            var stroke = olStyle.getStroke() || (circleImage && circleImage.getStroke());

            var scale = image && image.getScale() || 1;
            if (fill) {
                Object.assign(style, this._extractColor(fill.getColor(), 'fillColor', 'fillOpacity'));
            }
            if (stroke) {
                Object.assign(style, this._extractColor(stroke.getColor(), 'strokeColor', 'strokeOpacity'));
                style['strokeWidth'] = stroke.getWidth();
                var lineDash = stroke.getLineDash();
                style['strokeDashstyle'] = lineDash && lineDash.length && lineDash.slice() || 'solid';
            }
            if (image && (image instanceof ol.style.RegularShape)) {
                style['pointRadius'] = image.getRadius() || 6;
            }
            if (image && (image instanceof ol.style.Icon)) {
                Object.assign(style, this._extractSvgIconStyle(image));
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
                this._extractColor(olTextStyle.getFill().getColor(), 'fontColor', 'fontOpacity')
            );
            var font = olTextStyle.getFont();
            if (stroke) {
                this._extractColor(stroke.getColor(), 'labelOutlineColor', 'labelOutlineOpacity')
                style['labelOutlineWidth'] = stroke.getWidth();
            }
            if (font) {
                var fontParts = font.split(/\s+/);
                // Digitizer custom styling quirk: support "normal" font-variant as an alias for "regular" font-weight
                if (/^bold|regular|italic|normal$/.test(fontParts[0] || '')) {
                    style['fontWeight'] = fontParts[0] !== 'normal' && fontParts[0] || 'regular';
                    fontParts.splice(0, 1);
                }
                var sizeRatio = (this.mbMap && this.mbMap.options.dpi || 96.) / (96. * (window.devicePixelRatio || 1));
                if (/^\d+\w+$/.test(fontParts[0] || '')) {
                    style['fontSize'] = [sizeRatio * parseFloat(fontParts[0]), 'px'].join('');
                    fontParts.splice(0, 1);
                } else {
                    // Default font size is 10px
                    style['fontSize'] = [sizeRatio * 10, 'px'].join('');
                }
                var fontFamily = fontParts.join(' ');
                if (fontFamily) {
                    style['fontFamily'] = fontFamily;
                }
            }
            var align = (olTextStyle.getTextAlign() || '')[0] || 'c';
            var baseline = (olTextStyle.getTextBaseline() || '')[0] || 'm';
            style['labelAlign'] = [align, baseline].join('');
            style['labelXOffset'] = olTextStyle.getOffsetX();
            style['labelYOffset'] = olTextStyle.getOffsetY();
            return style;
        },
        /**
         * @param {ol.style.Icon} image
         * @return {Object}
         * @private
         */
        _extractSvgIconStyle: function(image) {
            var anchor = image.getAnchor();
            var scale = image.getScale() || 1;
            var iconElement = image.getImage(1);
            var iconUrl = iconElement && iconElement.src;
            if (anchor !== null && iconUrl) {
                var size = image.getSize() || [iconElement.naturalWidth, iconElement.naturalHeight];
                // Normalize scale (multiple possible formats) to two-component array
                if (!Array.isArray(scale)) {
                    scale = [scale, scale];
                }
                if (scale.length < 2) {
                    scale.push(scale[0]);
                }
                var rules = {
                    externalGraphic: iconUrl,
                    graphicXOffset: -anchor[0] * scale[0],
                    graphicYOffset: -anchor[1] * scale[1],
                    graphicWidth: size[0] * scale[0],
                    graphicHeight: size[1]* scale[1]
                };
                Mapbender.StyleUtil.fixSvgStyleAssetUrls(rules);
                return rules;
            } else {
                return {};
            }
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
            $popup.one('click', '.-fn-close', function() {
                olMap.removeOverlay(overlay);
                def.resolve();
            });
            return def.promise();
        },
        /**
         * @param {mmViewParams} viewParams
         * @param {Object} mapOptions
         * @param {Array<Number>=} [maxExtent]
         * @return {{}}
         * @private
         */
        calculateViewOptions_: function(viewParams, mapOptions, maxExtent) {
            var deg2rad = 2 * Math.PI / 360;
            var scales = mapOptions.scales;
            var dpi = mapOptions.dpi || 72;

            var viewOptions = {
                projection: viewParams.srsName,
                rotation: deg2rad * viewParams.rotation
            };
            if (scales && scales.length) {
                var upm = Mapbender.mapEngine.getProjectionUnitsPerMeter(viewParams.srsName);
                var inchesPerMetre = 39.37;
                viewOptions['resolutions'] = scales.map(function(scale) {
                    return scale * upm / (inchesPerMetre * dpi);
                });
            } else {
                viewOptions.zoom = 7; // hope for the best
            }
            if (maxExtent) {
                viewOptions.extent = maxExtent;
            }
            /** @see https://github.com/openlayers/openlayers/blob/v6.4.3/src/ol/View.js#L148 */
            viewOptions.constrainResolution = !!mapOptions.fixedZoomSteps;

            return viewOptions;
        },
        /**
         * @return {Array<number>}
         * @private
         */
        getMapPadding_: function() {
            var viewRect = this.olMap.getViewport().getBoundingClientRect();
            // Padding order is top, right, bottom, left, compatible with ol.View fit method
            /** @see https://github.com/openlayers/openlayers/blob/main/src/ol/View.js#L83 */
            var padding = [0, 0, 0, 0];
            var sidepane = $('.sidePane').get(0);
            var toolbars = $('.toolBar').get();
            for (var i = 0; i < toolbars.length; ++i) {
                var toolbarRect = toolbars[i].getBoundingClientRect();
                if (toolbarRect.bottom <= viewRect.top + 0.5 * viewRect.height) {
                    // Top
                    padding[0] = Math.max(toolbarRect.bottom - viewRect.top, padding[0], 0);
                } else {
                    // Bottom
                    padding[2] = Math.max(viewRect.bottom - toolbarRect.top, padding[2], 0);
                }
            }
            var sidepaneRect = sidepane && sidepane.getBoundingClientRect();
            if (sidepaneRect) {
                if (sidepaneRect.left <= viewRect.left + 0.5 * viewRect.width) {
                    // Left
                    padding[3] = Math.max(sidepaneRect.right - viewRect.left, padding[3], 0);
                } else {
                    // Right
                    padding[1] = Math.max(viewRect.right - sidepaneRect.left, padding[1], 0);
                }
            }
            return padding;
        },
        getCenterWithoutPadding_: function(padding) {
            return this.adjustCenterForPadding_(this.olMap.getView().getCenter(), padding);
        },
        adjustCenterForPadding_: function(center, padding, factor) {
            var resolution = this.olMap.getView().getResolution();
            var padding_ = padding || this.getMapPadding_();
            var factor_ = factor || 1.0;
            return [
                center[0] + resolution * factor_ * 0.5 * (padding_[3] - padding_[1]),
                center[1] - resolution * factor_ * 0.5 * (padding_[0] - padding_[2])
            ];
        }
    });

    return MapModelOl4;
}());

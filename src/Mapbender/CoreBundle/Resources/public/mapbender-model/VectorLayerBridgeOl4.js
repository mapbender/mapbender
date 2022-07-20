window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerBridgeOl4 = (function() {
    function VectorLayerBridgeOl4(olMap) {
        window.Mapbender.VectorLayerBridge.call(this, olMap);
        this.wrappedLayer_ = new ol.layer.Vector({
            source: new ol.source.Vector({wrapX: false})
        });
        this.markerStyle_ = null;
    }
    VectorLayerBridgeOl4.prototype = Object.create(Mapbender.VectorLayerBridge.prototype);
    Object.assign(VectorLayerBridgeOl4.prototype, {
        constructor: VectorLayerBridgeOl4,
        clear: function() {
            this.wrappedLayer_.getSource().clear();
        },
        show: function() {
            this.wrappedLayer_.setVisible(true);
        },
        hide: function() {
            this.wrappedLayer_.setVisible(false);
        },
        retransform: function(fromSrsName, toSrsName) {
            this.wrappedLayer_.getSource().forEachFeature(/** @param {ol.Feature} feature */ function(feature) {
                var geometry = feature.getGeometry();
                if (geometry) {
                    geometry.transform(fromSrsName, toSrsName);
                }
            });
        },
        /**
         * @param {Array<ol.Feature>} features
         */
        addNativeFeatures: function(features) {
            this.wrappedLayer_.getSource().addFeatures(features);
        },
        /**
         * @param {Array<ol.Feature>} features
         */
        removeNativeFeatures: function(features) {
            var source = this.wrappedLayer_.getSource();
            for (var i = 0; i < features.length; ++i) {
                source.removeFeature(features[i]);
            }
        },
        setBuiltinMarkerStyle: function(name) {
            switch (name) {
                default:
                    if (name === null) {
                        throw new Error("Unknown marker style " + name);
                    } else {
                        this.markerStyle_ = null;
                    }
                    break;
                case 'poiIcon':
                    // @todo: move poi icon options out of mbMap widget
                    var poiOptions = $['mapbender']['mbMap'].prototype.options.poiIcon;
                    var iconUrl = Mapbender.configuration.application.urls.asset + poiOptions.image;
                    this.markerStyle_ = new ol.style.Style({
                        image: new ol.style.Icon({
                            src: iconUrl,
                            imgSize: [poiOptions.width, poiOptions.height],
                            anchor: [-poiOptions.xoffset, -poiOptions.yoffset],
                            anchorOrigin: ol.style.IconOrigin.TOP_LEFT,
                            anchorXUnits: ol.style.IconAnchorUnits.PIXELS,
                            anchorYUnits: ol.style.IconAnchorUnits.PIXELS
                        })
                    });
                    break;
            }
        },
        customizeStyle: function(styles) {
            var svgWithDefaults = Mapbender.StyleUtil.addSvgDefaults(styles);
            var defaultFn = ol.style.Style.defaultFunction;
            // default style function ignores feature + resolution arguments, fortunately
            var defaultStyle = (defaultFn())[0].clone();
            var callables = this.detectCallables_(svgWithDefaults);
            if (!(callables.fillColor || callables.fillOpacity || callables.strokeColor || callables.strokeOpacity)) {
                this.applyFillAndStroke_(defaultStyle, svgWithDefaults);
            }
            var textCallbacks = this._prepareTextStyleCallbacks(styles);
            var textStyle = textCallbacks['setText'] && this._prepareTextStyle(svgWithDefaults);
            // we know that the default style function does not populate a text style, so we do not need to merge anything
            if (textStyle) {
                defaultStyle.setText(textStyle);
            }
            var self = this;
            var customFeatureStyleFn = function(feature, resolution) {
                var style = defaultStyle.clone();
                var setterNames = Object.keys(textCallbacks);
                var textStyle = defaultStyle.getText();
                for (var i = 0; i < setterNames.length; ++i) {
                    var setterName = setterNames[i];
                    var callback = textCallbacks[setterName];
                    var value = callback(feature, resolution);
                    textStyle[setterName](value);
                }
                style.setText(textStyle);
                if (callables.fillColor || callables.fillOpacity || callables.strokeColor || callables.strokeOpacity) {
                    var resolved = self.resolveCallables_(svgWithDefaults, callables, feature);
                    self.applyFillAndStroke_(style, resolved);
                }
                return [style];
            };
            this.wrappedLayer_.setStyle(customFeatureStyleFn);
        },
        applyFillAndStroke_: function(style, svgRules) {
            var fillColor = Mapbender.StyleUtil.parseSvgColor(svgRules, 'fillColor', 'fillOpacity');
            var strokeColor = Mapbender.StyleUtil.parseSvgColor(svgRules, 'strokeColor', 'strokeOpacity');
            style.setFill(new ol.style.Fill({
                color: fillColor
            }));
            style.setStroke(new ol.style.Stroke({
                color: strokeColor,
                width: svgRules.strokeWidth
            }));
            style.setImage(new ol.style.Circle({
                radius: svgRules.pointRadius,
                fill: style.getFill(),
                stroke: style.getStroke()
            }));
        },
        getMarkerFeature_: function(lon, lat, nativeStyle) {
            var feature = new ol.Feature({
                geometry: new ol.geom.Point([lon, lat])
            });
            if (nativeStyle) {
                feature.setStyle(nativeStyle);
            }
            return feature;
        },
        /**
         * @param {HTMLImageElement} img
         * @param {Number} offsetX
         * @param {Number} offsetY
         */
        imageToMarkerStyle_: function(img, offsetX, offsetY) {
            return new ol.style.Style({
                image: new ol.style.Icon({
                    src: img.src,
                    imgSize: [img.naturalWidth, img.naturalHeight],
                    anchor: [-offsetX, -offsetY],
                    anchorOrigin: ol.style.IconOrigin.TOP_LEFT,
                    anchorXUnits: ol.style.IconAnchorUnits.PIXELS,
                    anchorYUnits: ol.style.IconAnchorUnits.PIXELS
                })
            });
        },
        createDraw_: function(type) {
            var source = this.wrappedLayer_.getSource();
            switch (type) {
                case 'point':
                    return new ol.interaction.Draw({
                        type: 'Point',
                        stopClick: true,
                        source: source
                    });
                case 'line':
                    return new ol.interaction.Draw({
                        type: 'LineString',
                        stopClick: true,
                        source: source
                    });
                case 'polygon':
                    return new ol.interaction.Draw({
                        type: 'Polygon',
                        stopClick: true,
                        source: source
                    });
                case 'circle':
                    return new ol.interaction.Draw({
                        type: 'Circle',
                        stopClick: true,
                        source: source
                    });
                case 'rectangle':
                    return new ol.interaction.Draw({
                        type: 'Circle',
                        geometryFunction: ol.interaction.Draw.createBox(),
                        stopClick: true,
                        source: source
                    });
                default:
                    throw new Error("No such type " + type);
            }
        },
        activateDraw_: function(interaction, featureCallback) {
            var currentListeners = interaction.getListeners(ol.interaction.DrawEventType.DRAWEND) || [];
            for (var i = 0; i < currentListeners.length; ++i) {
                interaction.removeEventListener(ol.interaction.DrawEventType.DRAWEND, currentListeners[i]);
            }
            interaction.addEventListener(ol.interaction.DrawEventType.DRAWEND, function(e) {
                featureCallback(e.feature);
            });
            this.olMap.addInteraction(interaction);
        },
        endDraw_: function(interaction) {
            this.olMap.removeInteraction(interaction);
        },
        _prepareTextStyleCallbacks: function(svgStyles) {
            var self = this;
            var callbacks = {};
            switch (typeof (svgStyles['label'])) {
                case 'undefined':
                    break;
                case 'function':
                    callbacks.setText = svgStyles['label'];
                    break;
                default:
                    var labelLiteral = svgStyles['label'].toString();
                    callbacks.setText = function() {
                        return labelLiteral;
                    };
                    break;
            }
            if (svgStyles.labelAlign && typeof svgStyles.labelAlign === 'function') {
                callbacks.setTextAlign = function(feature, resolution) {
                    var alignBaseline = self._translateTextAlignment(svgStyles.labelAlign(feature, resolution));
                    return alignBaseline.align;
                };
                callbacks.setTextBaseline = function(feature, resolution) {
                    var alignBaseline = self._translateTextAlignment(svgStyles.labelAlign(feature, resolution));
                    return alignBaseline.baseline;
                };
            }
            if (svgStyles.labelXOffset && typeof svgStyles.labelXOffset === 'function') {
                callbacks.setOffsetX = svgStyles.labelXOffset;
            }
            if (svgStyles.labelYOffset && typeof svgStyles.labelYOffset === 'function') {
                callbacks.setOffsetY = svgStyles.labelYOffset;
            }
            return callbacks;
        },
        _translateTextAlignment: function(svgLabelAlign) {
            var baseline, align;
            switch (svgLabelAlign) {
                default:
                case 'cm':
                case 'cb':
                case 'ct':
                    align = 'center';
                    break;
                case 'lm':
                case 'lb':
                case 'lt':
                    align = 'left';
                    break;
                case 'rm':
                case 'rb':
                case 'rt':
                    align = 'right';
                    break;
            }
            switch (svgLabelAlign) {
                default:
                case 'cm':
                case 'lm':
                case 'rm':
                    baseline = 'middle';
                    break;
                case 'ct':
                case 'lt':
                case 'rt':
                    baseline = 'top';
                    break;
                case 'cb':
                case 'lb':
                case 'rb':
                    baseline = 'bottom';
                    break;
            }
            return {
                align: align,
                baseline: baseline
            };
        },
        _prepareTextStyle: function(svgStyles) {
            var alignBaseline = this._translateTextAlignment(svgStyles.labelAlign);
            return new ol.style.Text({
                fill: new ol.style.Fill({color: Mapbender.StyleUtil.svgToCssColorRule(svgStyles, 'fontColor', 'fontOpacity')}),
                stroke: new ol.style.Stroke({
                    color: Mapbender.StyleUtil.svgToCssColorRule(svgStyles, 'labelOutlineColor', 'labelOutlineOpacity'),
                    width: svgStyles.labelOutlineWidth
                }),
                textAlign: alignBaseline.align,
                textBaseline: alignBaseline.baseline,
                offsetX: svgStyles.labelXOffset,
                offsetY: svgStyles.labelYOffset
            });
        },
        detectCallables_: function(o) {
            var callables = {};
            Object.keys(o).forEach(function(propname) {
                if (typeof o[propname] === 'function') {
                    callables[propname] = o[propname];
                }
            });
            return callables;
        },
        resolveCallables_: function(baseRules, callables, feature) {
            if (!Object.keys(callables).length) {
                return baseRules;
            } else {
                var resolved = Object.assign({}, baseRules);
                Object.keys(callables).forEach(function(propName) {
                    resolved[propName] = callables[propName](feature);
                });
                return resolved;
            }
        },
        dummy_: null
    });
    return VectorLayerBridgeOl4;
}());

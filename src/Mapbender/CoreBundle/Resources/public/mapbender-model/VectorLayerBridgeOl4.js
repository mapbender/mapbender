window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerBridgeOl4 = (function() {
    function VectorLayerBridgeOl4(olMap) {
        window.Mapbender.VectorLayerBridge.call(this, olMap);
        this.wrappedLayer_ = new ol.layer.Vector({
            map: olMap,
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
        /**
         * @param {Array<ol.Feature>} features
         */
        addNativeFeatures: function(features) {
            this.wrappedLayer_.getSource().addFeatures(features);
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
            var baseFill = new ol.style.Fill({color: Mapbender.StyleUtil.parseSvgColor(svgWithDefaults, 'fillColor', 'fillOpacity')});
            var baseStroke = new ol.style.Stroke({color: Mapbender.StyleUtil.parseSvgColor(svgWithDefaults, 'strokeColor', 'strokeOpacity')});
            defaultStyle.setFill(baseFill);
            defaultStyle.setStroke(baseStroke);

            var labelCallback = this._prepareLabelCallback(styles);
            var textStyle = labelCallback && this._prepareTextStyle(svgWithDefaults);
            // we know that the default style function does not populate a text style, so we do not need to merge anything
            if (textStyle) {
                defaultStyle.setText(textStyle);
            }
            var pointImageStyle = new ol.style.Circle({
                radius: svgWithDefaults.pointRadius,
                fill: baseFill,
                stroke: baseStroke
            });
            var customFeatureStyleFn = function(feature, resolution) {
                var style = defaultStyle.clone();
                if (labelCallback) {
                    var textStyle = defaultStyle.getText().clone();
                    textStyle.setText((labelCallback)(feature, resolution));
                    style.setText(textStyle);
                }
                style.setImage(pointImageStyle);
                return style;
            };
            var customLayerStyleFn = function(feature, resolution) {
                return [customFeatureStyleFn(feature, resolution)];
            };
            this.wrappedLayer_.setStyle(customLayerStyleFn);
        },
        getMarkerFeature_: function(lon, lat) {
            var feature = new ol.Feature({
                geometry: new ol.geom.Point([lon, lat])
            });
            if (this.markerStyle_) {
                feature.setStyle(this.markerStyle_);
            }
            return feature;
        },
        createDraw_: function(type) {
            var source = this.wrappedLayer_.getSource();
            switch (type) {
                case 'point':
                    return new ol.interaction.Draw({
                        type: 'Point',
                        source: source
                    });
                case 'line':
                    return new ol.interaction.Draw({
                        type: 'LineString',
                        source: source
                    });
                case 'polygon':
                    return new ol.interaction.Draw({
                        type: 'Polygon',
                        source: source
                    });
                case 'circle':
                    return new ol.interaction.Draw({
                        type: 'Circle',
                        source: source
                    });
                case 'rectangle':
                    return new ol.interaction.Draw({
                        type: 'Circle',
                        geometryFunction: ol.interaction.Draw.createBox(),
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
        _prepareLabelCallback: function(svgStyles) {
            var labelCallback, labelLiteral;
            switch (typeof (svgStyles['label'])) {
                case 'undefined':
                    break;
                case 'function':
                    labelCallback = svgStyles['label'];
                    break;
                default:
                    labelLiteral = svgStyles['label'].toString();
                    labelCallback = function() {
                        return labelLiteral;
                    };
                    break;
            }
            return labelCallback;
        },
        _prepareTextStyle: function(svgStyles) {
            var baseline, align;
            switch (svgStyles.labelAlign) {
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
            switch (svgStyles.labelAlign) {
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
            return new ol.style.Text({
                fill: new ol.style.Fill({color: Mapbender.StyleUtil.parseSvgColor(svgStyles, 'fontColor', 'fontOpacity')}),
                stroke: new ol.style.Stroke({
                    color: Mapbender.StyleUtil.parseSvgColor(svgStyles, 'labelOutlineColor', 'labelOutlineOpacity'),
                    width: svgStyles.labelOutlineWidth
                }),
                textAlign: align,
                textBaseline: baseline,
                offsetX: svgStyles.labelXOffset,
                offsetY: svgStyles.labelYOffset
            });
        },
        dummy_: null
    });
    return VectorLayerBridgeOl4;
}());

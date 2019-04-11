window.Mapbender = Mapbender || {};
window.Mapbender.MapEngineOl4 = (function() {
    function MapEngineOl4() {
        Mapbender.MapEngine.apply(this, arguments);
    }
    MapEngineOl4.prototype = Object.create(Mapbender.MapEngine.prototype);
    Object.assign(MapEngineOl4.prototype, {
        constructor: MapEngineOl4,
        patchGlobals: function(mapOptions) {
            var _tileSize = mapOptions && mapOptions.tileSize && parseInt(mapOptions.tileSize);
            var _dpi = mapOptions && mapOptions.dpi && parseInt(mapOptions.dpi);
            if (_tileSize) {
                // todo: apply tile size globally?
            }
            if (_dpi) {
                // todo: apply dpi globally?
            }
            // todo: image path?
             // something something Mapbender.configuration.application.urls.asset
            // todo: proxy host?
              // something something Mapbender.configuration.application.urls.proxy + '?url=';
            // Allow drag pan motion to continue outside of map div. Great for multi-monitor setups.
            // todo: fix drag pan
            // OpenLayers.Control.Navigation.prototype.documentDrag = true;
            Mapbender.MapEngine.prototype.patchGlobals.apply(this, arguments);
        },
        getLayerVisibility: function(olLayer) {
            return olLayer.getVisible();
        },
        createWmsLayer: function(source) {
            var sourceOpts = {
                url: source.configuration.options.url,
                transition: 0,
                params: {}
            };

            var activatedLeaves = source.getActivatedLeaves();
            var nonEmptyLayerNames = activatedLeaves.map(function(sourceLayer) {
                return sourceLayer.options.name;
            }).filter(function(layerName) {
                return !!layerName;
            });
            sourceOpts.params.LAYERS = nonEmptyLayerNames;
            // @todo: use configured styles
            var styles = nonEmptyLayerNames.map(function() {
                return '';
            });
            sourceOpts.params.STYLES = styles;

            var olSourceClass;
            var olLayerClass;
            if (source.configuration.options.tiled) {
                olSourceClass = ol.source.TileWMS;
                olLayerClass = ol.layer.Tile;
            } else {
                olSourceClass = ol.source.ImageWMS;
                olLayerClass = ol.layer.Image;
            }

            var layerOptions = {
                source: new (olSourceClass)(sourceOpts)
            };
            // todo: minScale / maxScale
            // todo: opacity
            // todo: transparent
            // todo: version
            // todo: format
            // todo: exception format
            return new (olLayerClass)(layerOptions);
        },
        /**
         * @param olLayer
         * @param layers
         * @param styles
         * @return {boolean}
         */
        compareWmsParams: function (olLayer, layers, styles) {
            var paramsNow = olLayer.getSource().getParams();
            var layersNow = paramsNow.LAYERS;
            var stylesNow = paramsNow.STYLES;
            var newLayers = (layersNow || '').toString() !== layers.toString();
            var newStyles = (stylesNow || '').toString() !== styles.toString();
            return newLayers || newStyles;
        },
        isProjectionAxisFlipped: function(srsName) {
            var projection = ol.proj.get(srsName);
            var axisOrientation = projection && projection.getAxisOrientation();
            return !!(axisOrientation && axisOrientation.substr(0, 2) === 'ne');
        },
        boundsFromArray: function(values) {
            var bounds = values.slice();
            Object.defineProperty(bounds, 'left', {
                get: function() { return this[0]; }
            });
            Object.defineProperty(bounds, 'bottom', {
                get: function() { return this[1]; }
            });
            Object.defineProperty(bounds, 'right', {
                get: function() { return this[2]; }
            });
            Object.defineProperty(bounds, 'top', {
                get: function() { return this[3]; }
            });
            return bounds;
        },
        transformBounds: function(bounds, fromProj, toProj) {
            var from = this._getProj(fromProj, true);
            var to = this._getProj(toProj, true);
            var transformFn = ol.proj.getTransformFromProjections(from, to);
            var transformed = ol.extent.applyTransform(bounds, transformFn);
            return this.boundsFromArray(transformed);
        },
        _getProj: function(projOrSrsName, strict) {
            // ol.proj.get will happily accept an ol.proj instance :)
            var proj = ol.proj.get(projOrSrsName);
            if (!proj && strict) {
                throw new Error("Unsupported projection " + projOrSrsName.toString());
            }
            if (proj && !proj.units_) {
                proj.units_ = 'degrees';
            }
            return proj || null;
        }
    });
    window.Mapbender.MapEngine.typeMap['ol4'] = MapEngineOl4;
    return MapEngineOl4;
}());

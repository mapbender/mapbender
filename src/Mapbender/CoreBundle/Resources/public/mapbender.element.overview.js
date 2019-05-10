(function($){

    $.widget("mapbender.mbOverview", {
        options: {
            layerset: 0,
            target: null,
            width: 200,
            height: 100,
            anchor: 'right-top',
            maximized: true,
            fixed: false
        },
        overview: null,
        mbMap: null,

        /**
         * Creates the overview
         */
        _create: function(){
            if(!Mapbender.checkTarget("mbOverview", this.options.target)){
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },

        /**
         * Initializes the overview
         */
        _setup: function() {
            this.mbMap = $('#' + this.options.target).data('mapbenderMbMap');

            this.element.addClass(this.options.anchor);
            if (!this.options.maximized) {
                this.element.addClass("closed");
            } else {
                this._initOverview();
            }
            $('.toggleOverview', this.element).on('click', $.proxy(this._openClose, this));
            this._trigger('ready');
        },
        _initOverview: function() {
            this.overview = this._createOverviewControl();
            if (this.overview) {
                this.mbMap.map.olMap.addControl(this.overview);
                $(document).on('mbmapsrschanged', $.proxy(this._onMbMapSrsChanged, this));
            }
        },
        _createOverviewControl: function() {
            var layers = this._createLayers();
            if (!layers.length){
                Mapbender.info(Mapbender.trans("mb.core.overview.nolayer"));
                return false;
            }
            var projection = this.mbMap.getModel().getCurrentProjectionCode();
            var maxExtent = this.mbMap.map.olMap.maxExtent;

            var options = {
                layers: layers,
                div: $('.overviewContainer', this.element).get(0),
                size: new OpenLayers.Size(this.options.width, this.options.height),
                mapOptions: {
                    maxExtent: (maxExtent && maxExtent.clone()) || null,
                    projection: projection,
                    theme: null
                }
            };
            if (this.options.fixed){
                $.extend(options, {
                    minRatio: 1,
                    maxRatio: 1000000000
                    // ,autoPan: false
                });
            }
            return new OpenLayers.Control.OverviewMap(options);
        },
        _getSourceInstanceDefinitions: function() {
            var instanceDefs = [];
            var layerSet = (Mapbender.configuration.layersets[this.options.layerset] || []).slice().reverse();
            for (var lsix = 0; lsix < layerSet.length; ++lsix) {
                var instanceMap = layerSet[lsix];
                var instanceIds = Object.keys(instanceMap);
                for (var idIndex = 0; idIndex < instanceIds.length; ++ idIndex) {
                    var instanceId = instanceIds[idIndex];
                    instanceDefs.push(instanceMap[instanceId]);
                }
            }
            return instanceDefs;
        },
        _createLayers: function() {
            var layers = [];
            var srsName = this.mbMap.getModel().getCurrentProjectionCode();
            var instanceDefs = this._getSourceInstanceDefinitions();
            for (var i = 0; i < instanceDefs.length; ++i) {
                var source = instanceDefs[i];
                // Legacy HACK: Overview ignores backend settings on instance layers, enables all children
                //        of the root layer with non-empty names, ignores every other layer
                var activatedLayers = source.getActivatedLeaves();
                var nonEmptyLayerNames = activatedLayers.map(function(sourceLayer) {
                    return sourceLayer.options.name;
                }).filter(function(layerName) {
                    return !!layerName;
                });
                if (nonEmptyLayerNames.length) {
                    layers = layers.concat(source.createNativeLayers(srsName).map(function(nativeLayer) {
                        nativeLayer.mergeNewParams({
                            LAYERS: nonEmptyLayerNames
                        });
                        return nativeLayer;
                    }));
                }
            }
            if (layers.length) {
                layers[0].setIsBaseLayer(true);
            }
            return layers;
        },
        /**
         * Opens/closes the overview element
         */
        _openClose: function(event){
            var self = this;
            $(this.element).toggleClass('closed');
            window.setTimeout(function(){
                if (!$(self.element).hasClass('closed')) {
                    if (self.overview === null) {
                        self._initOverview();
                    } else if (self.overview && self.overview.ovmap) {
                        self.overview.ovmap.updateSize();
                    }
                }
            }, 300);
        },
        /**
         * Cahnges the overview srs
         */
        _onMbMapSrsChanged: function(event, data) {
            if (data.mbMap !== this.mbMap) {
                return;
            }
            var oldProj = this.overview.ovmap.getProjectionObject();
            if (oldProj.projCode === data.to.projCode) {
                return;
            }
            var newCenter = this.overview.ovmap.getCenter().clone().transform(oldProj, data.to);
            // NOTE: this extent is already transformed
            var newMaxExtent = this.mbMap.model.map.olMap.maxExtent || null;
            if (newMaxExtent) {
                newMaxExtent = newMaxExtent.clone();
            }
            try {
                this._changeSrs(data.to.projCode, newCenter, newMaxExtent);
            } catch (e) {
                console.error("Overview srs change failed", e);
            }
        },
        _changeSrs: function(srsCode, newCenter, newMaxExtent) {
            /**
             * @type {null|OpenLayers.Map}
             */
            var ovMap = this.overview.ovmap;

            var baseLayer = ovMap.layers[0];
            var layerUpdateOrder = ovMap.layers.filter(function(l) {
                return ovMap.baseLayer !== l;
            }).concat(ovMap.layers.filter(function(l) {
                if (ovMap.baseLayer === l) {
                    baseLayer = l;
                    return true;
                } else {
                    return false;
                }
            }));
            var layerOptions = {
                projection: srsCode
            };
            if (newMaxExtent) {
                layerOptions.maxExtent = newMaxExtent;
            }
            try {
                for (var lix = 0; lix < layerUpdateOrder.length; ++lix) {
                    var layer = layerUpdateOrder[lix];
                    layer.addOptions(layerOptions);
                }
                ovMap.displayProjection = baseLayer.projection;
                ovMap.projection = baseLayer.projection;
                ovMap.maxExtent = baseLayer.maxExtent;
                ovMap.units = baseLayer.units;
                ovMap.setCenter(newCenter, null, false, true);
                this.overview.update();
            } catch (e) {
                console.error("Overview srs change failed", e);
            }
        }
    });

})(jQuery);

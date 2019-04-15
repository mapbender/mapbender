(function($){

    $.widget("mapbender.mbOverview", {
        options: {
            layerset: []
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
            } else if (!this.element.hasClass('closed')) {
                // if we start closed, wait with initialization until opened
                this._initDisplay();
            }
            $('.toggleOverview', this.element).on('click', $.proxy(this._openClose, this));

            this._trigger('ready');
        },
        _initDisplay: function() {
            var layers = this._createLayers();
            if (!layers.length){
                Mapbender.info(Mapbender.trans("mb.core.overview.nolayer"));
                return null;
            }

            switch (Mapbender.mapEngine.code) {
                case 'ol4':
                    this._initAsOl4Control(layers);
                    break;
                case 'ol2':
                    this._initAsOl2Control(layers);
                    $(document).bind('mbmapsrschanged', this._changeSrs2.bind(this));
                    break;
                default:
                    throw new Error("Unhandled engine code " + Mapbender.mapEngine.code);
            }
        },
        _initAsOl4Control: function(layers) {
            var viewportId = 'mb-overview-' + this.element.attr('id') + '-viewport';
            var $viewport = $('.overviewContainer', this.element);
            $('.toggleOverview', this.element).on('click', function() {
                $('button', $viewport).click();
            });
            $viewport.attr('id', viewportId);
            // @see https://github.com/openlayers/openlayers/blob/v4.6.5/src/ol/control/overviewmap.js

            var mainMapModel = this.mbMap.model;
            var center = mainMapModel.olMap.getView().getCenter();

            var viewOptions = {
                projection: mainMapModel.getCurrentProjectionCode(),
                center: center
            };
            if (this.options.fixed) {
                var maxExtent = mainMapModel.getMaxExtent();
                var projectedWidth = Math.abs(maxExtent.right - maxExtent.left);
                var projectedHeight = Math.abs(maxExtent.top - maxExtent.bottom);
                var resolutionH = projectedWidth / this.options.width;
                var resolutionV = projectedHeight / this.options.height;
                var resolution = Math.max(resolutionH, resolutionV);
                viewOptions.resolutions = [resolution];
            }
            var controlOptions = {
                collapsible: true,
                collapsed: false,
                target: viewportId,
                layers: layers,
                view: new ol.View(viewOptions)
            };
            this.overview = new ol.control.OverviewMap(controlOptions);

            mainMapModel.olMap.addControl(this.overview);
            $('.ol-overviewmap-map', $viewport)
                .width(this.options.width)
                .height(this.options.height)
            ;
            this.overview.ovmap_.updateSize();
        },
        _initAsOl2Control: function(layers) {
            this.overview = this._createOverviewControl(layers);
            if (this.overview) {
                this.mbMap.map.olMap.addControl(this.overview);
            }
        },
        _createOverviewControl: function(layers) {
            var projection = this.mbMap.getModel().getCurrentProjectionCode();
            var maxExtent = this.mbMap.map.olMap.maxExtent;
            if (layers.length) {
                layers[0].setIsBaseLayer(true);
            }

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
            var layerSet = Mapbender.configuration.layersets[this.options.layerset] || [];
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
                if (source.hasVisibleLayers(srsName)) {
                    layers = layers.concat(source.createNativeLayers(srsName));
                }
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
                    if (!self.overview) {
                        self._initDisplay();
                    } else {
                        if (self.overview && self.overview.ovmap) {
                            self.overview.ovmap.updateSize();
                        }
                    }
                }
            }, 300);
        },

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
        _changeSrs2: function(srsCode, newCenter, newMaxExtent) {
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
        ,
        _changeSrs4: function(event, srs) {
            console.log("Overview changesrs event", event, srs);
            var properties = this.overview.ovmap_.getProperties();
            properties.view = new ol.View({
              projection: this.mbMap.model.getCurrentProjectionCode(),
              center: this.mbMap.model.olMap.getView().getCenter(),
              extent: this.mbMap.model.getMaxExtent(),
              resolution: this.mbMap.model.olMap.getView().getResolution()
            });
            this.overview.ovmap_.setProperties(properties);
            return;


            // @todo 3.1.0: this won't work on OL4, starting here
            var ovMap = overview.ovmap;
            var oldProj = ovMap.getProjectionObject();
            if (oldProj.projCode === srs.projection.projCode) {
                return;
            }
            var center = ovMap.getCenter().clone().transform(oldProj, srs.projection);

            var mainMapMaxExtent = this.mbMap.model.map.olMap.maxExtent;


            ovMap.projection = srs.projection;
            ovMap.displayProjection = srs.projection;
            ovMap.units = srs.projection.proj.units;
            if (mainMapMaxExtent) {
                // NOTE: this extent is already transformed
                ovMap.maxExtent = mainMapMaxExtent.clone();
            }
        }
    });

})(jQuery);

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
            this._updateToggleIcon(this.options.maximized);
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
                this._initOverview();
            }
            $('.toggleOverview', this.element).on('click', $.proxy(this._openClose, this));
            this._trigger('ready');
        },
        _initOverview: function() {
            var layers = this._createLayers();
            if (!layers.length){
                Mapbender.info(Mapbender.trans("mb.core.overview.nolayer"));
                return false;
            }

            switch (Mapbender.mapEngine.code) {
                default:
                    this._initAsOl4Control(layers);
                    break;
                case 'ol2':
                    this._initAsOl2Control(layers);
                    break;
            }
            $(this.mbMap.element).bind('mbmapsrschanged', this._onMbMapSrsChanged.bind(this));
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
        _createLayers: function() {
            var layers = [];
            var srsName = this.mbMap.getModel().getCurrentProjectionCode();
            var lsId = this.options.layerset;
            var layerset = Mapbender.layersets.filter(function(x) {
                return ('' + lsId) === ('' + x.id);
            })[0];
            var instanceDefs = layerset && layerset.children.slice().reverse() || [];

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
            this.element.toggleClass('closed')
            var newState = !this.element.hasClass('closed');
            this._updateToggleIcon(newState);
            if (newState) {
                window.setTimeout(function() {
                    if (!self.overview) {
                        self._initOverview();
                    } else {
                        if (self.overview && self.overview.ovmap) {
                            self.overview.ovmap.updateSize();
                        }
                    }
                }, 300);
            }
        },
        _updateToggleIcon: function(newState) {
            var $icon = $('.toggleOverview i.fa', this.element);
            $icon.toggleClass('fa-plus', !newState);
            $icon.toggleClass('fa-minus', newState);
        },
        _onMbMapSrsChanged: function(event, data) {
            try {
                switch (Mapbender.mapEngine.code) {
                    default:
                        this._changeSrs4(event, data);
                        break;
                    case 'ol2':
                        this._changeSrs2(event, data);
                        break;
                }
            } catch (e) {
                console.error("Overview srs change failed", e);
            }
        },
        _changeSrs2: function(event, data) {
            /**
             * @type {null|OpenLayers.Map}
             */
            var ovMap = this.overview.ovmap;
            var oldProj = ovMap.getProjectionObject();
            var newCenter = ovMap.getCenter().clone().transform(oldProj, data.to);
            // NOTE: this extent is already transformed
            var newMaxExtent = this.mbMap.model.map.olMap.maxExtent || null;
            if (newMaxExtent) {
                newMaxExtent = newMaxExtent.clone();
            }

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
                projection: data.to
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
        _changeSrs4: function(event, data) {
            var properties = this.overview.ovmap_.getProperties();
            properties.view = new ol.View({
              projection: data.to,
              center: this.mbMap.model.olMap.getView().getCenter(),
              extent: this.mbMap.model.getMaxExtent(),
              resolution: this.mbMap.model.olMap.getView().getResolution()
            });
            this.overview.ovmap_.setProperties(properties);
        }
    });

})(jQuery);

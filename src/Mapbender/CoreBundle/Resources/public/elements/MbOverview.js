(function () {
    class MbOverview extends MapbenderElement {

        constructor(configuration, $element) {
            super(configuration, $element);

            this._updateToggleIcon();
            var lsId = this.options.layerset;
            var layerset = Mapbender.layersets.filter(function(x) {
                return ('' + lsId) === ('' + x.id);
            })[0];
            this.sources_ = layerset && layerset.children.slice().reverse() || [];

            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget('mbOverview');
            });
        }

        /**
         * Creates the overview
         */
        _create(){
            this._updateToggleIcon();
            var lsId = this.options.layerset;
            var layerset = Mapbender.layersets.filter(function(x) {
                return ('' + lsId) === ('' + x.id);
            })[0];
            this.sources_ = layerset && layerset.children.slice().reverse() || [];

            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget('mbOverview');
            });
        }

        /**
         * Initializes the overview
         */
        _setup() {
            if (!this.$element.hasClass('closed')) {
                // if we start closed, wait with initialization until opened
                this._initOverview();
            }
            $('.toggleOverview', this.$element).on('click', $.proxy(this._openClose, this));
            Mapbender.elementRegistry.markReady(this.$element.attr('id'));
        }

        _initOverview() {
            var layers = this._createLayers();
            if (!layers.length){
                Mapbender.info(Mapbender.trans("mb.core.overview.nolayer"));
                return false;
            }

            this._initAsControl(layers);
            $(this.mbMap.element).bind('mbmapsrschanged', this._onMbMapSrsChanged.bind(this));
        }

        _initAsControl(layers) {
            var viewportId = 'mb-overview-' + this.$element.attr('id') + '-viewport';
            var $viewport = $('.overviewContainer', this.$element);
            $('.toggleOverview', this.$element).on('click', function() {
                $('button', $viewport).click();
            });
            $viewport.attr('id', viewportId);
            // @see https://github.com/openlayers/openlayers/blob/main/src/ol/control/OverviewMap.js

            var mainMapModel = this.mbMap.model;
            var center = mainMapModel.olMap.getView().getCenter();

            var viewOptions = {
                projection: mainMapModel.getCurrentProjectionCode(),
                center: center
            };
            var maxExtent = mainMapModel.getMaxExtent();
            if (maxExtent) {
                var projectedWidth = Math.abs(maxExtent.right - maxExtent.left);
                var projectedHeight = Math.abs(maxExtent.top - maxExtent.bottom);
                var resolutionH = projectedWidth / this.options.width;
                var resolutionV = projectedHeight / this.options.height;
                viewOptions.maxResolution = Math.max(resolutionH, resolutionV);
                if (this.options.fixed) {
                    viewOptions.resolutions = [viewOptions.maxResolution];
                }
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
            ;

            var self = this;
            this.overview.getOverviewMap().updateSize();
            window.setTimeout(function() {
                self.overview.render();
            });
        }

        _createLayers() {
            var layers = [];
            var srsName = this.mbMap.getModel().getCurrentProjectionCode();

            for (var i = 0; i < this.sources_.length; ++i) {
                var source = this.sources_[i];
                // Legacy HACK: Overview ignores backend settings on instance layers, enables all children
                //        of the root layer with non-empty names, ignores every other layer
                if (source.type !== 'wms' || source.hasVisibleLayers(srsName)) {
                    source.createNativeLayers(srsName);
                    layers = layers.concat(source.getNativeLayers());
                }
            }
            return layers;
        }

        /**
         * Opens/closes the overview element
         */
        _openClose(event){
            var self = this;
            this.$element.toggleClass('closed')
            var newState = !this.$element.hasClass('closed');
            this._updateToggleIcon();
            if (newState) {
                window.setTimeout(function() {
                    if (!self.overview) {
                        self._initOverview();
                    } else {
                        if (self.overview && self.overview.getOverviewMap()) {
                            self.overview.getOverviewMap().updateSize();
                            self.overview.resetExtent_();
                        }
                    }
                }, 300);
            }
        }

        _updateToggleIcon() {
            var state = !this.$element.hasClass('closed');
            var $icon = $('.toggleOverview i.fa', this.$element);
            $icon.toggleClass('fa-plus', !state);
            $icon.toggleClass('fa-minus', state);
        }

        _onMbMapSrsChanged(event, data) {
            try {
                var ovMap = this.overview.getOverviewMap();
                var properties = ovMap.getProperties();
                properties.view = new ol.View({
                    projection: data.to,
                    center: this.mbMap.model.olMap.getView().getCenter(),
                    extent: this.mbMap.model.getMaxExtent(),
                    resolution: this.mbMap.model.olMap.getView().getResolution()
                });
                ovMap.setProperties(properties);
            } catch (e) {
                console.error("Overview srs change failed", e);
            }
        }

        getPrintData() {
            var printData = {
                changeAxis: false
            };
            var extentArray = this.overview.getOverviewMap().getView().calculateExtent();
            printData.center = {
                x: 0.5 * (extentArray[0] + extentArray[2]),
                y: 0.5 * (extentArray[1] + extentArray[3])
            };
            printData.height = Math.abs(extentArray[3] - extentArray[1]);
            var extent = {
                bottom: printData.center.y - 0.5 * printData.height,
                top: printData.center.y + 0.5 * printData.height,
                // NOTE: extent left / right values don't matter much in print backend, will be adjusted for target region aspect ratio anyway
                left: printData.center.x - 0.5 * printData.height,
                right: printData.center.x + 0.5 * printData.height
            };

            const nativeMapView = this.overview.getOverviewMap().getView();
            const mapModel = this.mbMap.getModel();

            const constrainedResolution = nativeMapView.getConstrainedResolution(nativeMapView.getResolution());
            const srsName = mapModel.getCurrentProjectionCode();
            const scale = mapModel.resolutionToScale(constrainedResolution, 72, srsName);

            printData.layers = this.sources_.flatMap((source) => source.getPrintConfigs(extent, scale, srsName));
            return printData.layers.length && printData || null;
        }
    }
    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbOverview = MbOverview;
})();

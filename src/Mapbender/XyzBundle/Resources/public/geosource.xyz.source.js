window.Mapbender = Mapbender || {};

(function () {
    Mapbender.XyzSource = class XyzSource extends Mapbender.Source {
        constructor(definition) {
            definition.children = [{...definition}];
            super(definition);
        }

        createNativeLayers(srsName, mapOptions) {
            var sourceOpts = {
                url: this.options.url,
                attributions: this.options.attribution || undefined,
                minZoom: this.options.minZoom || 0,
                maxZoom: this.options.maxZoom || 22,
            };

            this.nativeLayers = [new ol.layer.Tile({
                opacity: this.options.opacity,
                source: new ol.source.XYZ(sourceOpts),
            })];
            return this.nativeLayers;
        }

        getPrintConfigs(bounds, scale, srsName) {
            var boundsArray = [bounds.left, bounds.bottom, bounds.right, bounds.top];
            var bbox = Mapbender.mapEngine.transformBounds(boundsArray, srsName, "EPSG:3857");
            return [{
                ...this._getPrintBaseOptions(),
                url: this.options.url,
                bbox: bbox,
            }];
        }

        updateEngine() {
            Mapbender.mapEngine.setLayerVisibility(this.getNativeLayer(), this.getRootLayer().state.visibility);
        }

        setLayerOrder(newLayerIdOrder) {
            // no sublayers for XYZ tile sources
        }

        getSelected() {
            return this.getRootLayer().getSelected();
        }
    };

    Mapbender.Source.typeMap['xyz'] = Mapbender.XyzSource;
})();

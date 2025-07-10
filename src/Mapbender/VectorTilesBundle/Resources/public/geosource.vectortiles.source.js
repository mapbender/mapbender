class VectorTilesSource extends Mapbender.Source {
    constructor(definition) {
        definition.children = [{...definition}];
        super(definition);
    }

    createNativeLayers(srsName, mapOptions) {
        const mapboxVector = new ol.layer.MapboxVector({
            styleUrl: this.options.jsonUrl,
        });
        mapboxVector.setOpacity(this.options.opacity);
        this.nativeLayers = [mapboxVector];
        return this.nativeLayers;
    }

    getSelected() {
        return this.getRootLayer().getSelected();
    }

    updateEngine() {
        const isPseudoMercator = Mapbender.Model?.getCurrentProjectionCode() === 'EPSG:3857';
        Mapbender.mapEngine.setLayerVisibility(this.getNativeLayer(), isPseudoMercator && this.getSelected());
    }

    setLayerOrder(newLayerIdOrder) {
        // do nothing, there are no sublayers for vector tile sources
    }
}

window.Mapbender = Mapbender || {};
window.Mapbender.VectorTilesSource = VectorTilesSource;
Mapbender.Source.typeMap['vector_tiles'] = VectorTilesSource;

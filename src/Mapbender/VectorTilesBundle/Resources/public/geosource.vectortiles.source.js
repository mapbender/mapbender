class VectorTilesSource extends Mapbender.Source {
    constructor(definition) {
        definition.children = [{...definition}];
        super(definition);
    }

    createNativeLayers(srsName, mapOptions) {
        this.nativeLayers = [new ol.layer.MapboxVector({
            styleUrl: this.options.jsonUrl,
        })];
        return this.nativeLayers;
    }

    getSelected() {
        return this.getRootLayer().getSelected();
    }

    updateEngine() {
        Mapbender.mapEngine.setLayerVisibility(this.getNativeLayer(), this.getSelected());
    }

    setLayerOrder(newLayerIdOrder) {
        // do nothing, there are no sublayers for vector tile sources
    }
}

window.Mapbender = Mapbender || {};
window.Mapbender.VectorTilesSource = VectorTilesSource;
Mapbender.Source.typeMap['vector_tiles'] = VectorTilesSource;

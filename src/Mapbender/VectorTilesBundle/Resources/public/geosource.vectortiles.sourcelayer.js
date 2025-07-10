class VectorTilesSourceLayer extends Mapbender.SourceLayer {

    constructor(definition, source, parent) {
        super(definition, source, parent);
    }

    hasBounds() {
        return false;
    }

    supportsProjection(srsName) {
        return srsName === 'EPSG:3857';
    }
}


window.Mapbender = Mapbender || {};
window.Mapbender.VectorTilesSourceLayer = VectorTilesSourceLayer;
Mapbender.SourceLayer.typeMap['vector_tiles'] = VectorTilesSourceLayer;

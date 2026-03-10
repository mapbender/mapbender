class OgcApiSourceLayer extends Mapbender.SourceLayer {

    constructor(definition, source, parent) {
        super(definition, source, parent);
    }

    hasBounds() {
        return !!this.options.bbox;
    }

    getBounds(projCode, inheritFromParent) {
        // bbox is always in WGS 84
        const bounds = this.source._bboxArrayToBounds(this.options.bbox, 'EPSG:4326');
        return Mapbender.mapEngine.transformBounds(bounds, 'EPSG:4326', projCode);
    }

    intersectsExtent(extent, srsName) {
        if (!this.hasBounds()) {
            return true;
        }
        const extent_ = srsName !== 'EPSG:4326'
            ? Mapbender.mapEngine.transformBounds(extent, srsName, 'EPSG:4326')
            : extent;
        const layerBounds = this.source._bboxArrayToBounds(this.options.bbox, 'EPSG:4326');
        return Mapbender.Util.extentsIntersect(extent_, layerBounds);
    }
}

window.Mapbender = Mapbender || {};
window.Mapbender.OgcApiSourceLayer = OgcApiSourceLayer;
Mapbender.SourceLayer.typeMap['ogc_api_features'] = OgcApiSourceLayer;

/** OGC API Features Part 1 mandates bounding boxes are always in WGS 84 */
const OGC_API_BBOX_CRS = 'EPSG:4326';

class OgcApiSourceLayer extends Mapbender.SourceLayer {

    constructor(definition, source, parent) {
        super(definition, source, parent);
    }

    hasBounds() {
        return !!this.options.bbox;
    }

    getBounds(projCode, inheritFromParent) {
        const bounds = this.source._bboxArrayToBounds(this.options.bbox, OGC_API_BBOX_CRS);
        return Mapbender.mapEngine.transformBounds(bounds, OGC_API_BBOX_CRS, projCode);
    }

    intersectsExtent(extent, srsName) {
        if (!this.hasBounds()) {
            return true;
        }
        const extent_ = srsName !== OGC_API_BBOX_CRS
            ? Mapbender.mapEngine.transformBounds(extent, srsName, OGC_API_BBOX_CRS)
            : extent;
        const layerBounds = this.source._bboxArrayToBounds(this.options.bbox, OGC_API_BBOX_CRS);
        return Mapbender.Util.extentsIntersect(extent_, layerBounds);
    }
}

window.Mapbender = Mapbender || {};
window.Mapbender.OgcApiSourceLayer = OgcApiSourceLayer;
Mapbender.SourceLayer.typeMap['ogc_api_features'] = OgcApiSourceLayer;

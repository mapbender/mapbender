class VectorTilesSourceLayer extends Mapbender.SourceLayer {

    constructor(definition, source, parent) {
        super(definition, source, parent);
    }

    hasBounds() {
        return !!this.options.bbox;
    }

    getBounds(projCode, inheritFromParent) {
        // bbox is always in WGS 84
        const bounds = this.source._bboxArrayToBounds(this.options.bbox, "EPSG:4326");
        return Mapbender.mapEngine.transformBounds(bounds, "EPSG:4326", projCode);
    }

    intersectsExtent(extent, srsName) {
        if (!this.hasBounds()) {
            return true;
        }

        const extent_ = srsName !== 'EPSG:4326'
            ? Mapbender.mapEngine.transformBounds(extent, srsName, 'EPSG:4326')
            : extent;

        const layerBounds = this.source._bboxArrayToBounds(this.options.bbox, "EPSG:4326");
        return Mapbender.Util.extentsIntersect(extent_, layerBounds);
    }

    supportsProjection(srsName) {
        // Per Tile JSON spec, vector tiles are always in Web Mercator
        return srsName === 'EPSG:3857';
    }

    getLegend(forPrint) {
        return {
            topLevel: true,
            type: 'style',
            title: this.options.title,
            layers: this._getLegend(),
        }
    }

    async _getLegend() {
        await new Promise(resolve => setTimeout(resolve, 1000));
        return [
            {
                title: 'Keks',
                style: {
                    fillColor: '#ff0000',
                    fillOpacity: 0.5,
                    strokeColor: '#000000',
                    strokeWidth: 1,
                    label: 'Keks',
                }
            }
        ]
    }
}


window.Mapbender = Mapbender || {};
window.Mapbender.VectorTilesSourceLayer = VectorTilesSourceLayer;
Mapbender.SourceLayer.typeMap['vector_tiles'] = VectorTilesSourceLayer;

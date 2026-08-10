class OgcApiSourceLayer extends Mapbender.SourceLayer {

    constructor(definition, source, parent) {
        super(definition, source, parent);

        this.tooltipWrapper = {tooltip: this.options.tooltip?.template};
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

    hasTooltip() {
        return !!(this.options.tooltip?.template);
    }

    tooltipForFeature(feature) {
        if (!this.tooltipPlaceholderResolver) {
            this.tooltipPlaceholderResolver = Mapbender.StyleUtil.getPlaceholderResolver(
                this.tooltipWrapper,
                ['tooltip'], function (feature) {
                    return feature.getProperties() || {};
                }
            );
        }

        const tooltip = this.tooltipPlaceholderResolver(this.tooltipWrapper, feature).tooltip;
        if (!tooltip) return null;

        const fragment = document.createElement('div');
        fragment.innerHTML = tooltip;
        return fragment;
    }

}

window.Mapbender = Mapbender || {};
window.Mapbender.OgcApiSourceLayer = OgcApiSourceLayer;
Mapbender.SourceLayer.typeMap['ogc_api_features'] = OgcApiSourceLayer;

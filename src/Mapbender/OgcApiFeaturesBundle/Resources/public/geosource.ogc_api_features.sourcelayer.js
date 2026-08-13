class OgcApiSourceLayer extends Mapbender.SourceLayer {

    constructor(definition, source, parent) {
        super(definition, source, parent);

        this.tooltipWrapper = {tooltip: this.options.tooltip?.template};
        this.pointRadius = 5;
        // to avoid XSS, HTML in resolved tooltip data is escaoed by default. If you want to allow HTML, set this to false.
        this.escapeTooltipHtml = true;
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
                },
                this.escapeTooltipHtml
            );
        }

        const tooltip = this.tooltipPlaceholderResolver(this.tooltipWrapper, feature).tooltip;
        if (!tooltip) return null;

        const fragment = document.createElement('div');
        fragment.innerHTML = tooltip;
        return fragment;
    }

    hasHoverStyle() {
        return !!(this.options.hoverStyle && (this.options.hoverStyle.strokeColor || this.options.hoverStyle.fillColor));
    }

    getHoverStyle(geomType) {
        const isPoint = geomType === 'Point';
        const isCached = (isPoint && this._hoverStylePoint) || (!isPoint && this._hoverStyle);

        if (!isCached) {
            const styleSpec = this.options.hoverStyle;
            const stroke = styleSpec.strokeColor ? new ol.style.Stroke({
                color: styleSpec.strokeColor,
                width: isNaN(parseFloat(styleSpec.strokeWidth)) ? 1 : parseFloat(styleSpec.strokeWidth),
            }) : undefined;
            const fill = styleSpec.fillColor ? new ol.style.Fill({
                color: styleSpec.fillColor,
            }) : undefined;

            if (isPoint) {
                this._hoverStylePoint = new ol.style.Style({
                    image: new ol.style.Circle({
                        radius: this.pointRadius,
                        fill: fill,
                        stroke: stroke,
                    }),
                });
            } else {
                this._hoverStyle = new ol.style.Style({
                    stroke: stroke,
                    fill: fill,
                });
            }
        }

        return isPoint ? this._hoverStylePoint : this._hoverStyle;
    }

}

window.Mapbender = Mapbender || {};
window.Mapbender.OgcApiSourceLayer = OgcApiSourceLayer;
Mapbender.SourceLayer.typeMap['ogc_api_features'] = OgcApiSourceLayer;

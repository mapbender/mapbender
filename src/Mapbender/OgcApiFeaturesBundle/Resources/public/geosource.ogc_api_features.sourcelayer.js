class OgcApiSourceLayer extends Mapbender.SourceLayer {

    constructor(definition, source, parent) {
        super(definition, source, parent);
        this.tooltipMap = null;
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
        return this.options.tooltip?.propertyMap || this.options.propertyTemplate;
    }

    tooltipForFeature(feature) {
        const properties = feature.getProperties();
        if (!this.tooltipMap) this._buildTooltipMap();
        const propertyTitles = this.options.propertyTitles || {};
        const skipKeys = new Set(['geometry', 'layer', 'featureTitle']);
        const fragment = document.createDocumentFragment();
        let count = 0;
        for (const [key, value] of Object.entries(properties)) {
            if (skipKeys.has(key)) continue;
            if (value == null || value === '') continue;
            if (typeof value === 'object') continue;
            if (this.tooltipMap && !this.tooltipMap[key]) continue;
            const row = document.createElement('div');
            row.className = 'ogc-api-tooltip-row';
            const label = document.createElement('span');
            label.className = 'ogc-api-tooltip-key';
            const mapLabel = this.tooltipMap?.[key];
            // Prefer propertyTitles when the propertyMap just echoes the raw key
            label.textContent = (mapLabel && mapLabel !== key ? mapLabel : null) || propertyTitles[key] || key;
            const val = document.createElement('span');
            val.className = 'ogc-api-tooltip-val';
            val.textContent = value;
            row.appendChild(label);
            row.appendChild(val);
            fragment.appendChild(row);
            count++;
        }
        return count > 0 ? fragment : null;
    }

    _buildTooltipMap() {
        this.tooltipMap = {};
        for (const entry of this.options.tooltip.propertyMap) {
            if (typeof entry === 'string') {
                this.tooltipMap[entry] = Mapbender.trans(entry);
            } else {
                const [key, value] = Object.entries(entry)[0];
                this.tooltipMap[key] = Mapbender.trans(value);
            }
        }
    }

}

window.Mapbender = Mapbender || {};
window.Mapbender.OgcApiSourceLayer = OgcApiSourceLayer;
Mapbender.SourceLayer.typeMap['ogc_api_features'] = OgcApiSourceLayer;

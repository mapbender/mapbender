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
        if (!this.options.legend.enabled) return null;

        return {
            topLevel: true,
            type: 'style',
            title: this.options.title,
            layers: this._getLegend(),
        }
    }

    _getStopValue(value) {
        let val = value;
        if (typeof value === 'object' && value.stops) {
            // always use the last stop value (which will be the biggest value)
            return value.stops.at(-1)[1];
        }
        return val;
    }

    /**
     * @return {Promise<LegendDefinitionLayer[]>}
     */
    async _getLegend() {
        if (!this.styleJson) {
            const response = await fetch(this.options.jsonUrl);
            this.styleJson = await response.json();
            if (this.styleJson.sprite) {
                const responseSprint = await fetch(this.styleJson.sprite + '.json');
                this.spriteJson = await responseSprint.json();
            }
        }
        const propertyMap = this.source._getPropertyMap("legend");
        const map = this.styleJson.layers
            .map((layer) => {
                let title = layer.id;
                if (propertyMap) {
                    // if a propertyMap is defined, only use the layers that are defined there
                    if (!propertyMap[layer.id]) return null;
                    title = propertyMap[layer.id];
                }
                if (layer.type === 'symbol' && layer.layout?.['icon-image'] && !layer.layout['icon-image'].includes('{')) {
                    return this._wrapLegendEntry(layer, title, this._createImageLegendEntry(layer));
                }
                if (layer.type === 'fill' || layer.type === 'line') {
                    return this._wrapLegendEntry(layer, title, this._createShapeLegendEntry(layer));
                }
                if (layer.type === 'circle') {
                    return this._wrapLegendEntry(layer, title, this._createCircleLegendEntry(layer));
                }
                return null;
            })
            .filter((layer) => layer !== null);

        if (propertyMap) {
            const keys = Object.keys(propertyMap);
            map.sort((a, b) => keys.indexOf(a.id) - keys.indexOf(b.id));
        }
        return map;
    }

    _wrapLegendEntry(layer, title, style) {
        return {
            title: title,
            id: layer.id,
            style: style
        };
    }

    _createShapeLegendEntry(layer) {
        return {
            strokeColor: this._getStopValue(layer.paint?.['line-color']),
            strokeOpacity: this._getStopValue(layer.paint?.['line-opacity']),
            strokeWidth: this._getStopValue(layer.paint?.['line-width']),
            fillColor: this._getStopValue(layer.paint?.['fill-color']),
            fillOpacity: this._getStopValue(layer.paint?.['fill-opacity']),
        }
    }

    _createCircleLegendEntry(layer) {
        return {
            strokeColor: this._getStopValue(layer.paint?.['circle-stroke-color']),
            strokeOpacity: this._getStopValue(layer.paint?.['circle-stroke-opacity']),
            strokeWidth: this._getStopValue(layer.paint?.['circle-stroke-width']),
            fillColor: this._getStopValue(layer.paint?.['circle-color']),
            fillOpacity: this._getStopValue(layer.paint?.['circle-opacity']),
            radius: this._getStopValue(layer.paint?.['circle-radius']),
            circle: true,
        }
    }

    _createImageLegendEntry(layer) {
        const spriteJsonElement = this.spriteJson?.[layer.layout['icon-image']];
        return {
            image: this.styleJson.sprite + ".png",
            imageX: spriteJsonElement?.x || 0,
            imageY: spriteJsonElement?.y || 0,
            imageWidth: spriteJsonElement?.width || undefined,
            imageHeight: spriteJsonElement?.height || undefined,
        }
    }
}


window.Mapbender = Mapbender || {};
window.Mapbender.VectorTilesSourceLayer = VectorTilesSourceLayer;
Mapbender.SourceLayer.typeMap['vector_tiles'] = VectorTilesSourceLayer;

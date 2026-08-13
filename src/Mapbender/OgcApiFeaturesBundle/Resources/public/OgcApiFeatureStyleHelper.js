window.Mapbender = window.Mapbender || {}
window.Mapbender.OgcApiFeature = window.Mapbender.OgcApiFeature || {}

window.Mapbender.OgcApiFeature.StyleHelper = class {

    static createSimpleOlStyle(s) {
        const fillColor = this._hexToRgba(s.fillColor || '#3399CC', s.fillOpacity ?? 1);
        const strokeColor = this._hexToRgba(s.strokeColor || '#ffffff', s.strokeOpacity ?? 1);
        const strokeWidth = parseFloat(s.strokeWidth) || 1;
        const pointRadius = parseFloat(s.pointRadius) || 5;
        const lineDash = this._getDashArray(s.strokeDashstyle);

        const fill = new ol.style.Fill({ color: fillColor });
        const stroke = new ol.style.Stroke({
            color: strokeColor,
            width: strokeWidth,
            lineCap: s.strokeLinecap || 'round',
            lineJoin: 'round',
        });
        if (lineDash) {
            stroke.setLineDash(lineDash);
        }

        const styles = {
            Point: new ol.style.Style({
                image: new ol.style.Circle({
                    radius: pointRadius,
                    fill: fill,
                    stroke: stroke,
                }),
            }),
            LineString: new ol.style.Style({
                stroke: stroke,
            }),
            Polygon: new ol.style.Style({
                fill: fill,
                stroke: stroke,
            }),
        };
        styles.MultiPoint = styles.Point;
        styles.MultiLineString = styles.LineString;
        styles.MultiPolygon = styles.Polygon;
        styles.GeometryCollection = styles.Polygon;

        if (!s.label) {
            return (feature) => styles[feature.getGeometry()?.getType()] || styles.Polygon;
        }

        const labelTemplate = s.label;
        // Normalize font values - fallback to 'normal' if invalid CSS values
        const isValidFontWeight = (val) => val === 'normal' || val === 'bold' || !isNaN(parseInt(val));
        const isValidFontStyle = (val) => val === 'normal' || val === 'italic' || val === 'oblique';
        const fontWeight = isValidFontWeight(s.fontWeight) ? (s.fontWeight || 'normal') : 'normal';
        const fontStyle = isValidFontStyle(s.fontStyle) ? (s.fontStyle ?? 'normal') : 'normal';
        const fontSize = (s.fontSize || 12) + 'px';
        const fontFamily = s.fontFamily || 'Arial, Helvetica, sans-serif';
        const fontColor = s.fontColor || '#000000';
        const textBaseOptions = {
            font: fontStyle + ' ' + fontWeight + ' ' + fontSize + ' ' + fontFamily,
            fill: new ol.style.Fill({ color: fontColor }),
        };

        if (!/\$\{[^}]+\}/.test(labelTemplate)) {
            // Static label — attach once to each base style
            const text = new ol.style.Text({ ...textBaseOptions, text: labelTemplate });
            Object.values(styles).forEach(st => st.setText(text));
            return (feature) => styles[feature.getGeometry()?.getType()] || styles.Polygon;
        }

        // Dynamic label — resolve ${property} placeholders per feature at render time
        const styleCache = new Map();
        return (feature) => {
            const geomType = feature.getGeometry()?.getType() || 'Polygon';
            const base = styles[geomType] || styles.Polygon;
            const props = feature.getProperties();
            const resolved = labelTemplate.replace(/\$\{([^}]+)\}/g, (_, key) => {
                const val = props[key];
                return val != null ? String(val) : '';
            });
            const cacheKey = geomType + '\x00' + resolved;
            let style = styleCache.get(cacheKey);
            if (!style) {
                style = new ol.style.Style({
                    image: base.getImage(),
                    fill: base.getFill(),
                    stroke: base.getStroke(),
                    text: new ol.style.Text({ ...textBaseOptions, text: resolved }),
                });
                styleCache.set(cacheKey, style);
            }
            return style;
        };
    }

    static applyMapboxStyle(vectorLayer, mbStyle, collectionId) {
        // Find the source name that uses our collectionId as source-layer
        let sourceName = null;
        for (const layer of (mbStyle.layers || [])) {
            if (layer['source-layer'] === collectionId && layer.source) {
                sourceName = layer.source;
                break;
            }
        }
        // Fallback: use first vector/geojson source
        if (!sourceName) {
            for (const [name, src] of Object.entries(mbStyle.sources || {})) {
                if (src.type === 'vector' || src.type === 'geojson') {
                    sourceName = name;
                    break;
                }
            }
        }
        if (!sourceName) {
            return;
        }
        vectorLayer.set('_collectionId', collectionId);
        try {
            ol.mapboxStyle.stylefunction(vectorLayer, mbStyle, sourceName);
        } catch (e) {
            console.error('[OgcApiStyle] ol.mapboxStyle.stylefunction failed for "' + collectionId + '":', e);
        }
    }

    static _hexToRgba(hex, opacity) {
        hex = (hex || '#000000').replace('#', '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        const r = parseInt(hex.substring(0,2), 16);
        const g = parseInt(hex.substring(2,4), 16);
        const b = parseInt(hex.substring(4,6), 16);
        const alpha = isNaN(parseFloat(opacity)) ? 1 : parseFloat(opacity);
        return [r, g, b, alpha];
    }

    static _getDashArray(dashStyle) {
        const map = {
            'dash': [10, 5],
            'dot': [2, 5],
            'dashdot': [10, 5, 2, 5],
            'longdash': [20, 5],
            'longdashdot': [20, 5, 2, 5],
        };
        return map[dashStyle] || undefined;
    }

}

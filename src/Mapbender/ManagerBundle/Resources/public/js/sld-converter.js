/**
 * SLD / SE to Mapbox Style JSON converter.
 *
 * Uses the browser's native DOMParser — no external dependencies.
 *
 * Supported SLD symbolizers:
 *   - PolygonSymbolizer  → fill-color, fill-opacity, line-color, line-width, line-opacity
 *   - LineSymbolizer      → line-color, line-width, line-opacity, line-dasharray
 *   - PointSymbolizer     → circle-radius, circle-color, circle-opacity, circle-stroke-color, circle-stroke-width
 *
 * Unsupported / ignored (produces warnings):
 *   - TextSymbolizer (labels, halos, placement)
 *   - RasterSymbolizer
 *   - External graphic references (OnlineResource marks)
 *   - Multiple Rule sets / SLD filters (only first rule used)
 *   - Vendor-specific extensions (se:VendorOption etc.)
 *   - Complex expressions or property-based styling
 *
 * Exposes window.Mapbender.SldConverter
 */
class SldConverter {
    /**
     * Convert an SLD/SE XML string to a Mapbox Style JSON object.
     * @param {string} xmlStr — Raw XML content
     * @returns {{ style: object, warnings: string[] }}
     */
    static convert(xmlStr) {
        const warnings = [];
        const parser = new DOMParser();
        const doc = parser.parseFromString(xmlStr, 'application/xml');

        const parseErr = doc.querySelector('parsererror');
        if (parseErr) {
            return { style: null, warnings: ['XML parse error: ' + parseErr.textContent.substring(0, 200)] };
        }

        // Collect all symbolizers regardless of namespace
        const allElements = doc.getElementsByTagName('*');

        const polygonSyms = [];
        const lineSyms = [];
        const pointSyms = [];

        for (const el of allElements) {
            const local = el.localName;
            if (local === 'PolygonSymbolizer') polygonSyms.push(el);
            else if (local === 'LineSymbolizer') lineSyms.push(el);
            else if (local === 'PointSymbolizer') pointSyms.push(el);
            else if (local === 'TextSymbolizer') warnings.push('TextSymbolizer found but not supported — labels will be skipped.');
            else if (local === 'RasterSymbolizer') warnings.push('RasterSymbolizer found but not supported — raster rules will be skipped.');
        }

        // Check for multiple Rules (we only convert the first symbolizer of each type)
        const rules = [];
        for (const el of allElements) {
            if (el.localName === 'Rule') rules.push(el);
        }
        if (rules.length > 1) {
            warnings.push(`${rules.length} rules found — only the first symbolizer of each type is converted. Complex rule-based styling is not supported.`);
        }

        // Check for Filter elements
        for (const el of allElements) {
            if (el.localName === 'Filter' || el.localName === 'ElseFilter') {
                warnings.push('SLD Filter expressions are not supported and will be ignored.');
                break;
            }
        }

        const layers = [];

        // ── Polygon ──
        if (polygonSyms.length) {
            const sym = polygonSyms[0];
            const fill = SldConverter._parseFill(sym, warnings);
            const stroke = SldConverter._parseStroke(sym, warnings);
            layers.push({
                id: 'sld-fill',
                type: 'fill',
                paint: {
                    'fill-color': fill.color,
                    'fill-opacity': fill.opacity,
                }
            });
            if (stroke.color !== 'none') {
                layers.push({
                    id: 'sld-fill-outline',
                    type: 'line',
                    paint: {
                        'line-color': stroke.color,
                        'line-width': stroke.width,
                        'line-opacity': stroke.opacity,
                    }
                });
            }
        }

        // ── Line ──
        if (lineSyms.length) {
            const sym = lineSyms[0];
            const stroke = SldConverter._parseStroke(sym, warnings);
            const linePaint = {
                'line-color': stroke.color,
                'line-width': stroke.width,
                'line-opacity': stroke.opacity,
            };
            if (stroke.dasharray) {
                linePaint['line-dasharray'] = stroke.dasharray;
            }
            layers.push({
                id: 'sld-line',
                type: 'line',
                paint: linePaint
            });
        }

        // ── Point ──
        if (pointSyms.length) {
            const sym = pointSyms[0];
            const pt = SldConverter._parsePointSymbolizer(sym, warnings);
            layers.push({
                id: 'sld-circle',
                type: 'circle',
                paint: {
                    'circle-radius': pt.radius,
                    'circle-color': pt.fillColor,
                    'circle-opacity': pt.fillOpacity,
                    'circle-stroke-color': pt.strokeColor,
                    'circle-stroke-width': pt.strokeWidth,
                }
            });
        }

        if (!layers.length) {
            warnings.push('No supported symbolizers found in the SLD file.');
            return { style: null, warnings };
        }

        const style = {
            version: 8,
            name: SldConverter._getText(doc, 'Name') || 'Imported SLD Style',
            sources: {},
            layers
        };
        return { style, warnings };
    }

    // ── Internal helpers ──

    static _findChild(parent, localName) {
        for (const ch of parent.children) {
            if (ch.localName === localName) return ch;
        }
        // Search deeper (namespace-prefixed elements)
        for (const ch of parent.getElementsByTagName('*')) {
            if (ch.localName === localName) return ch;
        }
        return null;
    }

    static _getText(ctx, localName) {
        for (const el of ctx.getElementsByTagName('*')) {
            if (el.localName === localName) return el.textContent.trim();
        }
        return null;
    }

    static _parseCssParam(parent, name) {
        for (const el of parent.getElementsByTagName('*')) {
            if ((el.localName === 'CssParameter' || el.localName === 'SvgParameter')
                && el.getAttribute('name') === name) {
                return el.textContent.trim();
            }
        }
        return null;
    }

    static _parseFill(sym) {
        const fillEl = SldConverter._findChild(sym, 'Fill');
        if (!fillEl) return { color: '#000000', opacity: 1 };
        const color = SldConverter._parseCssParam(fillEl, 'fill') || '#000000';
        const opacity = parseFloat(SldConverter._parseCssParam(fillEl, 'fill-opacity')) || 1;
        return { color, opacity };
    }

    static _parseStroke(sym) {
        const strokeEl = SldConverter._findChild(sym, 'Stroke');
        if (!strokeEl) return { color: 'none', width: 0, opacity: 1, dasharray: null };
        const color = SldConverter._parseCssParam(strokeEl, 'stroke') || '#000000';
        const width = parseFloat(SldConverter._parseCssParam(strokeEl, 'stroke-width')) || 1;
        const opacity = parseFloat(SldConverter._parseCssParam(strokeEl, 'stroke-opacity')) || 1;
        const dashRaw = SldConverter._parseCssParam(strokeEl, 'stroke-dasharray');
        let dasharray = null;
        if (dashRaw) {
            dasharray = dashRaw.split(/[\s,]+/).map(Number).filter(n => !isNaN(n));
            if (!dasharray.length) dasharray = null;
        }
        return { color, width, opacity, dasharray };
    }

    static _parsePointSymbolizer(sym, warnings) {
        const result = { radius: 5, fillColor: '#ff0000', fillOpacity: 1, strokeColor: '#000000', strokeWidth: 1 };
        const graphic = SldConverter._findChild(sym, 'Graphic');
        if (!graphic) return result;

        // Size
        const sizeStr = SldConverter._getText(graphic, 'Size');
        if (sizeStr) result.radius = Math.max(1, parseFloat(sizeStr) / 2);

        const mark = SldConverter._findChild(graphic, 'Mark');
        if (mark) {
            const wnk = SldConverter._getText(mark, 'WellKnownName');
            if (wnk && wnk !== 'circle' && wnk !== 'square') {
                warnings.push(`Point mark "${wnk}" not fully supported — rendered as circle.`);
            }
            const fill = SldConverter._parseFill(mark, warnings);
            result.fillColor = fill.color;
            result.fillOpacity = fill.opacity;
            const stroke = SldConverter._parseStroke(mark, warnings);
            if (stroke.color !== 'none') {
                result.strokeColor = stroke.color;
                result.strokeWidth = stroke.width;
            }
        }

        // External graphic
        const extGraphic = SldConverter._findChild(graphic, 'ExternalGraphic');
        if (extGraphic) {
            warnings.push('ExternalGraphic (icon images) not supported — rendered as colored circle.');
        }

        return result;
    }
}

window.Mapbender = window.Mapbender || {};
window.Mapbender.SldConverter = SldConverter;

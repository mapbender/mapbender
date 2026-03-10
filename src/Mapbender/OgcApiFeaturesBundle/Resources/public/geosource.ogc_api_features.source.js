class OgcApiSource extends Mapbender.Source {
    constructor(definition) {
        definition.children = [{...definition}];
        super(definition);
        this._currentRequestIds = {};
        this.propertyMaps = [];
    }

    createNativeLayers(srsName, mapOptions) {
        this.nativeLayers = [];
        this._vectorSources = {};
        this.getRootLayer().children.forEach((child) => {
            const collectionId = child.options.collectionId;
            this._currentRequestIds[collectionId] = 0;
            const vectorSource = new ol.source.Vector({
                strategy: ol.loadingstrategy.bbox,
                loader: (extent, resolution, projection) => {
                    this._loadCollection(collectionId, extent, resolution, projection, vectorSource);
                }
            });
            this._vectorSources[collectionId] = vectorSource;
            const layerOptions = {
                source: vectorSource,
            };
            const olStyle = this._createOlStyle(child.options.style);
            if (olStyle) {
                layerOptions.style = olStyle;
            }
            const vectorLayer = new ol.layer.Vector(layerOptions);
            this.nativeLayers.push(vectorLayer);
        });
        this.setOpacity(this.options.opacity);
        return this.nativeLayers;
    }

    _hexToRgba(hex, opacity) {
        hex = (hex || '#000000').replace('#', '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        const r = parseInt(hex.substring(0,2), 16);
        const g = parseInt(hex.substring(2,4), 16);
        const b = parseInt(hex.substring(4,6), 16);
        return [r, g, b, parseFloat(opacity) || 1];
    }

    _getDashArray(dashStyle) {
        const map = {
            'dash': [10, 5],
            'dot': [2, 5],
            'dashdot': [10, 5, 2, 5],
            'longdash': [20, 5],
            'longdashdot': [20, 5, 2, 5],
        };
        return map[dashStyle] || undefined;
    }

    _createOlStyle(styleDef) {
        if (!styleDef) return null;
        // Simple/manual style format
        if (styleDef.fillColor !== undefined || styleDef.strokeColor !== undefined) {
            return this._createSimpleOlStyle(styleDef);
        }
        // Mapbox style format
        if (styleDef.version && styleDef.layers) {
            return this._createMapboxOlStyle(styleDef);
        }
        return null;
    }

    _createSimpleOlStyle(s) {
        const fillColor = this._hexToRgba(s.fillColor || '#ff0000', s.fillOpacity ?? 1);
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
        // Add label if defined
        if (s.label) {
            const text = new ol.style.Text({
                text: s.label,
                font: (s.fontSize || '12') + 'px ' + (s.fontFamily || 'Arial, Helvetica, sans-serif'),
                fill: new ol.style.Fill({ color: s.fontColor || '#000000' }),
            });
            Object.values(styles).forEach(st => st.setText(text));
        }

        styles.MultiPoint = styles.Point;
        styles.MultiLineString = styles.LineString;
        styles.MultiPolygon = styles.Polygon;
        styles.GeometryCollection = styles.Polygon;

        return (feature) => {
            const geomType = feature.getGeometry()?.getType();
            return styles[geomType] || styles.Polygon;
        };
    }

    _createMapboxOlStyle(mbStyle) {
        // Extract paint properties from first relevant layer
        const paintLayer = mbStyle.layers?.find(l =>
            l.type === 'fill' || l.type === 'line' || l.type === 'circle'
        );
        if (!paintLayer) return null;

        const paint = paintLayer.paint || {};
        const type = paintLayer.type;

        let fill, stroke, image;

        if (type === 'fill') {
            const fc = paint['fill-color'] || '#ff0000';
            const fo = paint['fill-opacity'] ?? 1;
            const oc = paint['fill-outline-color'];
            fill = new ol.style.Fill({ color: this._parseMbColor(fc, fo) });
            stroke = oc ? new ol.style.Stroke({ color: this._parseMbColor(oc, 1), width: 1 }) : undefined;
        } else if (type === 'line') {
            const lc = paint['line-color'] || '#000000';
            const lo = paint['line-opacity'] ?? 1;
            const lw = paint['line-width'] || 1;
            stroke = new ol.style.Stroke({
                color: this._parseMbColor(lc, lo),
                width: typeof lw === 'number' ? lw : 1,
            });
        } else if (type === 'circle') {
            const cr = paint['circle-radius'] || 5;
            const cc = paint['circle-color'] || '#ff0000';
            const co = paint['circle-opacity'] ?? 1;
            const sc = paint['circle-stroke-color'];
            const sw = paint['circle-stroke-width'] || 0;
            image = new ol.style.Circle({
                radius: typeof cr === 'number' ? cr : 5,
                fill: new ol.style.Fill({ color: this._parseMbColor(cc, co) }),
                stroke: sc ? new ol.style.Stroke({ color: this._parseMbColor(sc, 1), width: sw }) : undefined,
            });
        }

        return new ol.style.Style({ fill, stroke, image });
    }

    _parseMbColor(color, opacity) {
        if (typeof color === 'string') {
            if (color.startsWith('#')) {
                return this._hexToRgba(color, opacity);
            }
            // Try to parse rgba/rgb
            const rgbaMatch = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
            if (rgbaMatch) {
                return [
                    parseInt(rgbaMatch[1]),
                    parseInt(rgbaMatch[2]),
                    parseInt(rgbaMatch[3]),
                    rgbaMatch[4] !== undefined ? parseFloat(rgbaMatch[4]) * opacity : opacity,
                ];
            }
            return color; // CSS named color, let OL handle it
        }
        if (Array.isArray(color)) {
            // Mapbox expression - just use a default
            return this._hexToRgba('#ff0000', opacity);
        }
        return this._hexToRgba('#ff0000', opacity);
    }

    _loadCollection(collectionId, extent, resolution, projection, source) {
        const requestId = ++this._currentRequestIds[collectionId];
        const projCode = projection.getCode();
        const bboxExtent = ol.proj.transformExtent(extent, projCode, 'EPSG:4326');
        const bbox = bboxExtent.join(',');
        const geojsonReader = new ol.format.GeoJSON();
        const child = this.getRootLayer().children.find(c => c.options.collectionId === collectionId);
        const limit = child?.options?.featureLimit || this.options.featureLimit;
        const apiEndpoint = `${this.options.jsonUrl}/collections/${collectionId}/items?f=json&bbox=${bbox}&limit=${limit}`;
        fetch(apiEndpoint)
            .then((resp) => {
                if (!resp.ok) {
                    throw new Error('HTTP ' + resp.status);
                }
                return resp.json();
            })
            .then((data) => {
                if (requestId !== this._currentRequestIds[collectionId]) {
                    return;
                }
                const parsed = geojsonReader.readFeatures(data, {
                    dataProjection: 'EPSG:4326',
                    featureProjection: projCode
                });
                source.clear(true);
                source.addFeatures(parsed);
            })
            .catch((err) => {
                console.error(`Failed to load collection "${collectionId}":`, err);
            });
    }

    getSelected() {
        return this.getRootLayer().getSelected();
    }

    updateEngine() {
        const children = this.getRootLayer().children;
        const rootVisible = this.getRootLayer().state.visibility;
        this.nativeLayers.forEach((layer, index) => {
            const childVisible = children[index] ? children[index].state.visibility : false;
            Mapbender.mapEngine.setLayerVisibility(layer, rootVisible && childVisible);
        });
    }

    featureInfoEnabled() {
        return this.getRootLayer().state.info;
    }

    loadFeatureInfo(mapModel, x, y, options, elementId) {
        return [null, this.createFeatureInfoPromise(mapModel, x, y, options, this.id, elementId)];
    }

    async createFeatureInfoPromise(mapModel, x, y, options, sourceId, elementId) {
        const olMap = mapModel.olMap;
        const children = this.getRootLayer().children;
        let features = [];
        this.nativeLayers.forEach((layer, index) => {
            let results = olMap.getFeaturesAtPixel([x, y], {
                layerFilter: (l) => l === layer,
                hitTolerance: options.hitTolerance || 5,
            });
            if (results.length > 0) {
                const childTitle = children[index]?.options?.title || children[index]?.options?.collectionId || '';
                results.forEach((feature) => {
                    feature.set('featureTitle', childTitle, true);
                    features.push(feature);
                });
            }
        });
        if (!features.length) {
            return null;
        }
        const content = document.createElement('div');
        let hasFeatures = false;
        for (const feature of features) {
            const featureContent = this.createFeatureInfoForFeature(feature);
            if (featureContent) {
                hasFeatures = true;
                content.appendChild(featureContent);
            }
        }
        if (!hasFeatures) {
            return null;
        }
        Mapbender?.FeatureInfo?.setupHighlight(content, sourceId, elementId);
        return content;
    }

    createFeatureInfoForFeature(feature) {
        const properties = feature.getProperties();
        let label = properties.featureTitle;
        const geometryDiv = document.createElement('div');
        geometryDiv.className = 'geometryElement';
        geometryDiv.id = this.options.id + '/' + feature.ol_uid;
        const wkt = new ol.format.WKT();
        geometryDiv.setAttribute('data-geometry', wkt.writeFeature(feature));
        geometryDiv.setAttribute('data-srid', 'EPSG:3857');
        geometryDiv.setAttribute('data-label', label);
        if (label) {
            const h5 = document.createElement('h5');
            h5.className = 'featureinfo__title mt-3';
            h5.textContent = label;
            geometryDiv.appendChild(h5);
        }
        const table = document.createElement('table');
        table.className = 'table table-striped table-bordered table-condensed featureinfo__table';
        const tbody = document.createElement('tbody');
        table.appendChild(tbody);
        const propertyMap = this._getPropertyMap('featureInfo');
        Object.entries(properties).forEach(([key, value]) => {
            if (propertyMap && !propertyMap[key]) {
                return;
            }
            const row = document.createElement('tr');
            const headerCell = document.createElement('th');
            headerCell.textContent = propertyMap?.[key] || key;
            const dataCell = document.createElement('td');
            dataCell.textContent = value;
            row.appendChild(headerCell);
            row.appendChild(dataCell);
            tbody.appendChild(row);
        });
        geometryDiv.appendChild(table);
        return geometryDiv;
    }

    _getPropertyMap(subtype) {
        if (!this.options[subtype].propertyMap) {
            return null;
        }
        if (!this.propertyMaps[subtype]) {
            this.propertyMaps[subtype] = {};
            for (const entry of this.options[subtype].propertyMap) {
                if (typeof entry === 'string') {
                    this.propertyMaps[subtype][entry] = Mapbender.trans(entry);
                } else {
                    const [key, value] = Object.entries(entry)[0];
                    this.propertyMaps[subtype][key] = Mapbender.trans(value);
                }
            }
        }
        return this.propertyMaps[subtype];
    }
}

window.Mapbender = Mapbender || {};
window.Mapbender.OgcApiSource = OgcApiSource;
Mapbender.Source.typeMap['ogc_api_features'] = OgcApiSource;

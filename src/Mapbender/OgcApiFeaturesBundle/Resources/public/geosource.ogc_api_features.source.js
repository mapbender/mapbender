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
            const vectorLayer = new ol.layer.Vector(layerOptions);
            // Apply style: use olms.stylefunction for Mapbox styles, custom for simple styles
            this._applyStyle(vectorLayer, child.options.style, collectionId);
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

    _applyStyle(vectorLayer, styleDef, collectionId) {
        if (!styleDef) {
            console.warn('[OgcApiStyle] No styleDef for collection:', collectionId);
            return;
        }
        // Simple/manual style format
        if (styleDef.fillColor !== undefined || styleDef.strokeColor !== undefined) {
            vectorLayer.setStyle(this._createSimpleOlStyle(styleDef));
            return;
        }
        // Mapbox style format — use ol-mapbox-style library
        if (styleDef.version && styleDef.layers && typeof olms !== 'undefined' && olms.stylefunction) {
            this._applyMapboxStyle(vectorLayer, styleDef, collectionId);
            return;
        }
        console.warn('[OgcApiStyle] No style path matched for "' + collectionId + '". olms available:', typeof olms !== 'undefined', '| styleDef keys:', Object.keys(styleDef));
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

    _applyMapboxStyle(vectorLayer, mbStyle, collectionId) {
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
            console.warn('[OgcApiStyle] No source found for collection:', collectionId, '| sources:', Object.keys(mbStyle.sources || {}));
            return;
        }
        console.log('[OgcApiStyle] Applying Mapbox style: collection="' + collectionId + '" source="' + sourceName + '"');
        vectorLayer.set('_collectionId', collectionId);
        try {
            olms.stylefunction(vectorLayer, mbStyle, sourceName);
        } catch (e) {
            console.error('[OgcApiStyle] olms.stylefunction failed for "' + collectionId + '":', e);
        }
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
                // Set 'layer' property so olms.stylefunction can match source-layer
                parsed.forEach(f => f.set('layer', collectionId, true));
                source.clear(true);
                source.addFeatures(parsed);
                // Debug: test style result on first feature
                if (parsed.length > 0) {
                    const olLayer = this.nativeLayers.find(l => l.getSource() === source);
                    const styleFn = olLayer?.getStyle();
                    if (typeof styleFn === 'function') {
                        const result = styleFn(parsed[0], 1000);
                        const styles = Array.isArray(result) ? result : (result ? [result] : []);
                        if (styles.length) {
                            styles.forEach((s, i) => console.log('[OgcApiStyle] "' + collectionId + '" style[' + i + '] fill:', s.getFill?.()?.getColor?.(), '| stroke:', s.getStroke?.()?.getColor?.(), s.getStroke?.()?.getWidth?.()));
                        } else {
                            console.warn('[OgcApiStyle] Style function returned nothing for "' + collectionId + '" \u2014 feature will be invisible. layer prop:', parsed[0].get('layer'));
                        }
                    }
                }
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

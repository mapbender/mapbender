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
            // Apply style from availableStyles lookup
            const styleDef = this._getStyleDef(child);
            this._applyStyle(vectorLayer, styleDef, collectionId);
            vectorLayer.set('_activeStyle', child.options.style || null);
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
        const alpha = parseFloat(opacity);
        return [r, g, b, isNaN(alpha) ? 1 : alpha];
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
            return;
        }
        // Simple/manual style format
        if (styleDef.fillColor !== undefined || styleDef.strokeColor !== undefined) {
            vectorLayer.setStyle(this._createSimpleOlStyle(styleDef));
            return;
        }
        // Mapbox style format — use ol-mapbox-style library (now bundled in ol.mapboxStyle)
        if (styleDef.version && styleDef.layers && typeof ol !== 'undefined' && ol.mapboxStyle && ol.mapboxStyle.stylefunction) {
            this._applyMapboxStyle(vectorLayer, styleDef, collectionId);
            return;
        }
    }

    _createSimpleOlStyle(s) {
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
        const fontWeight = s.fontWeight === 'bold' ? 'bold' : 'normal';
        const fontStyle = s.fontStyle === 'italic' ? 'italic' : (s.fontWeight === 'italic' ? 'italic' : 'normal');
        const textBaseOptions = {
            font: `${fontStyle} ${fontWeight} ${s.fontSize || 12}px ${s.fontFamily || 'Arial, Helvetica, sans-serif'}`,
            fill: new ol.style.Fill({ color: s.fontColor || '#000000' }),
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

    _applyMapboxStyle(vectorLayer, mbStyle, collectionId) {
        const resolved = this._resolveMapboxSource(mbStyle, collectionId);
        if (!resolved) {
            return;
        }
        if (!this._mapboxSourceLayers) this._mapboxSourceLayers = {};
        this._mapboxSourceLayers[collectionId] = resolved.effectiveSourceLayer;
        vectorLayer.set('_collectionId', collectionId);
        try {
            ol.mapboxStyle.stylefunction(vectorLayer, mbStyle, resolved.sourceName);
        } catch (e) {
            console.error('[OgcApiStyle] ol.mapboxStyle.stylefunction failed for "' + collectionId + '":', e);
        }
    }

    /**
     * Resolves the Mapbox source name and the effective source-layer for a given collectionId.
     *
     * The collectionId may differ from the style's source-layer name when the style was built
     * for an MVT dataset with a different layer name. The stylefunction looks up styles via
     * l[feature.getProperties()['mvt:layer']], so features must be tagged with the source-layer
     * name from the style, not the collectionId.
     *
     * @returns {{sourceName: string, effectiveSourceLayer: string}|null}
     */
    _resolveMapboxSource(mbStyle, collectionId) {
        // Prefer an exact match: a style layer whose source-layer equals the collectionId
        for (const layer of (mbStyle.layers || [])) {
            if (layer['source-layer'] === collectionId && layer.source) {
                return { sourceName: layer.source, effectiveSourceLayer: collectionId };
            }
        }
        // Fallback: use the first vector/geojson source in the style
        let sourceName = null;
        for (const [name, src] of Object.entries(mbStyle.sources || {})) {
            if (src.type === 'vector' || src.type === 'geojson') {
                sourceName = name;
                break;
            }
        }
        if (!sourceName) {
            return null;
        }
        // Find the first source-layer referenced by that source
        let effectiveSourceLayer = collectionId;
        for (const layer of (mbStyle.layers || [])) {
            if (layer.source === sourceName && layer['source-layer']) {
                effectiveSourceLayer = layer['source-layer'];
                break;
            }
        }
        return { sourceName, effectiveSourceLayer };
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
        // Dispatch a load-start event so _initLayerEvents (@see mapbender.model.js) picks it up
        source.dispatchEvent('imageloadstart');
        fetch(apiEndpoint)
            .then((resp) => {
                if (!resp.ok) {
                    throw new Error('HTTP ' + resp.status);
                }
                return resp.json();
            })
            .then((data) => {
                if (requestId !== this._currentRequestIds[collectionId]) {
                    console.warn(`Discarding response for collection "${collectionId}" due to newer request`);
                    return;
                }
                const parsed = geojsonReader.readFeatures(data, {
                    dataProjection: 'EPSG:4326',
                    featureProjection: projCode
                });
                // Set 'layer' property for internal use (tooltips, identification)
                // Set 'mvt:layer' property so ol-mapbox-style's stylefunction can match source-layer
                const mvtLayer = this._mapboxSourceLayers?.[collectionId] ?? collectionId;
                parsed.forEach(f => {
                    f.set('layer', collectionId, true);
                    f.set('mvt:layer', mvtLayer, true);
                    // Expose properties on the feature object itself so the ol-mapbox-style
                    // expression evaluator (EvaluationContext.properties()) can read them.
                    // That method accesses feature.properties (GeoJSON convention), not OL's
                    // feature.get(). Without this, ['get', 'prop'] in filters returns undefined.
                    f.properties = f.getProperties();
                });
                source.clear(true);
                source.addFeatures(parsed);
                source.dispatchEvent('imageloadend');
            })
            .catch((err) => {
                console.error(`Failed to load collection "${collectionId}":`, err);
                source.dispatchEvent('imageloaderror');
            });
    }

    getSelected() {
        return this.getRootLayer().getSelected();
    }

    _getStyleDef(child) {
        const styleName = child.options.style;
        const available = child.options.availableStyles;
        if (!styleName || !available) return null;
        const found = available.find(s => s.name === styleName);
        return found ? found.style : null;
    }

    updateEngine() {
        const children = this.getRootLayer().children;
        const rootVisible = this.getRootLayer().state.visibility;
        this.nativeLayers.forEach((layer, index) => {
            const child = children[index];
            const childVisible = child ? child.state.visibility : false;
            Mapbender.mapEngine.setLayerVisibility(layer, rootVisible && childVisible);
            if (child && child.options.style && child.options.style !== layer.get('_activeStyle')) {
                const styleDef = this._getStyleDef(child);
                if (styleDef) {
                    this._applyStyle(layer, styleDef, child.options.collectionId);
                    layer.set('_activeStyle', child.options.style);
                }
            }
        });
        if (!this._tooltipInitialized && Mapbender.Model?.olMap) {
            const hasTooltip = children.some(c => c.options.tooltip?.propertyMap);
            if (hasTooltip) {
                this._initTooltip(Mapbender.Model.olMap);
            }
        }
        if (!this._resolutionListenerInitialized && Mapbender.Model?.olMap) {
            this._initResolutionListener(Mapbender.Model.olMap);
        }
    }

    _initResolutionListener(olMap) {
        this._resolutionListenerInitialized = true;
        this._lastResolution = olMap.getView().getResolution();
        olMap.on('moveend', () => {
            const newResolution = olMap.getView().getResolution();
            if (newResolution !== this._lastResolution) {
                this._lastResolution = newResolution;
                Object.values(this._vectorSources).forEach(source => {
                    source.refresh();
                });
            }
        });
    }

    _initTooltip(olMap) {
        this._tooltipInitialized = true;
        const container = document.createElement('div');
        container.className = 'ogc-api-tooltip';
        container.style.display = 'none';
        document.body.appendChild(container);
        this._tooltipElement = container;
        this._tooltipOverlay = new ol.Overlay({
            element: container,
            offset: [12, 0],
            positioning: 'center-left',
            stopEvent: false,
        });
        olMap.addOverlay(this._tooltipOverlay);
        this._tooltipDebounce = null;
        olMap.on('pointermove', (evt) => {
            if (evt.dragging) {
                this._hideTooltip();
                return;
            }
            clearTimeout(this._tooltipDebounce);
            this._tooltipDebounce = setTimeout(() => {
                this._handlePointerMove(olMap, evt.pixel, evt.coordinate);
            }, 60);
        });
        olMap.getViewport().addEventListener('mouseout', () => {
            this._hideTooltip();
        });
    }

    _handlePointerMove(olMap, pixel, coordinate) {
        let hit = null;
        let hitChild = null;
        const children = this.getRootLayer().children;
        for (let i = 0; i < this.nativeLayers.length; i++) {
            const layer = this.nativeLayers[i];
            const features = olMap.getFeaturesAtPixel(pixel, {
                layerFilter: (l) => l === layer,
                hitTolerance: 5,
            });
            if (features.length) {
                hit = features[0];
                hitChild = children[i];
                break;
            }
        }
        if (!hit || !hitChild?.options?.tooltip?.propertyMap) {
            this._hideTooltip();
            olMap.getTargetElement().style.cursor = '';
            return;
        }
        olMap.getTargetElement().style.cursor = 'pointer';
        const content = this._buildTooltipContent(hit, hitChild);
        if (!content) {
            this._hideTooltip();
            return;
        }
        this._tooltipElement.innerHTML = '';
        this._tooltipElement.appendChild(content);
        this._tooltipElement.style.display = '';
        this._tooltipOverlay.setPosition(coordinate);
    }

    _buildTooltipContent(feature, child) {
        const properties = feature.getProperties();
        const propertyMap = this._getChildTooltipMap(child);
        const propertyTitles = child.options.propertyTitles || {};
        const skipKeys = new Set(['geometry', 'layer', 'featureTitle']);
        const fragment = document.createDocumentFragment();
        let count = 0;
        for (const [key, value] of Object.entries(properties)) {
            if (skipKeys.has(key)) continue;
            if (value == null || value === '') continue;
            if (typeof value === 'object') continue;
            if (propertyMap && !propertyMap[key]) continue;
            const row = document.createElement('div');
            row.className = 'ogc-api-tooltip-row';
            const label = document.createElement('span');
            label.className = 'ogc-api-tooltip-key';
            const mapLabel = propertyMap?.[key];
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

    _hideTooltip() {
        if (this._tooltipElement) {
            this._tooltipElement.style.display = 'none';
        }
    }

    _getChildTooltipMap(child) {
        const mapArray = child.options.tooltip?.propertyMap;
        if (!mapArray) return null;
        const cid = child.options.collectionId;
        if (!this._tooltipMaps) this._tooltipMaps = {};
        if (!this._tooltipMaps[cid]) {
            this._tooltipMaps[cid] = {};
            for (const entry of mapArray) {
                if (typeof entry === 'string') {
                    this._tooltipMaps[cid][entry] = Mapbender.trans(entry);
                } else {
                    const [key, value] = Object.entries(entry)[0];
                    this._tooltipMaps[cid][key] = Mapbender.trans(value);
                }
            }
        }
        return this._tooltipMaps[cid];
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
        // Find propertyTitles for this feature's collection
        const collectionId = feature.get('layer');
        const child = this.getRootLayer().children.find(c => c.options.collectionId === collectionId);
        const propertyTitles = child?.options?.propertyTitles || {};
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
            headerCell.textContent = propertyMap?.[key] || propertyTitles[key] || key;
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

    setLayerOrder(layerIdOrder) {
        var listsSorted = [];
        var _pickChildId = function(ids, layer) {
            if (!ids.length) {
                return null;
            } else {
                var ix = ids.indexOf(layer.options.id);
                if (ix !== -1) {
                    return ids[ix];
                }
            }
            if (layer.children && layer.children.length) {
                for (var ci = 0; ci < layer.children.length; ++ci) {
                    var ch = _pickChildId(ids, layer.children[ci]);
                    if (ch !== null) {
                        return ch;
                    }
                }
            }
            return null;
        };
        var _siblingSort = function(a, b) {
            var childA = _pickChildId(layerIdOrder, a);
            var childB = _pickChildId(layerIdOrder, b);
            var ixA = layerIdOrder.includes(childA) ? layerIdOrder.indexOf(childA) : Number.MAX_SAFE_INTEGER;
            var ixB = layerIdOrder.includes(childB) ? layerIdOrder.indexOf(childB) : Number.MIN_SAFE_INTEGER;
            return parseInt(ixA, 10) - parseInt(ixB, 10);
        };
        var parentIdOrder = [];
        for (var idIx = 0; idIx < layerIdOrder.length; ++idIx) {
            var layerId = layerIdOrder[idIx];
            var layerObj = this.getLayerById(layerId);
            if (!layerObj) continue;
            if (listsSorted.indexOf(layerObj.siblings) === -1) {
                layerObj.siblings.sort(_siblingSort);
                listsSorted.push(layerObj.siblings);
            }
            if (layerObj.parent) {
                var parentId = layerObj.parent.options.id;
                if (parentId && parentIdOrder.indexOf(parentId) === -1) {
                    parentIdOrder.push(parentId);
                }
            }
        }
        if (parentIdOrder.length) {
            this.setLayerOrder(this, parentIdOrder);
        }
        // Rebuild nativeLayers array to match the new children order
        var children = this.getRootLayer().children;
        var reorderedNativeLayers = [];
        children.forEach((child) => {
            var collectionId = child.options.collectionId;
            var vectorSource = this._vectorSources[collectionId];
            if (vectorSource) {
                var nativeLayer = this.nativeLayers.find((nl) => nl.getSource() === vectorSource);
                if (nativeLayer) {
                    reorderedNativeLayers.push(nativeLayer);
                }
            }
        });
        // Only update if we matched all layers
        if (reorderedNativeLayers.length === this.nativeLayers.length) {
            this.nativeLayers = reorderedNativeLayers;
            // Reorder the actual OL layers on the map to match the new nativeLayers order.
            // We need to find the map and update layer positions relative to other sources.
            var olMap = Mapbender.mapModel?.olMap || Mapbender.Model?.olMap;
            if (olMap) {
                var mapLayers = olMap.getLayers();
                // Determine the position range of this source's native layers in the map's layer collection
                var positions = [];
                this.nativeLayers.forEach(function(nl) {
                    var pos = mapLayers.getArray().indexOf(nl);
                    if (pos !== -1) {
                        positions.push(pos);
                    }
                });
                if (positions.length === this.nativeLayers.length) {
                    // Sort positions ascending so we know which map slots belong to this source
                    positions.sort(function(a, b) { return a - b; });
                    // Place reordered native layers into the same map positions
                    for (var i = 0; i < positions.length; i++) {
                        var currentPos = mapLayers.getArray().indexOf(this.nativeLayers[i]);
                        var targetPos = positions[i];
                        if (currentPos !== targetPos) {
                            mapLayers.removeAt(currentPos);
                            mapLayers.insertAt(targetPos, this.nativeLayers[i]);
                        }
                    }
                }
            }
        }
    }
}

window.Mapbender = Mapbender || {};
window.Mapbender.OgcApiSource = OgcApiSource;
Mapbender.Source.typeMap['ogc_api_features'] = OgcApiSource;

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
            const vectorLayer = new ol.layer.Vector({
                source: vectorSource,
                // Set per-collection styles here:
                // style: this._getStyleForCollection(collectionId),
            });
            this.nativeLayers.push(vectorLayer);
        });
        this.setOpacity(this.options.opacity);
        return this.nativeLayers;
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

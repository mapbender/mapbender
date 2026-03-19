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
                    return;
                }
                const parsed = geojsonReader.readFeatures(data, {
                    dataProjection: 'EPSG:4326',
                    featureProjection: projCode
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

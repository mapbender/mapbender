class VectorTilesSource extends Mapbender.Source {
    constructor(definition) {
        definition.children = [{...definition}];
        super(definition);
        this.wkt = new ol.format.WKT();
    }

    createNativeLayers(srsName, mapOptions) {
        this.nativeLayers = [new ol.layer.MapboxVector({
            styleUrl: this.options.jsonUrl,
            opacity: this.options.opacity,
        })];
        return this.nativeLayers;
    }

    getPrintConfigs(bounds, scale, srsName) {
        const boundsArray = [bounds.left, bounds.bottom, bounds.right, bounds.top];
        const bbox = Mapbender.mapEngine.transformBounds(boundsArray, srsName, "EPSG:3857");
        return [{
            ...this._getPrintBaseOptions(),
            styleUrl: this.options.jsonUrl,
            bbox: bbox,
        }];
    }

    getSelected() {
        return this.getRootLayer().getSelected();
    }

    featureInfoEnabled() {
        return this.getRootLayer().state.info;
    }

    loadFeatureInfo(mapModel, x, y, options, elementId) {
        return [null, this.createFeatureInfoPromise(mapModel, x, y, options, this.id, elementId)];
    }

    async createFeatureInfoPromise(mapModel, x, y, options, sourceId, elementId) {
        const layer = this.getNativeLayer();
        const olMap = mapModel.olMap;
        const features = olMap.getFeaturesAtPixel([x, y], {
            layerFilter: (l) => l === layer,
            hitTolerance: options.hitTolerance || 5,
        });
        if (!features.length) {
            return null;
        }
        const content = document.createElement("div");
        let hasFeatures = false;
        for (const feature of features) {
            const featureContent = this.createFeatureInfoForFeature(feature);
            if (featureContent) {
                hasFeatures = true;
                content.appendChild(featureContent);
            }
        }
        if (!hasFeatures) return null;

        Mapbender?.FeatureInfo?.setupHighlight(content, sourceId, elementId);
        return content;
    }

    createFeatureInfoForFeature(feature) {
        const properties = feature.getProperties();
        let label;
        if (this.options.featureInfo.title) {
            label = this._labelReplaceRegex(this.options.featureInfo.title, feature);
        } else {
            label = properties.label || properties.name || properties.title;
        }

        if (!label && this.options.featureInfo.hideIfNoTitle) return null;

        const geometryDiv = document.createElement('div');
        geometryDiv.className = 'geometryElement';
        geometryDiv.id = this.options.id + "/" + feature.ol_uid;
        geometryDiv.setAttribute('data-geometry', this.wkt.writeFeature(ol.render.toFeature(feature)));
        geometryDiv.setAttribute('data-srid', 'EPSG:3857');
        geometryDiv.setAttribute('data-label', label);

        if (label) {
            const h5 = document.createElement("h5");
            h5.className = "featureinfo__title";
            h5.textContent = label;
            geometryDiv.appendChild(h5);
        }

        const table = document.createElement('table');
        table.className = 'table table-striped table-bordered table-condensed featureinfo__table';
        const tbody = document.createElement('tbody');
        table.appendChild(tbody);

        const propertyMap = this._getPropertyMap('featureInfo');
        Object.entries(properties).forEach(([key, value]) => {
            if (propertyMap && !propertyMap[key]) return;

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

    /**
     * @param subtype {"featureInfo"|"legend"}
     */
    _getPropertyMap(subtype) {
        if (!this.options[subtype].propertyMap) return null;
        if (!this.propertyMap) {
            this.propertyMap = {};
            for (const entry of this.options[subtype].propertyMap) {
                if (typeof entry === 'string') {
                    this.propertyMap[entry] = Mapbender.trans(entry);
                } else {
                    const [key, value] = Object.entries(entry)[0];
                    this.propertyMap[key] = Mapbender.trans(value);
                }
            }
        }
        return this.propertyMap;
    }

    _labelReplaceRegex(labelWithRegex, feature) {
        let regex = /\${([^}]+)}/g;
        let match = [];
        let hasMatch = false;
        let label = labelWithRegex;

        while ((match = regex.exec(labelWithRegex)) !== null) {
            let featureValue = (feature.get(match[1])) ? feature.get(match[1]).toString() : '';
            if (featureValue !== '') {
                hasMatch = true;
            }
            label = label.replace(match[0], featureValue);
        }
        return hasMatch ? label : null;
    }

    updateEngine() {
        Mapbender.mapEngine.setLayerVisibility(this.getNativeLayer(), this.getRootLayer().state.visibility);
    }

    setLayerOrder(newLayerIdOrder) {
        // do nothing, there are no sublayers for vector tile sources
    }
}

window.Mapbender = Mapbender || {};
window.Mapbender.VectorTilesSource = VectorTilesSource;
Mapbender.Source.typeMap['vector_tiles'] = VectorTilesSource;

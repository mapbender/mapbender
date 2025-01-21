/**
 * @typedef {Object} RasterPrintDataRecord
 * @property {string} type
 * @property {Number|null} minResolution
 * @property {Number|null} maxResolution
 * @property {string} url
 */
/**
 * @typedef {Object} SourceSettings
 * @property {Number} opacity
 * @property {Array<{id: Number, name: String}>} selectedLayers
 */
/**
 * @typedef {Object} SourceSettingsDiff
 * @property {Number} [opacity]
 * @property {Array<Object>} [activate]
 * @property {Array<Object>} [deactivate]
 * @property {Array<Object>} [changeStyle]
 */

window.Mapbender = Mapbender || {};

(function () {
    Mapbender.LayerGroup = class LayerGroup {
        constructor(title, parent) {
            /**
             * @type string
             * @private
             */
            this.title_ = title;
            /**
             * @type {null|Mapbender.LayerGroup}
             */
            this.parent = parent || null;
            /**
             *
             * @type {Mapbender.LayerGroup[]}
             */
            this.children = [];
            /**
             * @type {Mapbender.LayerGroup[]}
             */
            this.siblings = [this];
        }

        getTitle() {
            return this.title_;
        }

        /**
         * returnes whether this layergroup and all its parents are selected
         * @see getSelected
         * @returns {Boolean}
         */
        getActive() {
            let active = this.getSelected();
            let parent = this.parent;
            while (parent && active) {
                active = active && parent.getSelected();
                parent = parent.parent;
            }
            return active;
        }

        /**
         * returns whether this layer is currently selected.
         * @see getActive
         * @return Boolean
         * @abstract
         */
        getSelected() {
            throw new Error("Invoked abstract LayerGroup.getSelected");
        }

        getParent() {
            return this.parent;
        }

        /**
         * removes the given child layer from this LayerGroup
         * @param {Mapbender.LayerGroup} child
         */
        removeChild(child) {
            [this.children, this.siblings].forEach(function (list) {
                const index = list.indexOf(child);
                if (-1 !== index) {
                    list.splice(index, 1);
                }
            });
        }
    }

    /**
     * The most basic LayerGroup definition that is shown in the layer tree but does not display anything itself
     */
    Mapbender.Layerset = class Layerset extends Mapbender.LayerGroup {
        constructor(title, id, selected) {
            super(title, null);
            /**
             * @type {string|number}
             */
            this.id = id;
            /**
             * @type Boolean
             */
            this.selected = selected;
        }

        getId() {
            return this.id;
        }

        getSelected() {
            return this.selected;
        }

        setSelected(state) {
            this.selected = !!state;
        }

        getSettings() {
            return {
                selected: this.getSelected()
            };
        }

        /**
         * Changes all given layer settings
         * @see getSettings
         * @param {{selected?: Boolean}} settings
         * @returns {boolean} true if at least one attribute has been changed
         */
        applySettings(settings) {
            let dirty = false;
            if ("selected" in settings) {
                dirty = settings.selected !== this.selected;
                this.setSelected(settings.selected);
            }
            return dirty;
        }
    }

    /**
     * A source represents a data source that is displayed on the map. WmsSource, WmtsSource, GeoJsonSource etc. extend
     * from this base class. A source can have one or more SourceLayers.
     * They must be defined in the constructor using definition.configuration.children
     * @see SourceLayer
     * @abstract
     */
    Mapbender.Source = class Source extends Mapbender.LayerGroup {
        constructor(definition) {
            super(definition.title, null);

            /**
             * A unique identifier for this source
             * @type {null|string}
             */
            this.id = null;
            if (definition.id || definition.id === 0) {
                this.id = '' + definition.id;
            }

            /**
             * The native OpenLayers layers
             * @see createNativeLayers
             * @see getNativeLayers
             * @type {ol.Layer[]}
             */
            this.nativeLayers = [];

            /**
             * Indicates whether this source has been added by the user during runtime, e.g. by using the WMSLoader
             * dynamic sources are removed when the view is reset
             * @type {boolean}
             */
            this.isDynamicSource = false;
            if ("isDynamicSource" in definition) this.isDynamicSource = definition.isDynamicSource;

            /**
             * a unique identifier for the type of source, e.g. 'wms' or 'geojson'
             * @type {string}
             */
            this.type = definition.type || 'undefined';

            /**
             * Configuration options for this source, keys depend on the source type
             * @type {object}
             */
            this.configuration = definition.configuration || {};
            this.configuration.options = this.configuration.options || {};
            this.configuration.children = (this.configuration.children || []).map(childDef => Mapbender.SourceLayer.factory(childDef, this, null));

            this.children = this.configuration.children;
            this.configuredSettings_ = this.getSettings();

            /**
             * the (optional) layerset this source is assigned to
             * @type {null|Mapbender.Layerset}
             */
            this.layerset = null;
        }

        /**
         * Creates a new source instance
         * @param {*} definition object containing at least the attribute 'type'
         * @returns {Mapbender.Source}
         */
        static factory(definition) {
            var typeClass = Source.typeMap[definition.type];
            if (!typeClass) {
                typeClass = Mapbender.Source;
            }
            return new typeClass(definition);
        }

        /**
         * Creates the native OpenLayers layers required for this source and stores them in the class variable nativeLayers
         * @abstract
         * @param {String} srsName
         * @param {Object} [mapOptions]
         * @return {ol.Layer[]}
         */
        createNativeLayers(srsName, mapOptions) {
            console.error("Layer creation not implemented", this);
            throw new Error("Layer creation not implemented");
        }

        /**
         * Layer state has changed, e.g. layers have been reordered, deselected etc. Refresh the view in the native layers.
         * @abstract
         */
        updateEngine() {
            console.error("Update engine not implemented", this);
            throw new Error("Update engine not implemented");
        }

        /**
         * @returns {ol.Layer[]}
         */
        getNativeLayers() {
            return this.nativeLayers.slice();
        }

        getActive() {
            const upstream = super.getActive();
            // NOTE: some sources like e.g. WmsLoader sources don't have a layerset
            return upstream && (!this.layerset || this.layerset.getSelected());
        }

        /**
         * destroys all currently assigned native layers
         * @param {ol.Map} olMap
         */
        destroyLayers(olMap) {
            if (this.nativeLayers && this.nativeLayers.length) {
                this.nativeLayers.map(function (olLayer) {
                    Mapbender.mapEngine.destroyLayer(olMap, olLayer);
                });
            }
            this.nativeLayers = [];
        }

        getSettings() {
            return {
                opacity: this.configuration.options.opacity
            };
        }

        getConfiguredSettings() {
            return Object.assign({}, this.configuredSettings_);
        }

        /**
         * Returns all layers that support feature info
         * @return {Array<Mapbender.SourceLayer>}
         */
        getFeatureInfoLayers() {
            return [];
        }

        /**
         * @param {SourceSettings} settings
         * @return {boolean} true if at least one setting has been changed
         */
        applySettings(settings) {
            const diff = this.diffSettings(this.getSettings(), settings);
            if (diff) {
                this.applySettingsDiff(diff);
                return true;
            } else {
                return false;
            }
        }

        /**
         * @param {SourceSettingsDiff} diff
         */
        applySettingsDiff(diff) {
            if (diff && typeof (diff.opacity) !== 'undefined') {
                this.setOpacity(diff.opacity);
            }
        }

        /**
         * @param {SourceSettings} from
         * @param {SourceSettings} to
         * @return {SourceSettingsDiff|null}
         */
        diffSettings(from, to) {
            // before v4, only the selectedIds were saved as an array.
            // since v4, an object with id and name is saved are saved encapsulated as an array of objects to
            // check for selectedIds for backwards compatibility
            // @todo for v5: Remove check
            const diff = to.hasOwnProperty('selectedIds') ?
                {
                    activate: to.selectedIds.filter(function (id) {
                        return (from.selectedLayers || []).findIndex(fromLayer =>
                            fromLayer.id === id
                        ) === -1;
                    }),
                    deactivate: (from.selectedLayers || []).filter(function (layer) {
                        return -1 === to.selectedIds.indexOf(layer.id);
                    })
                } :
                {
                    activate: (to.selectedLayers || []).filter(function (layer) {
                        return (from.selectedLayers || []).findIndex(fromLayer =>
                            fromLayer.id === layer.id ||
                            fromLayer.name === layer.name
                        ) === -1;
                    }),

                    deactivate: (from.selectedLayers || []).filter(function (layer) {
                        return (to.selectedLayers || []).findIndex(toLayer =>
                            toLayer.id === layer.id ||
                            toLayer.name === layer.name
                        ) === -1;
                    })
                };
            diff.changeStyle = (to.selectedLayers || []).filter((layer) => !!layer.style);

            if (to.opacity !== from.opacity) {
                diff.opacity = to.opacity
            }
            if (!diff.activate.length) {
                delete (diff.activate);
            }
            if (!diff.deactivate.length) {
                delete (diff.deactivate);
            }
            if (!diff.changeStyle.length) {
                delete (diff.changeStyle);
            }
            // null if completely empty
            return Object.keys(diff).length && diff || null;
        }

        /**
         * @param {SourceSettings} base
         * @param {SourceSettingsDiff} diff
         * @return {SourceSettings}
         */
        mergeSettings(base, diff) {
            var settings = Object.assign({}, base);
            if (typeof (diff.opacity) !== 'undefined') {
                settings.opacity = diff.opacity;
            }
            settings.selectedLayers = settings.selectedLayers.filter(function (layer) {
                return -1 === ((diff || {}).deactivate || []).findIndex(diffLayer => (diffLayer.options?.id ?? diffLayer.id) === (layer.options?.id ?? layer.id));
            });
            settings.selectedLayers = settings.selectedLayers.concat((diff || {}).activate || []);

            if (diff.changeStyle) {
                for (const layer of settings.selectedLayers) {
                    for (const styleLayer of diff.changeStyle) {
                        if (layer.id === styleLayer.id) {
                            layer.style = styleLayer.style;
                        }
                    }
                }
            }

            return settings;
        }

        /**
         * indicates whether this source should be recreated when a srs change occurs
         * @param {string} oldProj
         * @param {string} newProj
         * @returns {boolean}
         */
        checkRecreateOnSrsSwitch(oldProj, newProj) {
            return false;
        }

        /**
         * Gets the native OpenLayers layer of the specified index
         * @see createNativeLayers
         * @param {number|undefined} index
         * @returns {ol.Layer|null}
         */
        getNativeLayer(index) {
            const layer = this.nativeLayers[index || 0] || null;
            const c = this.nativeLayers.length;
            if (typeof index === 'undefined' && c !== 1) {
                console.warn("Mapbender.Source.getNativeLayer called on a source with flexible layer count; currently " + c + " native layers");
            }
            return layer;
        }

        /**
         * @param {string} id
         * @return {SourceLayer}
         */
        getLayerById(id) {
            let foundLayer = null;
            Mapbender.Util.SourceTree.iterateLayers(this, false, function (sourceLayer) {
                if ((sourceLayer.options?.id ?? sourceLayer.id) === id) {
                    foundLayer = sourceLayer;
                    // abort iteration
                    return false;
                }
            });
            return foundLayer;
        }

        /**
         * @returns {Mapbender.SourceLayer}
         */
        getRootLayer() {
            return this.configuration.children[0];
        }

        /**
         * @param {number|undefined} layerId
         * @param {string} projCode
         * @param {boolean} inheritFromParent
         * @returns {number[]|boolean} false if bounds could not be calculated
         */
        getLayerBounds(layerId, projCode, inheritFromParent) {
            const layer = layerId ? this.getLayerById(layerId) : this.configuration.children[0];

            if (!layer) {
                console.warn("No layer, unable to calculate bounds");
                return false;
            }
            return layer.getBounds(projCode, inheritFromParent) || null;
        }

        /**
         * @param {number} value between 0 and 1
         */
        setOpacity(value) {
            this.configuration.options.opacity = value;
            this.nativeLayers.map(function (layer) {
                layer.setOpacity(value);
            });
        }

        /**
         * reorders the layers of this source. The default implementation forwards to
         * [Mapbender.Geo.SourceHandler.setLayerOrder] which works for hierarchical sources like WMS and WMTS
         * @param {string[]} newLayerIdOrder the layer ids in their new order
         */
        setLayerOrder(newLayerIdOrder) {
            Mapbender.Geo.SourceHandler.setLayerOrder(this, newLayerIdOrder);
        }

        _bboxArrayToBounds(bboxArray, projCode) {
            return Mapbender.mapEngine.boundsFromArray(bboxArray);
        }

        _getPrintBaseOptions() {
            return {
                type: this.configuration.type,
                sourceId: this.id,
                opacity: this.configuration.options.opacity
            };
        }

        /**
         * Returns information that is passed to the printing service when printing or exporting a map
         * @param {Number[]} bounds
         * @param {Number} scale
         * @param {String} srsName
         * @return {Array<Object>}
         */
        getPrintConfigs(bounds, scale, srsName) {
            return [];
        }

        /**
         * Custom toJSON for mbMap.getMapState()
         * Drops nativeLayers to avoid circular references
         * @returns {{configuration: Object, id: (string|null), title, type: string}}
         */
        toJSON() {
            return {
                id: this.id,
                title: this.title,
                type: this.type,
                configuration: this.configuration
            };
        }
    }


    /**
     * @abstract
     */
    Mapbender.SourceLayer = class SourceLayer extends Mapbender.LayerGroup {
        constructor(definition, source, parent) {
            super(((definition || {}).options || {}).title || '', parent)
            this.options = definition.options || {};
            this.options.treeOptions = this.options.treeOptions || {
                selected: true,
                info: false,
                toggle: true,
                allow: {selected: true, info: false, toggle: true,}
            };

            this.state = definition.state || {
                info: null,
                outOfBounds: false,
                outOfScale: false,
                visibility: true,
            };

            this.source = source;
            var childDefs = definition.children || [];
            var i, child, childDef;
            for (i = 0; i < childDefs.length; ++i) {
                childDef = childDefs[i];
                child = Mapbender.SourceLayer.factory(childDef, source, this);
                child.siblings = this.children;
                this.children.push(child);
            }
            this.siblings = [this];
        }

        static factory(definition, source, parent) {
            let typeClass = SourceLayer.typeMap[source.type];
            if (!typeClass) {
                typeClass = Mapbender.SourceLayer;
            }
            return new typeClass(definition, source, parent);
        }

        /**
         * is this layer selected in the layertree
         * Caution: This does not mean it's visible, parent layers might be unselected
         * @returns {boolean}
         */
        getSelected() {
            return this.options.treeOptions.selected;
        }

        setSelected(state) {
            this.options.treeOptions.selected = !!state;
        }

        getId() {
            return this.options.id;
        }

        getName() {
            return this.options.name;
        }

        /**
         * Should the layer be displayed at this scale level?
         * @param {number} scale
         * @returns {boolean}
         */
        isInScale(scale) {
            return true;
        }

        /**
         * Does the layer has contents in this extent?
         * @param {number[]} extent
         * @param {string} srsName
         * @returns {boolean}
         */
        intersectsExtent(extent, srsName) {
            return true;
        }

        /**
         * need custom toJSON for getMapState call
         */
        toJSON() {
            // Skip the circular-ref inducing properties 'siblings', 'parent' and 'source'
            const r = {
                options: this.options,
                state: this.state
            };
            if (this.children && this.children.length) {
                r.children = this.children;
            }
            return r;
        }

        /**
         * removes this layer from the source tree
         * @returns {string|null} the if of the removed layer or null of the layer had no parent
         */
        remove() {
            const index = this.siblings.indexOf(this);
            if (index === -1) {
                return null;
            }

            this.siblings.splice(index, 1);
            if (!this.siblings.length && this.parent && this.parent.remove) {
                return this.parent.remove();
            }

            return this.options.id;
        }

        /**
         * @param {Mapbender.SourceLayer} child
         */
        addChild(child) {
            this.children.push(child);
            this.children.forEach((child) => child.siblings = this.children);
            Mapbender.Model.updateSource(this.source);
        }

        /**
         * @param {Mapbender.SourceLayer[]} children
         */
        addChildren(children) {
            for(const child of children) {
                this.children.push(child);
                this.children.forEach((child) => child.siblings = this.children);
            }
            Mapbender.Model.updateSource(this.source);
        }

        /**
         * @param {string} projCode
         * @param {boolean} inheritFromParent
         * @returns {number[]|boolean} false if bounds could not be calculated
         */
        getBounds(projCode, inheritFromParent) {
            var bboxMap = this.options.bbox;
            var srsOrder = [projCode].concat(Object.keys(bboxMap));
            for (var i = 0; i < srsOrder.length; ++i) {
                var srsName = srsOrder[i];
                var bboxArray = bboxMap[srsName];
                if (bboxArray) {
                    var bounds = this.source._bboxArrayToBounds(bboxArray, srsName);
                    return Mapbender.mapEngine.transformBounds(bounds, srsName, projCode);
                }
            }
            var inheritParent_ = inheritFromParent || (typeof inheritFromParent === 'undefined');
            if (inheritParent_ && this.parent) {
                return this.parent.getBounds(projCode, true);
            }
            return null;
        }

        /**
         * Returns the legend for this layer. The legend can be either an external
         * url (e.g. for WMS services) or a style definition that is rendered on a canvas
         *
         * @param {boolean} forPrint true if the legend is exported for print (false if its for display)
         * @return {null|{type: 'url', url: string, topLevel: boolean}|LegendDefinition}
         */
        getLegend(forPrint) {
            return null;
        }

        /**
         * is this layer restricted to spatial bbox?
         * @returns {boolean}
         */
        hasBounds() {
            var layer = this;
            do {
                if (Object.keys(layer.options.bbox).length) {
                    return true;
                }
                layer = layer.parent;
            } while (layer);
            return false;
        }

        /**
         * Returns a list of menu options supported by this layer.
         * Core mapbender menu options:
         * - layerremove: Deletes this layer
         * - metadata: Opens metadata in a new window. options.medataUrl should be defined
         * - opacity: Opacity slider between 0 and 1. See {Mapbender.Source.setOpacity}
         * - dimension: selection slider for dimensions like e.g. time
         * - zoomtolayer: Changes the map's view to fit the layer
         * @returns {string[]}
         */
        getSupportedMenuOptions() {
            const supported = ['layerremove'];
            if (this.options.metadataUrl) {
                supported.push('metadata');
            }
            // opacity + dimension are only available on root layer
            if (!this.getParent()) {
                supported.push('opacity');
                if ((this.source.configuration.options.dimensions || []).length) {
                    supported.push('dimension');
                }
            }
            if (this.hasBounds()) {
                supported.push('zoomtolayer');
            }
            if (this.options.availableStyles && this.options.availableStyles.length > 1 && !this.children.length) {
                supported.push('select_style');
            }
            return supported;
        }
    }

    Mapbender.Source.typeMap = {};
    Mapbender.SourceLayer.typeMap = {};
}());

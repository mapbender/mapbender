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

(function () {
    /**
     * A source represents a data source that is displayed on the map. WmsSource, WmtsSource, GeoJsonSource etc. extend
     * from this base class. A source can have one or more SourceLayers.
     * They must be defined in the constructor using definition.children
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

            this.isBaseSource = definition.isBaseSource;

            /**
             * a unique identifier for the type of source, e.g. 'wms' or 'geojson'
             * @type {string}
             */
            this.type = definition.type || 'undefined';

            /**
             * Configuration options for this source, keys depend on the source type
             * @type {object}
             */
            this.options = definition.options || {};
            this.children = (definition.children || []).map(childDef => Mapbender.SourceLayer.factory(childDef, this, null));

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
                opacity: this.options.opacity
            };
        }

        getConfiguredSettings() {
            return Object.assign({}, this.configuredSettings_);
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
            return this.children[0];
        }

        /**
         * @param {number|undefined} layerId
         * @param {string} projCode
         * @param {boolean} inheritFromParent
         * @returns {number[]|boolean} false if bounds could not be calculated
         */
        getLayerBounds(layerId, projCode, inheritFromParent) {
            const layer = layerId ? this.getLayerById(layerId) : this.children[0];

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
            this.options.opacity = value;
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
                type: this.type,
                sourceId: this.id,
                opacity: this.options.opacity
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
         * @returns {{children: Array, options: Object, id: (string|null), title, type: string}}
         */
        toJSON() {
            return {
                id: this.id,
                title: this.title,
                type: this.type,
                children: this.children,
                options: this.options,
            };
        }

        featureInfoEnabled() {
            return false;
        }

        /**
         * Loads the feature info for the given coordinates.
         * Per default, a rejected promise is returned (causing the source to not be displayed in the featureInfo popup)
         * @param mapModel Mapbender.Model
         * @param x {number}
         * @param y {number}
         * @param options {maxCount: number, onlyValid: boolean, injectionScript: string} the featureInfo element's options
         * @returns {[?string, Promise<?string>]} An array with two elements: The url for the "open in new window" feature and a promise that loads the featureinfo data in the background.
         */
        loadFeatureInfo(mapModel, x, y, options) {
            return [null, Promise.reject()];
        }
    }

    Mapbender.Source.typeMap = {};
}());

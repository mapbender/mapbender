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
 */

window.Mapbender = Mapbender || {};

(function () {
    Mapbender.LayerGroup = class LayerGroup {
        constructor(title, parent) {
            this.title_ = title;
            this.parent = parent || null;
            this.children = [];
            this.siblings = [this];
        }

        getTitle() {
            return this.title_;
        }

        getActive() {
            var active = this.getSelected();
            var parent = this.parent;
            while (parent && active) {
                active = active && parent.getSelected();
                parent = parent.parent;
            }
            return active;
        }

        /**
         * @return Boolean
         * @abstract
         */
        getSelected() {
            throw new Error("Invoked abstract LayerGroup.getSelected");
        }

        removeChild(child) {
            [this.children, this.siblings].forEach(function (list) {
                var index = list.indexOf(child);
                if (-1 !== index) {
                    list.splice(index, 1);
                }
            });
        }
    }

    Mapbender.Layerset = class Layerset extends Mapbender.LayerGroup {
        constructor(title, id, selected) {
            super(title, null);
            this.id = id;
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

        applySettings(settings) {
            var dirty = settings.selected !== this.selected;
            this.setSelected(settings.selected);
            return dirty;
        }
    }

    /**
     * @abstract
     */
    Mapbender.Source = class Source extends Mapbender.LayerGroup {
        constructor(definition) {
            super(definition.title, null);

            this.id = null;
            this.title = null;
            this.type = null;
            this.configuration = {};
            this.nativeLayers = [];
            this.recreateOnSrsSwitch = false;
            this.wmsloader = false;

            if (definition.id || definition.id === 0) {
                this.id = '' + definition.id;
            }
            this.type = definition.type;
            this.configuration = definition.configuration;
            this.wmsloader = definition.wmsloader;
            var sourceArg = this;
            this.configuration.children = (this.configuration.children || []).map(function (childDef) {
                return Mapbender.SourceLayer.factory(childDef, sourceArg, null)
            });
            this.children = this.configuration.children;
            this.configuredSettings_ = this.getSettings();
        }

        /**
         * @param {*} definition
         * @returns {Mapbender.Source}
         */
        static factory(definition) {
            var typeClass = Source.typeMap[definition.type];
            if (!typeClass) {
                typeClass = Source;
            }
            return new typeClass(definition);
        }

        /**
         * @param {String} srsName
         * @param {Object} [mapOptions]
         * @return {Array<Object>}
         */
        createNativeLayers(srsName, mapOptions) {
            console.error("Layer creation not implemented", this);
            throw new Error("Layer creation not implemented");
        }

        /**
         * @param {String} srsName
         * @param {Object} [mapOptions]
         * @return {Array<Object>}
         */
        initializeLayers(srsName, mapOptions) {
            this.nativeLayers = this.createNativeLayers(srsName, mapOptions);
            return this.nativeLayers;
        }

        getActive() {
            var upstream = Mapbender.LayerGroup.prototype.getActive.call(this);
            // NOTE: (only) WmsLoader sources don't have a layerset
            return upstream && (!this.layerset || this.layerset.getSelected());
        }

        destroyLayers(olMap) {
            if (this.nativeLayers && this.nativeLayers.length) {
                this.nativeLayers.map(function (olLayer) {
                    Mapbender.mapEngine.destroyLayer(olMap, olLayer);
                });
            }
            this.nativeLayers = [];
        }

        getNativeLayers() {
            return this.nativeLayers.slice();
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
         * @param {SourceSettings} settings
         * @return {boolean}
         */
        applySettings(settings) {
            var diff = this.diffSettings(this.getSettings(), settings);
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

            if (to.opacity !== from.opacity) {
                diff.opacity = to.opacity
            }
            if (!diff.activate.length) {
                delete (diff.activate);
            }
            if (!diff.deactivate.length) {
                delete (diff.deactivate);
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
            return settings;
        }

        checkRecreateOnSrsSwitch(oldProj, newProj) {
            return this.recreateOnSrsSwitch;
        }

        getNativeLayer(index) {
            var layer = this.nativeLayers[index || 0] || null;
            var c = this.nativeLayers.length;
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
            var foundLayer = null;
            Mapbender.Util.SourceTree.iterateLayers(this, false, function (sourceLayer) {
                if ((sourceLayer.options?.id ?? sourceLayer.id) === id) {
                    foundLayer = sourceLayer;
                    // abort iteration
                    return false;
                }
            });
            return foundLayer;
        }

        getRootLayer() {
            return this.configuration.children[0];
        }

        _reduceBboxMap(bboxMap, projCode) {
            if (bboxMap && Object.keys(bboxMap).length) {
                if (projCode) {
                    if (bboxMap[projCode]) {
                        var reduced = {};
                        reduced[projCode] = bboxMap[projCode];
                        return reduced;
                    }
                    return null;
                }
                return bboxMap;
            }
            return null;
        }

        getLayerBounds(layerId, projCode, inheritFromParent) {
            var layer;
            if (layerId) {
                layer = this.getLayerById(layerId);
            } else {
                // root layer
                layer = this.configuration.children[0];
            }
            if (!layer) {
                console.warn("No layer, unable to calculate bounds");
                return false;
            }
            return layer.getBounds(projCode, inheritFromParent) || null;
        }

        setOpacity(value) {
            this.configuration.options.opacity = value;
            this.nativeLayers.map(function (layer) {
                layer.setOpacity(value);
            });
        }

        _bboxArrayToBounds(bboxArray, projCode) {
            return Mapbender.mapEngine.boundsFromArray(bboxArray);
        }

        _getPrintBaseOptions() {
            return {
                type: this.configuration.type,
                sourceId: this.id,
                // @todo: use live native layer opacity?
                opacity: this.configuration.options.opacity
            };
        }

        // Custom toJSON for mbMap.getMapState()
        // Drops nativeLayers to avoid circular references
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
            this.state = definition.state || {};
            this.source = source;
            var childDefs = definition.children || [];
            var i, child, childDef;
            for (i = 0; i < childDefs.length; ++i) {
                childDef = childDefs[i];
                child = SourceLayer.factory(childDef, source, this);
                child.siblings = this.children;
                this.children.push(child);
            }
            this.siblings = [this];
        }

        static factory(definition, source, parent) {
            var typeClass = SourceLayer.typeMap[source.type];
            if (!typeClass) {
                typeClass = SourceLayer;
            }
            return new typeClass(definition, source, parent);
        }

        // need custom toJSON for getMapState call
        toJSON() {
            // Skip the circular-ref inducing properties 'siblings', 'parent' and 'source'
            var r = {
                options: this.options,
                state: this.state
            };
            if (this.children && this.children.length) {
                r.children = this.children;
            }
            return r;
        }

        getParent() {
            return this.parent;
        }

        remove() {
            var index = this.siblings.indexOf(this);
            if (index !== -1) {
                this.siblings.splice(index, 1);
                if (!this.siblings.length && this.parent) {
                    return this.parent.remove();
                } else {
                    return this.options.id;
                }
            } else {
                return null;
            }
        }

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
    }

    Mapbender.Source.typeMap = {};
    Mapbender.SourceLayer.typeMap = {};
}());

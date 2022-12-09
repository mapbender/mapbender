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
 * @property {Array<String>} selectedIds
 */
/**
 * @typedef {Object} SourceSettingsDiff
 * @property {Number} [opacity]
 * @property {Array<String>} [activate]
 * @property {Array<String>} [deactivate]
 */

window.Mapbender = Mapbender || {};

window.Mapbender.LayerGroup = (function() {
    function LayerGroup(title, parent) {
        this.title_ = title;
        this.parent = parent || null;
        this.children = [];
        this.siblings = [this];
    }
    Object.assign(LayerGroup.prototype, {
        getTitle: function() {
            return this.title_;
        },
        getActive: function() {
            var active = this.getSelected();
            var parent = this.parent;
            while (parent && active) {
                active = active && parent.getSelected();
                parent = parent.parent;
            }
            return active;
        },
        /**
         * @return Boolean
         * @abstract
         */
        getSelected: function() {
            throw new Error("Invoked abstract LayerGroup.getSelected");
        },
        removeChild: function(child) {
            [this.children, this.siblings].forEach(function(list) {
                var index = list.indexOf(child);
                if (-1 !== index) {
                    list.splice(index, 1);
                }
            });
        }
    });
    return LayerGroup;
})();

window.Mapbender.Layerset = (function() {
    function Layerset(title, id, selected) {
        Mapbender.LayerGroup.call(this, title, null);
        this.id = id;
        this.selected = selected;
    }
    Layerset.prototype = Object.create(Mapbender.LayerGroup.prototype);
    Object.assign(Layerset.prototype, {
        constructor: Layerset,
        getId: function() {
            return this.id;
        },
        getSelected: function() {
            return this.selected;
        },
        setSelected: function(state) {
            this.selected = !!state;
        },
        getSettings: function() {
            return {
                selected: this.getSelected()
            };
        },
        applySettings: function(settings) {
            var dirty = settings.selected !== this.selected;
            this.setSelected(settings.selected);
            return dirty;
        }
    });
    return Layerset;
})();

window.Mapbender.Source = (function() {
    function Source(definition) {
        Mapbender.LayerGroup.call(this, definition.title, null);
        if (definition.id || definition.id === 0) {
            this.id = '' + definition.id;
        }
        this.type = definition.type;
        this.configuration = definition.configuration;
        this.wmsloader = definition.wmsloader;
        var sourceArg = this;
        this.configuration.children = (this.configuration.children || []).map(function(childDef) {
            return Mapbender.SourceLayer.factory(childDef, sourceArg, null)
        });
        this.children = this.configuration.children;
        this.configuredSettings_ = this.getSettings();
    }
    Source.typeMap = {};
    /**
     * @param {*} definition
     * @returns {Mapbender.Source}
     */
    Source.factory = function(definition) {
        var typeClass = Source.typeMap[definition.type];
        if (!typeClass) {
            typeClass = Source;
        }
        return new typeClass(definition);
    };
    Source.prototype = Object.create(Mapbender.LayerGroup.prototype);
    Object.assign(Source.prototype, {
        constructor: Source,
        /**
         * @param {String} srsName
         * @param {Object} [mapOptions]
         * @return {Array<Object>}
         */
        createNativeLayers: function(srsName, mapOptions) {
            console.error("Layer creation not implemented", this);
            throw new Error("Layer creation not implemented");
        },
        /**
         * @param {String} srsName
         * @param {Object} [mapOptions]
         * @return {Array<Object>}
         */
        initializeLayers: function(srsName, mapOptions) {
            this.nativeLayers = this.createNativeLayers(srsName, mapOptions);
            return this.nativeLayers;
        },
        getActive: function() {
            var upstream = Mapbender.LayerGroup.prototype.getActive.call(this);
            // NOTE: (only) WmsLoader sources don't have a layerset
            return upstream && (!this.layerset || this.layerset.getSelected());
        },
        id: null,
        title: null,
        type: null,
        configuration: {},
        nativeLayers: [],
        recreateOnSrsSwitch: false,
        wmsloader: false,
        destroyLayers: function(olMap) {
            if (this.nativeLayers && this.nativeLayers.length) {
                this.nativeLayers.map(function(olLayer) {
                    Mapbender.mapEngine.destroyLayer(olMap, olLayer);
                });
            }
            this.nativeLayers = [];
        },
        getNativeLayers: function() {
            return this.nativeLayers.slice();
        },
        getSettings: function() {
            return {
                opacity: this.configuration.options.opacity
            };
        },
        getConfiguredSettings: function() {
            return Object.assign({}, this.configuredSettings_);
        },
        /**
         * @param {SourceSettings} settings
         * @return {boolean}
         */
        applySettings: function(settings) {
            var diff = this.diffSettings(this.getSettings(), settings);
            if (diff) {
                this.applySettingsDiff(diff);
                return true;
            } else {
                return false;
            }
        },
        /**
         * @param {SourceSettingsDiff} diff
         */
        applySettingsDiff: function(diff) {
            if (diff && typeof (diff.opacity) !== 'undefined') {
                this.setOpacity(diff.opacity);
            }
        },
        /**
         * @param {SourceSettings} from
         * @param {SourceSettings} to
         * @return {SourceSettingsDiff|null}
         */
        diffSettings: function(from, to) {
            var diff = {
                activate: to.selectedIds.filter(function(id) {
                    return -1 === from.selectedIds.indexOf(id);
                }),
                deactivate: from.selectedIds.filter(function(id) {
                    return -1 === to.selectedIds.indexOf(id);
                })
            };
            if (to.opacity !== from.opacity) {
                diff.opacity = to.opacity
            }
            if (!diff.activate.length) {
                delete(diff.activate);
            }
            if (!diff.deactivate.length) {
                delete(diff.deactivate);
            }
            // null if completely empty
            return Object.keys(diff).length && diff || null;
        },
        /**
         * @param {SourceSettings} base
         * @param {SourceSettingsDiff} diff
         * @return {SourceSettings}
         */
        mergeSettings: function(base, diff) {
            var settings = Object.assign({}, base);
            if (typeof (diff.opacity) !== 'undefined') {
                settings.opacity = diff.opacity;
            }
            settings.selectedIds = settings.selectedIds.filter(function(id) {
                return -1 === ((diff || {}).deactivate || []).indexOf(id);
            });
            settings.selectedIds = settings.selectedIds.concat((diff || {}).activate || []);
            return settings;
        },
        checkRecreateOnSrsSwitch: function(oldProj, newProj) {
            return this.recreateOnSrsSwitch;
        },
        getNativeLayer: function(index) {
            var layer =  this.nativeLayers[index || 0] || null;
            var c = this.nativeLayers.length;
            if (typeof index === 'undefined' && c !== 1) {
                console.warn("Mapbender.Source.getNativeLayer called on a source with flexible layer count; currently "  + c + " native layers");
            }
            return layer;
        },
        /**
         * @param {string} id
         * @return {SourceLayer}
         */
        getLayerById: function(id) {
            var foundLayer = null;
            Mapbender.Util.SourceTree.iterateLayers(this, false, function(sourceLayer) {
                if (sourceLayer.options.id === id) {
                    foundLayer = sourceLayer;
                    // abort iteration
                    return false;
                }
            });
            return foundLayer;
        },
        getRootLayer: function() {
            return this.configuration.children[0];
        },
        _reduceBboxMap: function(bboxMap, projCode) {
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
        },
        getLayerBounds: function(layerId, projCode, inheritFromParent) {
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
        },
        setOpacity: function(value) {
            this.configuration.options.opacity = value;
            this.nativeLayers.map(function(layer) {
                layer.setOpacity(value);
            });
        },
        _bboxArrayToBounds: function(bboxArray, projCode) {
            return Mapbender.mapEngine.boundsFromArray(bboxArray);
        },
        _getPrintBaseOptions: function() {
            return {
                type: this.configuration.type,
                sourceId: this.id,
                // @todo: use live native layer opacity?
                opacity: this.configuration.options.opacity
            };
        },
        // Custom toJSON for mbMap.getMapState()
        // Drops nativeLayers to avoid circular references
        toJSON: function() {
            return {
                id: this.id,
                title: this.title,
                type: this.type,
                configuration: this.configuration
            };
        }
    });
    return Source;
}());

window.Mapbender.SourceLayer = (function() {
    function SourceLayer(definition, source, parent) {
        Mapbender.LayerGroup.call(this, ((definition || {}).options || {}).title || '', parent)
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
    SourceLayer.prototype = Object.create(Mapbender.LayerGroup.prototype);
    Object.assign(SourceLayer.prototype, {
        constructor: SourceLayer,
        // need custom toJSON for getMapState call
        toJSON: function() {
            // Skip the circular-ref inducing properties 'siblings', 'parent' and 'source'
            var r = {
                options: this.options,
                state: this.state
            };
            if (this.children && this.children.length) {
                r.children = this.children;
            }
            return r;
        },
        getParent: function() {
            return this.parent;
        },
        remove: function() {
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
        },
        getBounds: function(projCode, inheritFromParent) {
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
        },
        hasBounds: function() {
            var layer = this;
            do {
                if (Object.keys(layer.options.bbox).length) {
                    return true;
                }
                layer = layer.parent;
            } while (layer);
            return false;
        }
    });
    SourceLayer.typeMap = {};
    SourceLayer.factory = function(definition, source, parent) {
        var typeClass = SourceLayer.typeMap[source.type];
        if (!typeClass) {
            typeClass = SourceLayer;
        }
        return new typeClass(definition, source, parent);
    };
    return SourceLayer;
}());

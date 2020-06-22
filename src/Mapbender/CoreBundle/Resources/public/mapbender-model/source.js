/**
 * @typedef {Object} RasterPrintDataRecord
 * @property {string} type
 * @property {Number|null} minResolution
 * @property {Number|null} maxResolution
 * @property {string} url
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
            throw new Error("Invoked abstract LayerGroup.getSelected_");
        }
    });
    return LayerGroup;
})();

window.Mapbender.Layerset = (function() {
    function Layerset(title, id) {
        Mapbender.LayerGroup.call(this, title, null);
        this.id = id;
        // layersets always start out enabled
        this.selected = true;
    }
    Layerset.prototype = Object.create(Mapbender.LayerGroup.prototype);
    Object.assign(Layerset.prototype, {
        constructor: Layerset,
        getSelected: function() {
            return this.selected;
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
        if (definition.origId || definition.origId === 0) {
            this.origId = '' + definition.origId;
        }
        this.type = definition.type;
        this.configuration = definition.configuration;
        this.wmsloader = definition.wmsloader;
        var sourceArg = this;
        this.configuration.children = (this.configuration.children || []).map(function(childDef) {
            return Mapbender.SourceLayer.factory(childDef, sourceArg, null)
        });
        this.children = this.configuration.children;
    }
    Source.typeMap = {};
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
        createNativeLayers: function(srsName) {
            console.error("Layer creation not implemented", this);
            throw new Error("Layer creation not implemented");
        },
        initializeLayers: function(srsName) {
            this.nativeLayers = this.createNativeLayers(srsName);
            return this.nativeLayers;
        },
        id: null,
        origId: null,
        mqlid: null,
        title: null,
        type: null,
        configuration: {},
        nativeLayers: [],
        recreateOnSrsSwitch: false,
        wmsloader: false,
        rewriteLayerIds: function() {
            if (!this.id) {
                throw new Error("Can't rewrite layer ids with empty source id");
            }
            var rootLayer = this.configuration.children[0];
            rootLayer.rewriteChildIds(this.id);
        },
        destroyLayers: function() {
            if (this.nativeLayers && this.nativeLayers.length) {
                this.nativeLayers.map(function(olLayer) {
                    Mapbender.mapEngine.destroyLayer(olLayer);
                });
            }
            this.nativeLayers = [];
        },
        getNativeLayers: function() {
            return this.nativeLayers.slice();
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
        supportsMetadata: function() {
            return !(this.wmsloader || isNaN(parseInt(this.origId)));
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
        /**
         * @param {string} layerId
         * @param {boolean} [inheritFromParent] from parent layers; default true
         * @param {boolean} [inheritFromSource] from source; default true
         * @returns {null|Object.<string,Array.<float>>} mapping of EPSG code to BBOX coordinate pair; null if completely unrestricted
         */
        getLayerExtentConfigMap: function(layerId, inheritFromParent, inheritFromSource) {
            var inheritParent_ = inheritFromParent || (typeof inheritFromParent === 'undefined');
            var inheritSource_ = inheritFromSource || (typeof inheritFromSource === 'undefined');
            var sourceLayer = this.getLayerById(layerId);
            var boundsMap = null;
            while (sourceLayer && !boundsMap) {
                boundsMap = this._reduceBboxMap(sourceLayer.options.bbox);
                if (inheritParent_) {
                    sourceLayer = sourceLayer.parent
                } else {
                    break;
                }
            }
            if (!boundsMap && inheritSource_) {
                boundsMap = this._reduceBboxMap(this.configuration.options.bbox);
            }
            return boundsMap;
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
        // Custom toJSON for mbMap.getMapState()
        // Drops runtime-specific ollid and mqlid
        // Drops nativeLayers to avoid circular references
        toJSON: function() {
            return {
                id: this.id,
                origId: this.origId,
                title: this.title,
                type: this.type,
                configuration: this.configuration
            };
        }
    });
    Object.defineProperty(Source.prototype, 'ollid', {
        enumerable: true,
        get: function() {
            console.warn("Calling shimmed .ollid property accessor on source object", this);
            return (this.nativeLayers[0] || {}).id || null;
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
        if (!this.options.origId && this.options.id) {
            this.options.origId = this.options.id;
        }
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
        rewriteChildIds: function(parentId) {
            if (!this.options.origId) {
                this.options.origId = this.options.id;
            }
            this.options.id = [parentId, '_', this.siblings.indexOf(this)].join('');
            var nChildren = this.children && this.children.length || 0;
            for (var chIx = 0; chIx < nChildren; ++chIx) {
                this.children[chIx].rewriteChildIds(this.options.id);
            }
            if (!this.options.origId) {
                this.options.origId = this.options.id;
            }
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

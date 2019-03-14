window.Mapbender = Mapbender || {};
window.Mapbender.Source = (function() {
    function Source(definition) {
        if (definition.id || definition.id === 0) {
            this.id = '' + definition.id;
        }
        if (definition.origId || definition.origId === 0) {
            this.origId = '' + definition.origId;
        }
        this.title = definition.title;
        this.type = definition.type;
        this.configuration = definition.configuration;
        this.configuration.children = (this.configuration.children || []).map(function(childDef) {
            return Mapbender.SourceLayer.factory(childDef, definition, null)
        });
    }
    Source.typeMap = {};
    Source.factory = function(definition) {
        var typeClass = Source.typeMap[definition.type];
        if (!typeClass) {
            typeClass = Source;
        }
        return new typeClass(definition);
    };
    Source.prototype = {
        constructor: Source,
        initializeLayers: function() {
            console.error("Layer creation not implemented", this);
            throw new Error("Layer creation not implemented");
        },
        id: null,
        origId: null,
        mqlid: null,
        title: null,
        type: null,
        configuration: {},
        nativeLayers: [],
        recreateOnSrsSwitch: false,
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
                    olLayer.clearGrid();
                    olLayer.removeBackBuffer();
                    olLayer.destroy(false);
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
    };
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
        this.options = definition.options || {};
        this.state = definition.state || {};
        this.parent = parent;
        this.source = source;
        if (!this.options.origId && this.options.id) {
            this.options.origId = this.options.id;
        }

        if (definition.children && definition.children.length) {
            var self = this, i;
            this.children = definition.children.map(function(childDef) {
                return SourceLayer.factory(childDef, source, self);
            });
            for (i = 0; i < this.children.length; ++i) {
                this.children[i].siblings = this.children;
            }
        } else {
            // Weird hack because not all places that check for child layers do so
            // by checking children && children.length, but only do a truthiness test
            this.children = null;
        }
        this.siblings = [this];
    }
    SourceLayer.prototype = {
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
        }
    };
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

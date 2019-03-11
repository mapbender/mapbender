window.Mapbender = $.extend(Mapbender || {}, (function() {
    function Source(definition) {
        if (definition.id || definition.id === 0) {
            this.id = '' + definition.id;
        }
        if (definition.origId || definition.origId === 0) {
            this.origId = '' + definition.origId;
        }
        this.mqlid = definition.mqlid;
        this.ollid = definition.ollid;
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
        id: null,
        origId: null,
        ollid: null,
        mqlid: null,
        title: null,
        type: null,
        configuration: {},
        rewriteLayerIds: function() {
            if (!this.id) {
                throw new Error("Can't rewrite layer ids with empty source id");
            }
            var rootLayer = this.configuration.children[0];
            rootLayer.rewriteChildIds(this.id);
        }
    };

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
    return {
        Source: Source,
        SourceLayer: SourceLayer
    };
}()));

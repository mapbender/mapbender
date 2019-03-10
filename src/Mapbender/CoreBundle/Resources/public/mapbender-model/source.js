window.Mapbender = $.extend(Mapbender || {}, (function() {
    function Source(definition) {
        this.id = definition.id;
        this.mqlid = definition.mqlid;
        this.ollid = definition.ollid;
        this.origId = definition.origId;
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
    function SourceLayer(definition, source, parent) {
        this.options = definition.options || {};
        this.state = definition.state || {};
        this.parent = parent;
        this.source = source;

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

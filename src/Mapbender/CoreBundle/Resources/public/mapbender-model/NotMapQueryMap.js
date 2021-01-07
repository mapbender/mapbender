window.Mapbender = Mapbender || {};
window.Mapbender.NotMapQueryMap = (function() {
    // NotMapQueryMap, unlike MapQuery.Map, doesn't try to know better how to initialize
    // an OpenLayers Map, doesn't pre-assume any map properties, doesn't mess with default
    // map controls, doesn't prevent us from passing in layers, doesn't inherently combine layer
    // creation and adding layers to the map into the same operation etc.
    // The OpenLayers Map is simply passed in.
    function NotMapQueryMap($element, olMap) {
        this.idCounter = 0;
        this.element = $element;
        $element.data('mapQuery', this);
        this.olMap = olMap;
    }
    NotMapQueryMap.FakeVectorLayer = (function() {
        function FakeVectorLayer(id, olLayer, fakeMqMap) {
            this.id = id;
            this.label = olLayer.name;
            this.map = fakeMqMap;
            this.olLayer = olLayer;
            this.source = null;
        }
        return FakeVectorLayer;
    }());
    NotMapQueryMap.FakeSourceLayer = (function() {
        function FakeSourceLayer(id, source, fakeMqMap) {
            this.id = id;
            this.map = fakeMqMap;
            this.source = source;
        }
        // for older special snowflake versions of FeatureInfo
        Object.defineProperty(FakeSourceLayer.prototype, 'label', {
            configurable: false,
            enumerable: true,
            get: function() {
                return (this.source.getNativeLayer(0) || {}).name || this.id;
            }
        });
        Object.defineProperty(FakeSourceLayer.prototype, 'olLayer', {
            configurable: false,
            enumerable: true,
            get: function() {
                console.warn("Access to legacy olLayer property on NotMapQueryMap.FakeSourceLayer. Please switch to Mapbender.Model.getNativeLayer");
                return this.source.getNativeLayer();
            }
        });

        return FakeSourceLayer;
    }());
    NotMapQueryMap.prototype = {
        constructor: NotMapQueryMap,
        layers: function(layerOptions) {
            if (arguments.length !== 1 || Array.isArray(layerOptions) || layerOptions.type !== 'vector') {
                console.error("Unsupported MapQueryish layers call", arguments);
                throw new Error("Unsupported MapQueryish layers call");
            }
            console.warn("Engaging legacy emulation for MapQuery.Map.layers(), only allowed for 'vector' type. Please stop using this.", arguments);
            var fakeId = this._createId();
            var layerName = layerOptions.label || fakeId;
            var olLayer = new OpenLayers.Layer.Vector(layerName);
            var fakeMqLayer = new NotMapQueryMap.FakeVectorLayer(fakeId, olLayer, this);
            this.olMap.addLayer(olLayer);
            return fakeMqLayer;
        },
        _createId: function() {
            return 'certainly-not-mapquery-' + this.idCounter++;
        }
    };
    return NotMapQueryMap;
}());

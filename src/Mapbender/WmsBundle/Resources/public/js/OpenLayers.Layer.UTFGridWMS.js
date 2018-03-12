OpenLayers.Layer.UTFGridWMS = OpenLayers.Layer.UTFGridWMS || OpenLayers.Class(OpenLayers.Layer.WMS, {
    tileClass: OpenLayers.Tile.UTFGrid,
    CLASS_NAME: "OpenLayers.Layer.UTFGridWMS",
    initialize: function(name, url, params, options) {
        this.DEFAULT_PARAMS.format= "application/json";
        this.noMagic = true;

        OpenLayers.Layer.WMS.prototype.initialize.apply(this, arguments);
        this.tileOptions = OpenLayers.Util.extend({
            utfgridResolution: this.utfgridResolution
        }, this.tileOptions);
    },
    // inherit these methods from UTFGrid layer
    createBackBuffer: OpenLayers.Layer.UTFGrid.prototype.createBackBuffer,
    getFeatureInfo: OpenLayers.Layer.UTFGrid.prototype.getFeatureInfo,
    getFeatureId: OpenLayers.Layer.UTFGrid.prototype.getFeatureId
});

OpenLayers.Control.UTFGridWMS = OpenLayers.Control.UTFGridWMS || OpenLayers.Class(OpenLayers.Control.UTFGrid, {
    findLayers: function() {
        var candidates = this.layers || this.map.layers;
        var layers = [];
        var layer;
        for (var i=candidates.length-1; i>=0; --i) {
            layer = candidates[i];
            if (layer instanceof OpenLayers.Layer.UTFGrid
                || layer instanceof OpenLayers.Layer.UTFGridWMS) {
                layers.push(layer);
            }
        }
        return layers;
    }
});

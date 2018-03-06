OpenLayers.Layer.UTFGridWMS = OpenLayers.Layer.UTFGridWMS || OpenLayers.Class(OpenLayers.Layer.WMS, {
    tileClass: OpenLayers.Tile.UTFGrid,
    CLASS_NAME: "OpenLayers.Layer.UTFGridWMS",
    initialize: function(name, url, options) {
        this.DEFAULT_PARAMS.format= "application/json";
        this.noMagic = true;

        OpenLayers.Layer.WMS.prototype.initialize.apply(this, arguments);

        this.tileOptions = OpenLayers.Util.extend({
            utfgridResolution: this.utfgridResolution
        }, this.tileOptions);
    },
    createBackBuffer: function() {
        return;
    },
    getFeatureInfo: function() {
        return OpenLayers.Layer.UTFGrid.prototype.getFeatureInfo.apply(this, arguments);
    },
    getFeatureId: function() {
        return OpenLayers.Layer.UTFGrid.prototype.getFeatureId.apply(this, arguments);
    },
});

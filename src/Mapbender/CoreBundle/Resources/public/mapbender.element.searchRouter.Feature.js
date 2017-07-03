var Mapbender = Mapbender || {};

(function() {
    Mapbender.FeatureModel = Backbone.Model.extend({
        defaults: {
            geometry: {},
            properties: {}
        },

        feature: null,

        parse: function(response) {
            window.console && console.log("Feature", response);
        },

        getFeature: function() {
            if(this.feature === null) {
                this.feature = new OpenLayers.Format.GeoJSON().read({
                    type: 'Feature',
                    geometry: this.get('geometry'),
                    properties: this.get('properties')
                })[0];
                this.feature.model = this;
            }
            return this.feature;
        }
    });

    Mapbender.FeatureCollection = Backbone.Collection.extend({
        model: Mapbender.FeatureModel
    });

})();

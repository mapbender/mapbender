var Mapbender = Mapbender || {};

(function() {
    var format = new OpenLayers.Format.GeoJSON();

    Mapbender.FeatureModel = Backbone.Model.extend({
        defaults: {
            geometry: {},
            properties: {}
        },

        feature: null,

        parse: function(response) {
            console.log("Feature", response);
        },

        getFeature: function() {
            if(this.feature === null) {
                this.feature = format.read({
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

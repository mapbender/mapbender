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
                var geoJSON = new ol.format.GeoJSON();
                var geometry = this.attributes['geometry'];
                var properties = this.attributes['properties'];
                var jsonTemp = {
                    'type': 'Feature',
                    'geometry': geometry,
                    'properties': properties
                };
                this.feature = geoJSON.readFeature(jsonTemp)
            }
            return this.feature;
        }
    });

    Mapbender.FeatureCollection = Backbone.Collection.extend({
        model: Mapbender.FeatureModel
    });

})();

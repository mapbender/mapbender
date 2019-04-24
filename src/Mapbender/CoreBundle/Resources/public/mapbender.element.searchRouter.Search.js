var Mapbender = Mapbender || {};

/**
 * Mapbender Search Model.
 *
 * Incorporates the search properties (key/value pairs) and the results
 * collection.
 */
Mapbender.SearchModel = Backbone.Model.extend({
    defaults: {
        properties: {},
        results: new Mapbender.FeatureCollection(),
        srs: null,
        extent: null
    },

    url: function() {
        return this.router.callbackUrl + this.router.selected + '/search';
    },

    reset: function() {
        this.set({
            properties: {}
        }, {
            silent: true
        });

        var features = this.get('results').models;
        this.get('results').remove(features);
    },

    submit: function(event) {
        event.preventDefault();

        var properties = {},
            form = $(event.target),
            basename = form.attr('name');

        _.each($(':input', form), function(input, idx, all) {
            input = $(input);
            var name = input.attr('name'),
                key = name.substr(basename.length+1, name.length-basename.length-2),
                val = input.val()
            ;

            properties[key] = val;
        });

        var map = this.router.mbMap.map.olMap;

        this.set({
            properties: properties,
            extent: map.getExtent().toArray()
        });

        this.save();
    },

    initialize: function(attributes, options, router) {
        this.router = router;
    },

    /**
     * Filter out results before requesting again
     * @return object object for posting
     */
    toJSON: function() {
        var json = _.clone(this.attributes);
        delete json.results;
        return json;
    },

    /**
     * GeoJSON Feature parser
     * @param  {[type]} data [description]
     * @return {[type]}      [description]
     */
    parse: function(response) {
        return {
            properties: response.query,
            results: new Mapbender.FeatureCollection(response.features)
        };
    }
});

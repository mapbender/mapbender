var Mapbender = Mapbender || {};

/**
 * Mapbender Autocomplete Model.
 */
Mapbender.AutocompleteModel = Backbone.Model.extend({
    defaults: {
        key: null,
        value: null,
        properties: {},
        srs: null,
        extent: null,
        results: []
    },

    url: function() {
        return this.router.callbackUrl + this.router.selected + '/autocomplete';
    },

    submit: function(input, request) {
        var properties = {},
            form = input.closest('form'),
            basename = form.attr('name'),
            map = $('#' + this.router.options.target).data('mapbenderMbMap').map.olMap,
            name = input.attr('name');

        _.each($(':input', form), function(input, idx, all) {
            input = $(input);
            var name = input.attr('name'),
                key = name.substr(basename.length+1, name.length-basename.length-2),
                val = input.val();
            properties[key] = val;
        });

        this.set({
            key: name.substr(basename.length+1, name.length-basename.length-2),
            value: request.term,
            properties: properties,
            srs: map.getProjection(),
            extent: map.getExtent().toArray()
        });

        this.save();
    },

    initialize: function(attributes, options) {
        this.router = options.router;
    },

    toJSON: function() {
        var json = _.clone(this.attributes);
        delete json.results;
        return json;
    }
});

/**
 * Mapbender Search Model.
 *
 * Incorporates the search properties (key/value pairs) and the results
 * collection.
 */
Mapbender.SearchModel = Backbone.Model.extend({
    defaults: {
        properties: {},
        autocomplete_keys: {},
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
            autocomplete_keys = {},
            form = $(event.target),
            basename = form.attr('name');

        _.each($(':input', form), function(input, idx, all) {
            input = $(input);
            var name = input.attr('name'),
                key = name.substr(basename.length+1, name.length-basename.length-2),
                val = input.val(),
                autocomplete_key = input.attr('data-autocomplete-key');

            properties[key] = val;
            if(typeof autocomplete_key !== 'undefined') {
                autocomplete_keys[key] = autocomplete_key;
            }
        });

        var map = this.router.mbMap.map.olMap;

        this.set({
            properties: properties,
            autocomplete_keys: autocomplete_keys,
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

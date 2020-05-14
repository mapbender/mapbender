(function($) {

$.widget('mapbender.mbSimpleSearch', {
    options: {
        url: null,
        /** one of 'WKT', 'GeoJSON' */
        token_regex: null,
        token_regex_in: null,
        token_regex_out: null,
        label_attribute: null,
        geom_attribute: null,
        geom_format: null,
        result: {
            buffer: null,
            minscale: null,
            maxscale: null,
            icon_url: null,
            icon_offset: null
        },
        delay: 0
    },

    marker: null,
    layer: null,
    iconStyle: null,
    mbMap: null,

    _create: function() {
        var self = this;
        Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
            self.mbMap = mbMap;
            self._setup();
        });
    },
    _setup: function() {
        var self = this;
        var searchInput = $('.searchterm', this.element);
        var url = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search';
        this.layer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
        if (this.options.result.icon_url) {
            var offset = (this.options.result.icon_offset || '').split(new RegExp('[, ;]')).map(function(x) {
                return parseInt(x) || 0;
            });
            this.layer.addCustomIconMarkerStyle('simplesearch', this.options.result.icon_url, offset[0], offset[1]);
        }

        // Set up autocomplete
        this.autocomplete = new Mapbender.Autocomplete(searchInput, {
            url: url,
            delay: this.options.delay,
            dataTitle: this.options.label_attribute,
            dataIdx: null,
            preProcessor: $.proxy(this._tokenize, this)
        });

        // On manual submit (enter key, submit button), trigger autocomplete manually
        this.element.on('submit', function(evt) {
            var searchTerm = searchInput.val();
            if(searchTerm.length >= self.autocomplete.options.minLength) {
                self.autocomplete.find(searchTerm);
            }
            evt.preventDefault();
        });

        // On item selection in autocomplete, parse data and set map bbox
        searchInput.on('mbautocomplete.selected', $.proxy(this._onAutocompleteSelected, this));
    },
    _parseFeature: function(doc) {
        switch ((this.options.geom_format || '').toUpperCase()) {
            case 'WKT':
                return this.mbMap.getModel().parseWktFeature(doc);
            case 'GEOJSON':
                return this.mbMap.getModel().parseGeoJsonFeature(doc);
            default:
                throw new Error("Invalid geom_format " + this.options.geom_format);
        }
    },
    _onAutocompleteSelected: function(evt, evtData) {
        if(!evtData.data[this.options.geom_attribute]) {
            $.notify( Mapbender.trans("mb.core.simplesearch.error.geometry.missing"));
            return;
        }
        var feature = this._parseFeature(evtData.data[this.options.geom_attribute]);

        var zoomToFeatureOptions = this.options.result && {
            maxScale: parseInt(this.options.result.maxscale) || null,
            minScale: parseInt(this.options.result.minscale) || null,
            buffer: parseInt(this.options.result.buffer) || null
        };
        this.mbMap.getModel().zoomToFeature(feature, zoomToFeatureOptions);
        this._hideMobile();
        this._setFeatureMarker(feature);
    },
    _setFeatureMarker: function(feature) {
        this.layer.clear();
        Mapbender.vectorLayerPool.raiseElementLayers(this);
        var layer = this.layer;
        // @todo: add feature center / centroid api
        var bounds = Mapbender.mapEngine.getFeatureBounds(feature);
        var center = {
            lon: .5 * (bounds.left + bounds.right),
            lat: .5 * (bounds.top + bounds.bottom)
        };
        // fallback for broken icon: render a simple point geometry
        var onMissingIcon = function() {
            layer.addMarker(center.lon, center.lat);
        };
        if (this.options.result.icon_url) {
            layer.addIconMarker('simplesearch', center.lon, center.lat).then(null, onMissingIcon);
        } else {
            onMissingIcon();
        }
    },

    _hideMobile: function() {
        $('.mobileClose', $(this.element).closest('.mobilePane')).click();
    },

    _tokenize: function(string) {
        if (!(this.options.token_regex_in && this.options.token_regex_out)) return string;

        if (this.options.token_regex) {
            var regexp = new RegExp(this.options.token_regex, 'g');
            string = string.replace(regexp, " ");
        }

        var tokens = string.split(' ');
        var regex = new RegExp(this.options.token_regex_in);
        for(var i = 0; i < tokens.length; i++) {
            tokens[i] = tokens[i].replace(regex, this.options.token_regex_out);
        }

        return tokens.join(' ');
    }
});

})(jQuery);

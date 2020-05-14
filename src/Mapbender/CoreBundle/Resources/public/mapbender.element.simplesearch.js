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
    iconPromise: null,

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
        this.iconPromise = Mapbender.Util.preloadImageAsset(this.options.result.icon_url);

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
        var olMap = this.mbMap.getModel().olMap;
        var self = this;

        var bounds = feature.geometry.getBounds();

        // Add marker
        if(self.options.result.icon_url) {
            if(!self.marker) {
                this.iconPromise.then(function(image) {
                    var offset = (self.options.result.icon_offset || '').split(new RegExp('[, ;]'));
                    var x = parseInt(offset[0]);

                    var size = {
                        'w': image.naturalWidth,
                        'h': image.naturalHeight
                    };

                    var y = parseInt(offset[1]);

                    offset = {
                        'x': !isNaN(x) ? x : 0,
                        'y': !isNaN(y) ? y : 0
                    };

                    // 4
                    self.iconStyle = self.model.createIconStyle({src: image.src, size: [size.w, size.h], offset: [offset.x, offset.y]});
                    self.layer = self.model.setMarkerOnCoordinates(center, self.element.attr('id'), self.layer, self.iconStyle);
                    // 2
                    var icon = new OpenLayers.Icon(image.src, size, offset);
                    self.marker = new OpenLayers.Marker(bounds.getCenterLonLat(), icon);
                    self.layer = new OpenLayers.Layer.Markers();
                    olMap.addLayer(self.layer);
                    self.layer.addMarker(self.marker);
                });
            } else {
                var newPx = olMap.getLayerPxFromLonLat(bounds.getCenterLonLat());
                self.marker.moveTo(newPx);
            }
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
